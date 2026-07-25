<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AI\AIUseCase;
use App\Models\AiPromptTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiPromptTemplate>
 */
class AiPromptTemplateFactory extends Factory
{
    protected $model = AiPromptTemplate::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(array_map(
                static fn (AIUseCase $case): string => $case->value,
                AIUseCase::cases(),
            )),
            'system_prompt' => 'You are the Date Night AI assistant. Respond with valid JSON.',
            'user_prompt_template' => 'Context: {{context}}',
            'description' => $this->faker->sentence(),
            'version' => 1,
            'active' => true,
        ];
    }

    public function forUseCase(AIUseCase $useCase): self
    {
        return $this->state(fn (): array => ['name' => $useCase->value]);
    }

    public function inactive(): self
    {
        return $this->state(fn (): array => ['active' => false]);
    }
}
