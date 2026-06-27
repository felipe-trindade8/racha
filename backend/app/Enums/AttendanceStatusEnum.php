<?php

namespace App\Enums;

enum AttendanceStatusEnum: string
{
    case Available = 'available';
    case Injured = 'injured';
    // The player cannot attend this match.
    case Missing = 'missing';
}
