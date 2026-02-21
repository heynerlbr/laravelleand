<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\LoanInstallment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LoanController extends Controller
{
    public function calculate(Request $request)
    {
        $amount = $request->amount;
        $days = $request->days; 
        $includeFianza = $request->includeFianza;
        $includeFirma = $request->includeFirma;
        $installmentsCount = $request->installments ?? 1;

        // Interest calculation (24.1% EA)
        $interestRateEA = 0.241;
        $periodicInterest = ($amount * $interestRateEA) * ($days / 365);
        
        $fianza = $includeFianza ? ($amount * 0.1498) : 0;
        $firma = $includeFirma ? 36450 : 0;
        
        $subtotal = $periodicInterest + $fianza + $firma;
        $iva = $subtotal * 0.19;
        
        $total = $amount + $periodicInterest + $fianza + $firma + $iva;
        $installmentAmount = $total / $installmentsCount;

        // Generate schedule preview
        $schedule = [];
        $startDate = Carbon::now();
        for ($i = 1; $i <= $installmentsCount; $i++) {
            $schedule[] = [
                'number' => $i,
                'amount' => round($installmentAmount, 2),
                'due_date' => $startDate->copy()->addDays(($days / $installmentsCount) * $i)->format('Y-m-d')
            ];
        }

        return response()->json([
            'monto_solicitado' => $amount,
            'interes' => round($periodicInterest, 2),
            'fianza' => round($fianza, 2),
            'firma_electronica' => round($firma, 2),
            'iva' => round($iva, 2),
            'total_a_pagar' => round($total, 2),
            'cuotas' => $installmentsCount,
            'valor_cuota' => round($installmentAmount, 2),
            'schedule' => $schedule
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric',
            'payment_date' => 'required|date',
            'includeFianza' => 'boolean',
            'includeFirma' => 'boolean',
            'installments' => 'integer|min:1|max:12',
        ]);

        $amount = $validated['amount'];
        $installmentsCount = $validated['installments'] ?? 1;
        $paymentDate = new \DateTime($validated['payment_date']);
        $now = new \DateTime();
        $days = $now->diff($paymentDate)->days;
        if ($days < 7) $days = 7;

        $interestRateEA = 0.241;
        $periodicInterest = ($amount * $interestRateEA) * ($days / 365);
        $fianza = $request->includeFianza ? ($amount * 0.1498) : 0;
        $firma = $request->includeFirma ? 36450 : 0;
        $iva = ($periodicInterest + $fianza + $firma) * 0.19;
        $total = $amount + $periodicInterest + $fianza + $firma + $iva;
        $installmentAmount = $total / $installmentsCount;

        $loan = Loan::create([
            'user_id' => Auth::id(),
            'amount' => $amount,
            'payment_date' => $validated['payment_date'],
            'interest_rate' => 24.1,
            'interest_amount' => $periodicInterest,
            'fianza_amount' => $fianza,
            'firma_electronica_amount' => $firma,
            'iva_amount' => $iva,
            'total_to_pay' => $total,
            'installments_count' => $installmentsCount,
            'status' => 'pending'
        ]);

        // Create installments
        $startDate = Carbon::now();
        for ($i = 1; $i <= $installmentsCount; $i++) {
            LoanInstallment::create([
                'loan_id' => $loan->id,
                'installment_number' => $i,
                'amount' => $installmentAmount,
                'due_date' => $startDate->copy()->addDays(($days / $installmentsCount) * $i)
            ]);
        }

        return response()->json([
            'status' => 'ok',
            'msg' => 'Solicitud de préstamo con cuotas enviada con éxito',
            'loan' => $loan->load('installments')
        ]);
    }

    public function myLoans()
    {
        $loans = Loan::with('installments')->where('user_id', Auth::id())->orderBy('created_at', 'desc')->get();
        return response()->json([
            'status' => 'ok',
            'loans' => $loans
        ]);
    }

    public function stats()
    {
        $thisMonth = Carbon::now()->startOfMonth();
        $today = Carbon::now();
        
        // 1. Basic Volume Stats
        $creditsThisMonth = Loan::where('created_at', '>=', $thisMonth)->count();
        $totalAmountThisMonth = Loan::where('created_at', '>=', $thisMonth)->sum('amount');
        
        // 2. Profit Metrics (Interests + Fees)
        $totalInterestEarned = Loan::where('status', 'paid')->sum('interest_amount');
        $totalFeesEarned = Loan::where('status', 'paid')->sum(DB::raw('fianza_amount + firma_electronica_amount'));
        $totalProfit = $totalInterestEarned + $totalFeesEarned;

        // 3. Portfolio Health
        $activePortfolio = Loan::where('status', 'approved')->sum('amount');
        $totalExpectIncome = Loan::where('status', 'approved')->sum('total_to_pay');
        
        // 4. Overdue Analysis
        $overdueAmount = \App\Models\LoanInstallment::where('status', 'pending')
            ->where('due_date', '<', $today)
            ->sum('amount');

        // 5. Monthly Growth Logic (Comparison)
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();
        $creditsLastMonth = Loan::whereBetween('created_at', [$lastMonth, $lastMonthEnd])->count();
        $growth = $creditsLastMonth > 0 ? (($creditsThisMonth - $creditsLastMonth) / $creditsLastMonth) * 100 : 100;

        // 6. Detailed Overdue List (Lender needs to know WHO to call)
        $overdueLoans = \App\Models\Loan::with(['user', 'installments' => function($query) use ($today) {
                $query->where('status', 'pending')->where('due_date', '<', $today);
            }])
            ->whereHas('installments', function($query) use ($today) {
                $query->where('status', 'pending')->where('due_date', '<', $today);
            })
            ->get()
            ->map(function($loan) {
                return [
                    'id' => $loan->id,
                    'user_name' => $loan->user->name,
                    'user_email' => $loan->user->email,
                    'user_phone' => $loan->user->celular ?? $loan->user->identificacion, // Use celular or identification as fallback
                    'overdue_amount' => $loan->installments->sum('amount'),
                    'last_due_date' => $loan->installments->min('due_date'),
                    'status' => $loan->status
                ];
            });

        return response()->json([
            'status' => 'ok',
            'stats' => [
                'credits_this_month' => $creditsThisMonth,
                'total_amount_month' => round((float)$totalAmountThisMonth, 2),
                'active_portfolio' => round((float)$activePortfolio, 2),
                'total_profit' => round((float)$totalProfit, 2),
                'overdue_amount' => round((float)$overdueAmount, 2),
                'growth_percentage' => round($growth, 1),
                'expected_income' => round((float)$totalExpectIncome, 2),
                'total_users' => \App\Models\User::count(),
            ],
            'overdue_list' => $overdueLoans
        ]);
    }

    public function approve($id)
    {
        $loan = Loan::findOrFail($id);
        $loan->update(['status' => 'approved']);
        
        return response()->json([
            'status' => 'ok',
            'msg' => 'Préstamo aprobado con éxito',
            'loan' => $loan
        ]);
    }

    public function reject($id)
    {
        $loan = Loan::findOrFail($id);
        $loan->update(['status' => 'rejected']);
        
        return response()->json([
            'status' => 'ok',
            'msg' => 'Préstamo rechazado',
            'loan' => $loan
        ]);
    }

    public function pending()
    {
        $loans = Loan::with('user')->where('status', 'pending')->orderBy('created_at', 'desc')->get();
        return response()->json([
            'status' => 'ok',
            'loans' => $loans
        ]);
    }

    public function payInstallment($id)
    {
        $installment = LoanInstallment::where('loan_id', $id)
            ->where('status', 'pending')
            ->orderBy('installment_number', 'asc')
            ->first();

        if (!$installment) {
            return response()->json([
                'status' => 'error',
                'msg' => 'No hay cuotas pendientes para este préstamo'
            ], 400);
        }

        $installment->status = 'paid';
        $installment->save();

        // Check if all installments are paid
        $remaining = LoanInstallment::where('loan_id', $id)
            ->where('status', 'pending')
            ->count();

        if ($remaining === 0) {
            $loan = Loan::find($id);
            $loan->status = 'paid';
            $loan->save();
        }

        return response()->json([
            'status' => 'ok',
            'msg' => 'Cuota pagada con éxito',
            'installment' => $installment
        ]);
    }

    public function payFull($id)
    {
        $loan = Loan::findOrFail($id);
        
        LoanInstallment::where('loan_id', $id)
            ->where('status', 'pending')
            ->update(['status' => 'paid']);

        $loan->status = 'paid';
        $loan->save();

        return response()->json([
            'status' => 'ok',
            'msg' => 'Préstamo liquidado por completo',
            'loan' => $loan
        ]);
    }

    public function allLoans(Request $request)
    {
        $query = Loan::with(['user', 'installments'])->orderBy('created_at', 'desc');

        // Filtro por estado
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filtro por nombre o email del usuario
        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $loans = $query->get();

        return response()->json([
            'status' => 'ok',
            'loans' => $loans
        ]);
    }

    public function show($id)
    {
        $loan = Loan::with(['user', 'installments'])->findOrFail($id);
        return response()->json([
            'status' => 'ok',
            'loan' => $loan
        ]);
    }
}
