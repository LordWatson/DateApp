<?php

namespace App\Events;

use App\Models\Question;
use App\Models\Response;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class QuestionAnswered
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly Response $response,
        public readonly Question $question,
        public readonly mixed $value,
    ) {}
}
