<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;

/**
 * Talking to FedEx.
 *
 * Two sets of credentials, because the FedEx developer portal will not put Ship and Track
 * in one project: shipping (labels, rates, address validation) and tracking (Basic
 * Integrated Visibility, which carries transaction quotas).
 *
 * Tokens last an hour and are cached on disk, so a busy afternoon at the bench costs one
 * token call rather than one per label. The tracking quota is the reason this matters:
 * spending calls on authentication would be spending them on nothing.
 *
 * Nothing here throws on a FedEx failure. A carrier being down must not stop an order
 * shipping — the paint still goes on the truck — so every call returns a result the caller
 * can decide about, and the failure is logged rather than raised.
 */
class FedExClient
{
    public const PROJECT_SHIP  = 'ship';
    public const PROJECT_TRACK = 'track';

    public function environment(): string
    {
        return strtolower(trim((string)($_ENV['FEDEX_ENVIRONMENT'] ?? 'sandbox'))) === 'production'
            ? 'production'
            : 'sandbox';
    }

    public function baseUrl(): string
    {
        return $this->environment() === 'production'
            ? 'https://apis.fedex.com'
            : 'https://apis-sandbox.fedex.com';
    }

    public function accountNumber(): string
    {
        return trim((string)($_ENV['FEDEX_ACCOUNT_NUMBER'] ?? ''));
    }

    /** Whether a project has credentials at all — absent means the feature simply stays off. */
    public function configured(string $project = self::PROJECT_SHIP): bool
    {
        [$key, $secret] = $this->credentials($project);

        return $key !== '' && $secret !== '';
    }

    /**
     * A bearer token for one project, from cache when it is still good.
     *
     * Refreshed a minute early, because a token that expires mid-request is a failure that
     * looks like a credential problem and wastes an afternoon.
     */
    public function token(string $project = self::PROJECT_SHIP): ?string
    {
        [$key, $secret] = $this->credentials($project);

        if ($key === '' || $secret === '') {
            return null;
        }

        $cacheFile = $this->cachePath($project);
        $cached    = $this->readCache($cacheFile);

        if ($cached !== null) {
            return $cached;
        }

        $res = $this->request('POST', '/oauth/token', [
            'grant_type'    => 'client_credentials',
            'client_id'     => $key,
            'client_secret' => $secret,
        ], null, true);

        if (!$res['ok'] || empty($res['body']['access_token'])) {
            Logger::error('FedEx auth failed (' . $project . '): ' . $this->errorOf($res));

            return null;
        }

        $token   = (string)$res['body']['access_token'];
        $expires = time() + max(60, (int)($res['body']['expires_in'] ?? 3600) - 60);

        $this->writeCache($cacheFile, $token, $expires);

        return $token;
    }

    /**
     * Call a FedEx endpoint with a bearer token.
     *
     * @return array{ok:bool, status:int, body:array, error:?string}
     */
    public function call(string $method, string $path, array $payload = [], string $project = self::PROJECT_SHIP): array
    {
        $token = $this->token($project);

        if ($token === null) {
            return ['ok' => false, 'status' => 0, 'body' => [], 'error' => 'FedEx is not configured, or authentication failed.'];
        }

        $res = $this->request($method, $path, $payload, $token);

        if (!$res['ok']) {
            Logger::error('FedEx ' . $path . ' failed: ' . $this->errorOf($res));
            $res['error'] = $this->errorOf($res);
        }

        return $res;
    }

    /** The first human-readable message FedEx gave us, or something honest if it gave none. */
    public function errorOf(array $res): string
    {
        if (!empty($res['error'])) {
            return (string)$res['error'];
        }

        $errors = $res['body']['errors'] ?? [];

        if (is_array($errors) && $errors !== []) {
            return implode('; ', array_map(
                fn($e) => trim(($e['code'] ?? '') . ' ' . ($e['message'] ?? '')),
                $errors
            ));
        }

        return 'FedEx returned HTTP ' . ($res['status'] ?? 0) . ' with no message.';
    }

    /** @return array{0:string, 1:string} */
    private function credentials(string $project): array
    {
        return $project === self::PROJECT_TRACK
            ? [trim((string)($_ENV['FEDEX_TRACK_API_KEY'] ?? '')), trim((string)($_ENV['FEDEX_TRACK_SECRET_KEY'] ?? ''))]
            : [trim((string)($_ENV['FEDEX_API_KEY'] ?? '')),       trim((string)($_ENV['FEDEX_SECRET_KEY'] ?? ''))];
    }

    /**
     * @return array{ok:bool, status:int, body:array, error:?string}
     */
    private function request(string $method, string $path, array $payload, ?string $token, bool $form = false): array
    {
        $ch = curl_init($this->baseUrl() . $path);

        $headers = ['Accept: application/json'];

        if ($token !== null) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        $headers[] = $form ? 'Content-Type: application/x-www-form-urlencoded' : 'Content-Type: application/json';

        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 45,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_HTTPHEADER     => $headers,
        ]);

        if ($payload !== [] || $method !== 'GET') {
            curl_setopt(
                $ch,
                CURLOPT_POSTFIELDS,
                $form ? http_build_query($payload) : json_encode($payload)
            );
        }

        $raw    = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err    = curl_error($ch);
        curl_close($ch);

        if ($err !== '') {
            return ['ok' => false, 'status' => 0, 'body' => [], 'error' => $err];
        }

        $body = json_decode((string)$raw, true);

        return [
            'ok'     => $status >= 200 && $status < 300,
            'status' => $status,
            'body'   => is_array($body) ? $body : [],
            'error'  => null,
        ];
    }

    private function cachePath(string $project): string
    {
        return BASE_PATH . '/storage/cache/fedex_token_' . $project . '_' . $this->environment() . '.json';
    }

    private function readCache(string $file): ?string
    {
        if (!is_readable($file)) {
            return null;
        }

        $data = json_decode((string)@file_get_contents($file), true);

        if (!is_array($data) || empty($data['token']) || (int)($data['expires'] ?? 0) <= time()) {
            return null;
        }

        return (string)$data['token'];
    }

    private function writeCache(string $file, string $token, int $expires): void
    {
        // A token is a credential, so it is written 0600 and never 0644 like the other
        // things in storage.
        @file_put_contents($file, json_encode(['token' => $token, 'expires' => $expires]), LOCK_EX);
        @chmod($file, 0600);
    }
}
