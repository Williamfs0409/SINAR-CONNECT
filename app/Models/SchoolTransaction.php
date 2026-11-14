<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'invoice_id',
        'category',
        'direction',
        'method',
        'amount',
        'transaction_date',
        'notes',
    ];

    protected function casts(): array
    {
        return ['transaction_date' => 'date'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SppInvoice::class, 'invoice_id');
    }
}
