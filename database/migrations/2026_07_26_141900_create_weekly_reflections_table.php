<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weekly_reflections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('partner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('week_start');
            $table->date('week_end');
            $table->string('headline');
            $table->text('summary');
            $table->json('highlights')->nullable();
            $table->text('gentle_suggestion')->nullable();
            $table->text('encouragement')->nullable();
            $table->json('metrics')->nullable();
            $table->boolean('fallback_used')->default(false);
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'week_start']);
            $table->index(['user_id', 'week_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_reflections');
    }
};
