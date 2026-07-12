<?php

namespace App\Enums;

enum QuestionType: string
{
    case SingleChoice = 'single_choice';
    case MultipleChoice = 'multiple_choice';
    case Slider = 'slider';
    case Text = 'text';
    case TextArea = 'textarea';
}
