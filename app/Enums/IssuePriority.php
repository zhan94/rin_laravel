<?php

declare(strict_types=1);

namespace App\Enums;

enum IssuePriority: string
{
    case NORMAL = 'normal';
    case MEDIUM = 'medium';
    case URGENT = 'urgent';
}
