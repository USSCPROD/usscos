<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class User extends Model
{
    protected string $table       = 'users';
    protected bool   $softDeletes = true;

    protected array $fillable = [
        'company_id',
        'first_name',
        'last_name',
        'email',
        'password',
        'role',
        'permissions',
        'is_active',
        'avatar',
        'phone',
    ];

    protected array $hidden = [
        'password',
        'remember_token',
        'password_reset_token',
    ];

    protected array $casts = [
        'id'          => 'integer',
        'company_id'  => 'integer',
        'is_active'   => 'boolean',
        'permissions' => 'array',
    ];

    public function fullName(): string
    {
        return trim(($this->getAttribute('first_name') ?? '') . ' ' . ($this->getAttribute('last_name') ?? ''));
    }

    public function initials(): string
    {
        $first = $this->getAttribute('first_name') ?? '';
        $last  = $this->getAttribute('last_name')  ?? '';
        return strtoupper(substr($first, 0, 1) . substr($last, 0, 1));
    }
}
