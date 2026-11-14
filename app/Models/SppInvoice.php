<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SppInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'period_month',
        'amount',
        'amount_paid',
        'due_date',
    ];

    protected function casts(): array
    {
        return [
            'period_month' => 'date',
            'due_date' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(SchoolTransaction::class, 'invoice_id');
    }
}
