<?php

declare(strict_types=1);

namespace App\Exceptions;

class ValidationException extends \RuntimeException
{
    private array $errors;

    public function __construct(array $errors, string $message = 'Validation failed.', int $code = 422)
    {
        parent::__construct($message, $code);
        $this->errors = $errors;
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
