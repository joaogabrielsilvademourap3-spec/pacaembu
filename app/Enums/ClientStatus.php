<?php

namespace App\Enums;

enum ClientStatus: string
{
    case Active = 'active';
    case Paused = 'paused';
    case Cancelled = 'cancelled';
    case Prospect = 'prospect';
}
