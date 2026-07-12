<?php

namespace App\Enums;

enum CompletionStatus: string
{
    case InProgress = 'in_progress';
    case Completed = 'completed';
}
