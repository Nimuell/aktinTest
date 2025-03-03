<?php

declare(strict_types=1);

namespace App\Enum;

enum UserRole: string
{
    case ADMIN = 'ROLE_ADMIN';
    case AUTHOR = 'ROLE_AUTHOR';
    case READER = 'ROLE_READER';
} 