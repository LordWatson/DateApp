<?php

namespace App\Events;

use App\Models\LoveNote;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class LoveNoteSent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly User $sender,
        public readonly User $recipient,
        public readonly LoveNote $loveNote,
    ) {}
}
