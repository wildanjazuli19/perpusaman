<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExperimentRun extends Model
{
    protected $fillable = [
        'metrics',
        'data_size_bytes',
    ];

    protected function casts(): array
    {
        return [
            'metrics' => 'array',
        ];
    }
}
