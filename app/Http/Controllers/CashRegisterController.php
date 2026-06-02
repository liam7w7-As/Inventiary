<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\Sale;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CashRegisterController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user->role->name === 'admin';

        $filters = $request->only(['user_id', 'branch_id', 'status', 'date_from', 'date_to']);

        $query = CashRegister::with(['user', 'branch', 'opener'])
            ->when($filters['user_id'] ?? null, fn($q, $v) => $q->where('user_id', $v))
            ->when($filters['status'] ?? null, fn($q, $v) => $q->where('status', $v))
            ->when($filters['date_from'] ?? null, fn($q, $v) => $q->whereDate('opened_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn($q, $v) => $q->whereDate('opened_at', '<=', $v));

        if ($isAdmin) {
            $query->when($filters['branch_id'] ?? null, fn($q, $v) => $q->where('branch_id', $v));
        } else {
            $query->where('branch_id', $user->branch_id);
        }

        $cashRegisters = $query->orderByDesc('opened_at')->paginate(15)->withQueryString();

        // Summary: open registers today
        $todayQuery = CashRegister::whereDate('opened_at', today());
        if (!$isAdmin) $todayQuery->where('branch_id', $user->branch_id);
        $openToday = (clone $todayQuery)->where('status', 'open')->count();
        $totalInitialToday = (clone $todayQuery)->sum('initial_balance');

        return Inertia::render('CashRegisters/Index', [
            'cashRegisters' => $cashRegisters,
            'users'         => $isAdmin ? User::whereHas('role', fn($q) => $q->where('name', 'encargado'))->where('is_active', true)->orderBy('name')->get(['id', 'name']) : [],
            'branches'      => $isAdmin ? Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']) : [],
            'filters'       => $filters,
            'summary'       => [
                'openToday'         => $openToday,
                'totalInitialToday' => $totalInitialToday,
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('CashRegisters/Create', [
            'branches' => Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'users'    => User::whereHas('role', fn($q) => $q->where('name', 'encargado'))
                ->where('is_active', true)
                ->with('branch:id,name')
                ->orderBy('name')
                ->get(['id', 'name', 'branch_id']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_id'       => 'required|exists:branches,id',
            'user_id'         => 'required|exists:users,id',
            'initial_balance' => 'required|numeric|min:0',
            'sale_limit'      => 'nullable|numeric|min:0',
            'notes'           => 'nullable|string|max:500',
        ]);

        // Verify user is an encargado for that branch
        $targetUser = User::with('role')->find($validated['user_id']);
        if (!$targetUser || $targetUser->role->name !== 'encargado') {
            return back()->withErrors(['user_id' => 'El usuario debe ser un encargado.']);
        }
        if ($targetUser->branch_id != $validated['branch_id']) {
            return back()->withErrors(['user_id' => 'El encargado no pertenece a la sucursal seleccionada.']);
        }

        // Check if user already has an open register
        $hasOpen = CashRegister::where('user_id', $validated['user_id'])
            ->where('status', 'open')
            ->exists();

        if ($hasOpen) {
            return back()->withErrors(['user_id' => 'Este encargado ya tiene una caja abierta.']);
        }

        $cashRegister = CashRegister::create([
            ...$validated,
            'opened_by' => Auth::id(),
            'opened_at' => now(),
            'status'    => 'open',
        ]);

        ActivityLog::create([
            'user_id'     => Auth::id(),
            'branch_id'   => Auth::user()->branch_id,
            'action'      => 'cash_register.opened',
            'model_type'  => 'CashRegister',
            'model_id'    => $cashRegister->id,
            'description' => "Caja abierta para '{$targetUser->name}' con saldo Bs. {$validated['initial_balance']}.",
            'ip_address'  => $request->ip(),
        ]);

        return redirect('/cash-registers')->with('success', "Caja abierta para '{$targetUser->name}' correctamente.");
    }

    public function show($id)
    {
        $user = Auth::user();
        $isAdmin = $user->role->name === 'admin';

        $cashRegister = CashRegister::with(['user', 'branch', 'opener'])->findOrFail($id);

        // Encargado can only see their own registers
        if (!$isAdmin && $cashRegister->user_id !== $user->id) {
            abort(403, 'No tienes permiso para ver esta caja.');
        }

        $sales = Sale::with(['client', 'user'])
            ->where('cash_register_id', $id)
            ->orderByDesc('created_at')
            ->get();

        $totals = [
            'totalSales'  => $sales->where('status', 'completed')->sum('total'),
            'totalCount'  => $sales->where('status', 'completed')->count(),
            'cancelled'   => $sales->where('status', 'cancelled')->count(),
        ];

        return Inertia::render('CashRegisters/Show', [
            'cashRegister' => $cashRegister,
            'sales'        => $sales,
            'totals'       => $totals,
        ]);
    }

    public function getActive(Request $request)
    {
        $user = Auth::user();

        $cashRegister = CashRegister::where('status', 'open')
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('branch_id', $user->branch_id);
            })
            ->with('branch')
            ->first();

        return response()->json(['cashRegister' => $cashRegister]);
    }

    /**
     * Returns encargados for a given branch (used in Create form reactivity).
     */
    public function usersByBranch($branchId)
    {
        $users = User::whereHas('role', fn($q) => $q->where('name', 'encargado'))
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->get(['id', 'name']);

        // Also check which ones already have open registers
        $openRegisterUserIds = CashRegister::where('branch_id', $branchId)
            ->where('status', 'open')
            ->pluck('user_id')
            ->toArray();

        $users->each(function ($user) use ($openRegisterUserIds) {
            $user->has_open_register = in_array($user->id, $openRegisterUserIds);
        });

        return response()->json($users);
    }
}
