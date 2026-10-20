<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    protected $fillable = [
        'hybrid_payload',
        'baseline_payload',
        'metrics',
    ];

    protected function casts(): array
    {
        return [
            'hybrid_payload' => 'array',
            'baseline_payload' => 'array',
            'metrics' => 'array',
        ];
    }
}
