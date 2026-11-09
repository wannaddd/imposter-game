
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('word_pairs', function (Blueprint $table) {
            $table->id();
            $table->string('language', 2);
            $table->string('category', 50);
            $table->string('civilian_word');
            $table->string('imposter_word');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['language', 'category', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('word_pairs');
    }
};
