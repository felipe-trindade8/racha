<?php

namespace App\Enums;

enum PlayerStatusEnum: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Injured = 'injured';
}
