<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('date_night_themes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('emoji');
            $table->text('description');
            $table->string('colour', 7)->default('#EC4899');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index('active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('date_night_themes');
    }
};
