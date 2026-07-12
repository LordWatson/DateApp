<?php

namespace App\Enums;

enum QuestionnaireStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';
}
