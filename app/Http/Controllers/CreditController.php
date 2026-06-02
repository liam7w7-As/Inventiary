<?php

namespace App\Http\Controllers;

use App\Models\Credit;
use App\Models\CreditPayment;
use App\Models\Branch;
use App\Models\Client;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CreditController extends Controller
{
    private function markOverdue()
    {
        Credit::where('status', 'active')
            ->whereDate('due_date', '<', today())
            ->update(['status' => 'overdue']);
    }

    public function index(Request $request)
    {
        $this->markOverdue();

        $user = Auth::user();
        $isAdmin = $user->role->name === 'admin';

        $filters = $request->only(['branch_id', 'client_id', 'status', 'is_overdue', 'date_from', 'date_to']);

        $query = Credit::with(['client', 'branch', 'sale']);

        if (!$isAdmin) {
            $query->where('branch_id', $user->branch_id);
        } else {
            $query->when($filters['branch_id'] ?? null, fn($q, $v) => $q->where('branch_id', $v));
        }

        $query->when($filters['client_id'] ?? null, fn($q, $v) => $q->where('client_id', $v))
            ->when($filters['status'] ?? null, fn($q, $v) => $q->where('status', $v))
            ->when(isset($filters['is_overdue']) && $filters['is_overdue'] === 'true', fn($q) => $q->where('status', 'overdue'))
            ->when($filters['date_from'] ?? null, fn($q, $v) => $q->whereDate('due_date', '>=', $v))
            ->when($filters['date_to'] ?? null, fn($q, $v) => $q->whereDate('due_date', '<=', $v));

        $credits = $query->latest()->paginate(15)->withQueryString();

        // Calculate summaries
        $summaryQuery = clone $query;
        $allCredits = $summaryQuery->get();
        $summary = [
            'total_active' => $allCredits->where('status', 'active')->sum('balance'),
            'count_active' => $allCredits->where('status', 'active')->count(),
            'total_overdue' => $allCredits->where('status', 'overdue')->sum('balance'),
            'count_overdue' => $allCredits->where('status', 'overdue')->count(),
            'total_paid' => $allCredits->sum('paid_amount'), // Historical total paid
        ];

        return Inertia::render('Credits/Index', [
            'credits' => $credits,
            'branches' => $isAdmin ? Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']) : [],
            'clients' => Client::when(!$isAdmin, fn($q) => $q->where('branch_id', $user->branch_id))->orderBy('name')->get(['id', 'name']),
            'filters' => $filters,
            'summary' => $summary,
        ]);
    }

    public function show($id)
    {
        $this->markOverdue();
        $user = Auth::user();
        $isAdmin = $user->role->name === 'admin';

        $credit = Credit::with(['client', 'branch', 'sale', 'payments.user'])->findOrFail($id);

        if (!$isAdmin && $credit->branch_id !== $user->branch_id) {
            abort(403, 'No tienes permiso para ver este crédito.');
        }

        return Inertia::render('Credits/Show', [
            'credit' => $credit,
            'payments' => $credit->payments()->latest()->get(),
            'sale' => $credit->sale,
        ]);
    }

    public function addPayment(Request $request, $id)
    {
        $user = Auth::user();
        $isAdmin = $user->role->name === 'admin';

        $credit = Credit::findOrFail($id);

        if (!$isAdmin && $credit->branch_id !== $user->branch_id) {
            abort(403, 'No tienes permiso para registrar pagos en este crédito.');
        }

        if ($credit->status === 'paid') {
            return back()->with('error', 'El crédito ya está pagado totalmente.');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validated['amount'] > $credit->balance) {
            return back()->with('error', "El monto supera el saldo pendiente de Bs. " . number_format($credit->balance, 2));
        }

        try {
            DB::transaction(function () use ($validated, $credit, $user, $request) {
                CreditPayment::create([
                    'credit_id' => $credit->id,
                    'user_id' => $user->id,
                    'amount' => $validated['amount'],
                    'payment_date' => $validated['payment_date'],
                    'notes' => $validated['notes'],
                ]);

                $credit->paid_amount += $validated['amount'];
                $credit->balance -= $validated['amount'];

                if ($credit->balance <= 0) {
                    $credit->status = 'paid';
                }

                $credit->save();

                ActivityLog::create([
                    'user_id' => $user->id,
                    'branch_id' => $user->branch_id,
                    'action' => 'credit.payment_added',
                    'model_type' => 'Credit',
                    'model_id' => $credit->id,
                    'description' => "Pago de Bs. {$validated['amount']} registrado al crédito #{$credit->id}",
                    'ip_address' => $request->ip(),
                ]);
            });

            return back()->with('success', 'Pago registrado correctamente.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al registrar el pago: ' . $e->getMessage());
        }
    }

    public function updateDueDate(Request $request, $id)
    {
        $user = Auth::user();
        if ($user->role->name !== 'admin') {
            abort(403, 'Solo el administrador puede cambiar fechas de vencimiento.');
        }

        $credit = Credit::findOrFail($id);

        if ($credit->status === 'paid') {
            return back()->with('error', 'No se puede modificar la fecha de un crédito pagado.');
        }

        $validated = $request->validate([
            'due_date' => 'required|date|after:today',
            'notes' => 'nullable|string|max:255',
        ]);

        try {
            DB::transaction(function () use ($validated, $credit, $user, $request) {
                $oldDate = $credit->due_date;
                $credit->due_date = $validated['due_date'];
                
                if ($credit->status === 'overdue' && $credit->due_date >= today()) {
                    $credit->status = 'active';
                }

                $credit->save();

                ActivityLog::create([
                    'user_id' => $user->id,
                    'branch_id' => $user->branch_id,
                    'action' => 'credit.due_date_updated',
                    'model_type' => 'Credit',
                    'model_id' => $credit->id,
                    'description' => "Fecha de vencimiento cambiada de {$oldDate} a {$validated['due_date']}. Notas: " . ($validated['notes'] ?? ''),
                    'ip_address' => $request->ip(),
                ]);
            });

            return back()->with('success', 'Fecha de vencimiento actualizada correctamente.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al actualizar: ' . $e->getMessage());
        }
    }
}
