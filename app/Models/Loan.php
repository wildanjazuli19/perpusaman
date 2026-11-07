<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Loan extends Model
{
    protected $fillable = [
        'loan_code', 'member_id', 'book_id', 'quantity',
        'loan_date', 'due_date', 'returned_at', 'status', 'notes',
        'quantity_returned',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'loan_date' => 'date',
            'due_date' => 'date',
            'returned_at' => 'datetime',
            'quantity_returned' => 'integer',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
