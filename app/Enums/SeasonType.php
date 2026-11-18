<?php

declare(strict_types=1);

namespace App\Enums;

enum SeasonType: string
{
    case WINTER = 'winter';
    case SUMMER = 'summer';
    case OTHER = 'other';
}
