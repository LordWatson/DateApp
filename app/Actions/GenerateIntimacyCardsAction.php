<?php

namespace App\Actions;

use App\Enums\AI\AIUseCase;
use App\Models\IntimacyGame;
use App\Models\User;
use App\Services\AI\AIService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class GenerateIntimacyCardsAction
{
    /** One request writes the whole deck: about three times faster than a request per card. */
    public const DECK_SIZE = 10;

    private const MAX_CARD_LENGTH = 300;

    public function __construct(
        private readonly AIService $ai,
    ) {}

    /**
     * Ask the AI for a fresh deck of cards for this game.
     *
     * Returns an empty list when the AI is unavailable or answers badly; the
     * game page then plays the game's own stored cards.
     *
     * @param  list<string>  $alreadyPlayed  Cards the players have had this session, so none comes back.
     * @return list<string>
     */
    public function execute(IntimacyGame $game, User $user, array $alreadyPlayed = []): array
    {
        try {
            // The context is the game itself and holds nothing about the players, so no one's consent is needed.
            $response = $this->ai->generate(AIUseCase::IntimacyGame, ['context' => [
                'count' => self::DECK_SIZE,
                'title' => $game->title,
                'tagline' => $game->tagline,
                'description' => $game->description,
                'how_to_play' => $game->how_to_play,
                'category' => $game->category->label(),
                'intensity' => $game->intensity->label(),
                'players' => $game->players,
                'example_cards' => array_slice($game->prompts ?? [], 0, 5),
                'already_played' => $alreadyPlayed,
            ]], [], $game, $user->id);
        } catch (Throwable $e) {
            Log::warning('Intimacy cards could not be generated.', ['game' => $game->slug, 'exception' => $e->getMessage()]);

            return [];
        }

        if (! $response->successful) {
            return [];
        }

        // Lower-cased cards that must not come back: played, stored, or already in this deck.
        $known = array_map(Str::lower(...), [...$alreadyPlayed, ...$game->prompts ?? []]);
        $returned = $response->data['cards'] ?? [];
        $cards = [];

        foreach (is_array($returned) ? $returned : [] as $card) {
            $card = is_string($card) ? trim($card) : '';

            if ($card === '' || Str::length($card) > self::MAX_CARD_LENGTH || in_array(Str::lower($card), $known, true)) {
                continue;
            }

            $known[] = Str::lower($card);
            $cards[] = $card;
        }

        return array_slice($cards, 0, self::DECK_SIZE);
    }
}
