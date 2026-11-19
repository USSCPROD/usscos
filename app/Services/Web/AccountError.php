<?php

declare(strict_types=1);

namespace App\Services\Web;

/** Something a website customer did that we refuse, with a message meant for them. */
class AccountError extends \Exception
{
    public function __construct(string $message, private int $status = 422)
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return $this->status;
    }
}
