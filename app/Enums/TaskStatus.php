<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Todo = 'todo';
    case Doing = 'doing';
    case Review = 'review';
    case WaitingClient = 'waiting_client';
    case Done = 'done';
    case Cancelled = 'cancelled';
}
