<?php

declare(strict_types=1);

namespace App\Core;

class Validator
{
    private array $data;
    private array $rules;
    private array $errors    = [];
    private array $validated = [];

    private static array $customMessages = [];

    public function __construct(array $data, array $rules)
    {
        $this->data  = $data;
        $this->rules = $rules;
        $this->validate();
    }

    private function validate(): void
    {
        foreach ($this->rules as $field => $ruleSet) {
            $rules = is_string($ruleSet) ? explode('|', $ruleSet) : $ruleSet;
            $value = $this->data[$field] ?? null;

            foreach ($rules as $rule) {
                [$ruleName, $ruleParam] = $this->parseRule($rule);

                if ($ruleName === 'nullable' && ($value === null || $value === '')) {
                    $this->validated[$field] = null;
                    break;
                }

                if ($ruleName === 'sometimes' && !array_key_exists($field, $this->data)) {
                    break;
                }

                if (!$this->applyRule($ruleName, $field, $value, $ruleParam)) {
                    break;
                }
            }

            if (!isset($this->errors[$field]) && array_key_exists($field, $this->data)) {
                $this->validated[$field] = $value;
            }
        }
    }

    private function parseRule(string $rule): array
    {
        if (str_contains($rule, ':')) {
            [$name, $param] = explode(':', $rule, 2);
            return [$name, $param];
        }
        return [$rule, null];
    }

    private function applyRule(string $rule, string $field, mixed $value, ?string $param): bool
    {
        $label = ucfirst(str_replace('_', ' ', $field));

        return match ($rule) {
            'required'  => $this->assertOrFail($value !== null && $value !== '', $field, "{$label} is required."),
            'string'    => $this->assertOrFail(is_string($value) || $value === null, $field, "{$label} must be a string."),
            'integer'   => $this->assertOrFail(filter_var($value, FILTER_VALIDATE_INT) !== false, $field, "{$label} must be an integer."),
            'numeric'   => $this->assertOrFail(is_numeric($value), $field, "{$label} must be numeric."),
            'boolean'   => $this->assertOrFail(in_array($value, [true, false, 0, 1, '0', '1', 'true', 'false'], true), $field, "{$label} must be boolean."),
            'email'     => $this->assertOrFail(filter_var($value, FILTER_VALIDATE_EMAIL) !== false, $field, "{$label} must be a valid email address."),
            'url'       => $this->assertOrFail(filter_var($value, FILTER_VALIDATE_URL) !== false, $field, "{$label} must be a valid URL."),
            'min'       => $this->assertOrFail($this->validateMin($value, $param), $field, is_numeric($value) ? "{$label} must be at least {$param}." : "{$label} must be at least {$param} characters."),
            'max'       => $this->assertOrFail($this->validateMax($value, $param), $field, is_numeric($value) ? "{$label} must not exceed {$param}." : "{$label} must not exceed {$param} characters."),
            'between'   => $this->validateBetween($field, $label, $value, $param),
            'in'        => $this->assertOrFail(in_array($value, explode(',', $param ?? '')), $field, "{$label} must be one of: {$param}."),
            'not_in'    => $this->assertOrFail(!in_array($value, explode(',', $param ?? '')), $field, "{$label} contains an invalid value."),
            'confirmed' => $this->assertOrFail(($this->data[$field . '_confirmation'] ?? null) === $value, $field, "{$label} confirmation does not match."),
            'unique'    => $this->validateUnique($field, $label, $value, $param),
            'exists'    => $this->validateExists($field, $label, $value, $param),
            'date'      => $this->assertOrFail(strtotime($value) !== false, $field, "{$label} must be a valid date."),
            'regex'     => $this->assertOrFail(preg_match($param, $value) === 1, $field, "{$label} format is invalid."),
            'array'     => $this->assertOrFail(is_array($value), $field, "{$label} must be an array."),
            'alpha'     => $this->assertOrFail(ctype_alpha($value), $field, "{$label} must contain only letters."),
            'alpha_num' => $this->assertOrFail(ctype_alnum($value), $field, "{$label} must contain only letters and numbers."),
            'nullable', 'sometimes' => true,
            default     => true,
        };
    }

    private function assertOrFail(bool $condition, string $field, string $message): bool
    {
        if (!$condition) {
            $this->errors[$field][] = $message;
            return false;
        }
        return true;
    }

    private function validateMin(mixed $value, ?string $param): bool
    {
        if ($param === null) return true;
        return is_numeric($value) ? (float) $value >= (float) $param : strlen((string) $value) >= (int) $param;
    }

    private function validateMax(mixed $value, ?string $param): bool
    {
        if ($param === null) return true;
        return is_numeric($value) ? (float) $value <= (float) $param : strlen((string) $value) <= (int) $param;
    }

    private function validateBetween(string $field, string $label, mixed $value, ?string $param): bool
    {
        [$min, $max] = explode(',', $param ?? '0,0');
        $length = is_numeric($value) ? (float) $value : strlen((string) $value);
        return $this->assertOrFail($length >= (float) $min && $length <= (float) $max, $field, "{$label} must be between {$min} and {$max}.");
    }

    private function validateUnique(string $field, string $label, mixed $value, ?string $param): bool
    {
        // param format: table,column,ignore_id
        $parts  = explode(',', $param ?? '');
        $table  = $parts[0] ?? '';
        $column = $parts[1] ?? $field;
        $ignore = $parts[2] ?? null;

        if (!$table) return true;

        $sql    = "SELECT COUNT(*) as count FROM {$table} WHERE {$column} = ?";
        $params = [$value];

        if ($ignore) {
            $sql      .= ' AND id != ?';
            $params[]  = $ignore;
        }

        $row = Database::selectOne($sql, $params);
        return $this->assertOrFail((int) ($row['count'] ?? 0) === 0, $field, "{$label} is already taken.");
    }

    private function validateExists(string $field, string $label, mixed $value, ?string $param): bool
    {
        $parts  = explode(',', $param ?? '');
        $table  = $parts[0] ?? '';
        $column = $parts[1] ?? 'id';

        if (!$table) return true;

        $row = Database::selectOne("SELECT 1 FROM {$table} WHERE {$column} = ? LIMIT 1", [$value]);
        return $this->assertOrFail($row !== false, $field, "Selected {$label} does not exist.");
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    public function validated(): array
    {
        return $this->validated;
    }
}
