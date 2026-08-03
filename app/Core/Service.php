<?php

declare(strict_types=1);

namespace App\Core;

abstract class Service
{
    protected function transaction(callable $callback): mixed
    {
        return Database::transaction($callback);
    }

    protected function validate(array $data, array $rules): array
    {
        $validator = new Validator($data, $rules);

        if ($validator->fails()) {
            throw new \App\Exceptions\ValidationException($validator->errors());
        }

        return $validator->validated();
    }

    protected function log(string $message, array $context = []): void
    {
        Logger::info($message, $context);
    }
}
