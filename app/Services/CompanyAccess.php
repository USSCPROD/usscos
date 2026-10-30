<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Session;

final class CompanyAccess
{
    public const COMPANIES = ['ussc' => 'US Specialty Coatings', 'tcc' => 'Technical Coatings'];

    public static function allowed(array $user): array
    {
        $permissions = json_decode($user['permissions'] ?? '[]', true);
        if (!is_array($permissions)) {
            return [];
        }
        $configured = in_array('company:configured', $permissions, true);
        $allowed = [];
        foreach (self::COMPANIES as $key => $name) {
            if (in_array('company:' . $key, $permissions, true)) {
                $allowed[] = $key;
                $configured = true;
            }
        }
        // Existing accounts retain only their existing USSC workspace.
        return $configured ? $allowed : ['ussc'];
    }

    public static function current(): ?string
    {
        $user = Auth::user();
        if ($user === null) {
            return null;
        }
        $allowed = self::allowed($user);
        $current = Session::get('active_company');
        return in_array($current, $allowed, true) ? $current : ($allowed[0] ?? null);
    }

    public static function switchTo(string $company): void
    {
        if (!in_array($company, self::allowed(Auth::user() ?? []), true)) {
            throw new \InvalidArgumentException('You do not have access to that company.');
        }
        Session::set('active_company', $company);
        // Old forms in other tabs must not submit under a different company.
        Session::set('_csrf_token', bin2hex(random_bytes(32)));
    }

    public static function permissionsFor(array $actor, ?array $target, array $input): string
    {
        if (!in_array($actor['role'] ?? '', ['owner', 'admin'], true)) {
            throw new \InvalidArgumentException('Only owners and admins may manage company access.');
        }
        if (($actor['role'] ?? '') !== 'owner' && (($target['role'] ?? '') === 'owner' || ($input['role'] ?? '') === 'owner')) {
            throw new \InvalidArgumentException('Only an owner may manage an owner account.');
        }
        $selected = $input['company_access'] ?? [];
        if (!is_array($selected) || $selected === [] || array_diff($selected, array_keys(self::COMPANIES))) {
            throw new \InvalidArgumentException('Select at least one valid company.');
        }
        if (($actor['role'] ?? '') !== 'owner') {
            $existing = $target === null ? [] : self::allowed($target);
            $changed = array_merge(array_diff($selected, $existing), array_diff($existing, $selected));
            if (array_diff($changed, self::allowed($actor))) {
                throw new \InvalidArgumentException('You can only change access to companies you can access.');
            }
        }
        if ($target !== null && (int)$actor['id'] === (int)$target['id'] &&
            (!in_array('ussc', $selected, true) || ($input['role'] ?? '') !== $actor['role'] || (int)($input['is_active'] ?? 1) !== 1)) {
            throw new \InvalidArgumentException('Keep your own admin role, active status, and USSC access. Ask another owner to change them.');
        }
        $permissions = json_decode($target['permissions'] ?? '[]', true);
        if (!is_array($permissions)) {
            throw new \InvalidArgumentException('Existing permissions need review before saving.');
        }
        $permissions = array_values(array_filter($permissions, static fn ($p) => !is_string($p) || !str_starts_with($p, 'company:')));
        $permissions[] = 'company:configured';
        foreach (array_unique($selected) as $company) {
            $permissions[] = 'company:' . $company;
        }
        return json_encode($permissions, JSON_THROW_ON_ERROR);
    }
}
