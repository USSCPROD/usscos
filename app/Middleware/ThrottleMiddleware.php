<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

class ThrottleMiddleware
{
    private int $maxAttempts;
    private int $decayMinutes;

    public function __construct(int $maxAttempts = 60, int $decayMinutes = 1)
    {
        $this->maxAttempts  = $maxAttempts;
        $this->decayMinutes = $decayMinutes;
    }

    public function handle(Request $request, Response $response, callable $next): Response
    {
        $key     = 'throttle:' . $request->ip() . ':' . $request->uri();
        $hitFile = sys_get_temp_dir() . '/' . md5($key) . '.throttle';

        $data = $this->readHitData($hitFile);

        if ($data['reset_at'] < time()) {
            $data = ['hits' => 0, 'reset_at' => time() + ($this->decayMinutes * 60)];
        }

        $data['hits']++;
        $this->writeHitData($hitFile, $data);

        if ($data['hits'] > $this->maxAttempts) {
            $retryAfter = $data['reset_at'] - time();
            $response->header('Retry-After', (string) $retryAfter);
            $response->header('X-RateLimit-Limit', (string) $this->maxAttempts);
            $response->header('X-RateLimit-Remaining', '0');

            if ($request->isAjax()) {
                return $response->json(['error' => 'Too many requests.'], 429);
            }
            return $response->html('Too many requests. Please try again later.', 429);
        }

        $response->header('X-RateLimit-Limit', (string) $this->maxAttempts);
        $response->header('X-RateLimit-Remaining', (string) ($this->maxAttempts - $data['hits']));

        return $next();
    }

    private function readHitData(string $file): array
    {
        if (!file_exists($file)) {
            return ['hits' => 0, 'reset_at' => 0];
        }
        return json_decode(file_get_contents($file), true) ?: ['hits' => 0, 'reset_at' => 0];
    }

    private function writeHitData(string $file, array $data): void
    {
        file_put_contents($file, json_encode($data), LOCK_EX);
    }
}
