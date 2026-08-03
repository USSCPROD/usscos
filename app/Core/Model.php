<?php

declare(strict_types=1);

namespace App\Core;

abstract class Model
{
    protected string $table        = '';
    protected string $primaryKey   = 'id';
    protected bool   $timestamps   = true;
    protected bool   $softDeletes  = false;
    protected array  $fillable     = [];
    protected array  $guarded      = ['id'];
    protected array  $casts        = [];
    protected array  $hidden       = ['password', 'remember_token'];
    protected array  $attributes   = [];

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            }
        }
        return $this;
    }

    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $this->castAttribute($key, $value);
    }

    public function getAttribute(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    public function __get(string $key): mixed
    {
        return $this->getAttribute($key);
    }

    public function __set(string $key, mixed $value): void
    {
        $this->setAttribute($key, $value);
    }

    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    protected function isFillable(string $key): bool
    {
        if (in_array($key, $this->guarded)) {
            return false;
        }
        return empty($this->fillable) || in_array($key, $this->fillable);
    }

    protected function castAttribute(string $key, mixed $value): mixed
    {
        if (!isset($this->casts[$key]) || $value === null) {
            return $value;
        }

        return match ($this->casts[$key]) {
            'int', 'integer'  => (int) $value,
            'float', 'double' => (float) $value,
            'bool', 'boolean' => (bool) $value,
            'string'          => (string) $value,
            'array'           => is_string($value) ? json_decode($value, true) : (array) $value,
            'json'            => is_string($value) ? json_decode($value, true) : $value,
            'datetime'        => $value,
            default           => $value,
        };
    }

    public function toArray(): array
    {
        $attributes = $this->attributes;
        foreach ($this->hidden as $key) {
            unset($attributes[$key]);
        }
        return $attributes;
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }

    public function exists(): bool
    {
        return isset($this->attributes[$this->primaryKey]);
    }

    public function getKey(): mixed
    {
        return $this->attributes[$this->primaryKey] ?? null;
    }

    public function getTable(): string
    {
        if ($this->table) {
            return $this->table;
        }
        // Derive table name from class name: UserProfile -> user_profiles
        $class = (new \ReflectionClass($this))->getShortName();
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $class)) . 's';
    }

    protected function getTimestampColumns(): array
    {
        return ['created_at', 'updated_at'];
    }
}
