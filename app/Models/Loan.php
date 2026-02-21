<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amount',
        'payment_date',
        'interest_rate',
        'interest_amount',
        'fianza_amount',
        'firma_electronica_amount',
        'iva_amount',
        'total_to_pay',
        'installments_count',
        'installment_frequency',
        'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function installments()
    {
        return $this->hasMany(LoanInstallment::class);
    }
}
