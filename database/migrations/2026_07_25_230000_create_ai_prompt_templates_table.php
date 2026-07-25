<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_prompt_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->index();
            $table->text('system_prompt');
            $table->text('user_prompt_template');
            $table->string('description')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->boolean('active')->default(true)->index();
            $table->timestamps();

            $table->unique(['name', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_prompt_templates');
    }
};
