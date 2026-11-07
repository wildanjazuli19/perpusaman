<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookReturn extends Model
{
    protected $fillable = [
        'return_code', 'loan_id', 'quantity', 'book_condition', 'notes', 'returned_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'returned_at' => 'datetime',
        ];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }
}
