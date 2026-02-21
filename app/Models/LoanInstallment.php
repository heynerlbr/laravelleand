<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanInstallment extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id',
        'installment_number',
        'amount',
        'due_date',
        'status'
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }
}
