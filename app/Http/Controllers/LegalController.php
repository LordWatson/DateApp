<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class LegalController extends Controller
{
    public function privacy(): Response
    {
        return Inertia::render('legal/Privacy', [
            ...$this->operator(),
            'aiProvider' => match (config('ai.provider')) {
                'anthropic' => 'Anthropic (Claude)',
                'deepseek' => 'DeepSeek',
                default => null,
            },
        ]);
    }

    public function terms(): Response
    {
        return Inertia::render('legal/Terms', $this->operator());
    }

    /**
     * @return array{operator: string, contactEmail: string}
     */
    private function operator(): array
    {
        return [
            'operator' => (string) config('app.legal.operator'),
            'contactEmail' => (string) config('app.legal.contact_email'),
        ];
    }
}
