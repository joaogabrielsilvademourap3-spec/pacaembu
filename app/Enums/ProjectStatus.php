<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case Planning = 'planning';
    case InProgress = 'in_progress';
    case WaitingApproval = 'waiting_approval';
    case Revision = 'revision';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
