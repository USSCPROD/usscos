<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Customer extends Model
{
    protected string $table      = 'customers';
    protected string $primaryKey = 'id';
}
