<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WordPair extends Model
{
    protected $fillable = [
        'language',
        'category',
        'civilian_word',
        'imposter_word',
        'imposter_hint',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}