<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Book extends Model
{
    protected $fillable = [
        'category_id', 'isbn', 'title', 'author', 'publisher',
        'publication_year', 'stock_total', 'stock_available',
        'shelf_location', 'description',
    ];

    protected function casts(): array
    {
        return [
            'publication_year' => 'integer',
            'stock_total' => 'integer',
            'stock_available' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
