<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'registration_number',
        'name',
        'guardian_name',
        'guardian_phone',
        'class_group',
        'status',
    ];

    public function invoices(): HasMany
    {
        return $this->hasMany(SppInvoice::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(SchoolTransaction::class);
    }
}
