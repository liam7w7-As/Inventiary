<?php

namespace App\Http\Controllers;

use App\Models\StockTransfer;
use App\Models\Branch;
use App\Models\Product;
use App\Models\ActivityLog;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class StockTransferController extends Controller
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user->role->name === 'admin';

        $filters = $request->only(['branch_id', 'status', 'product_id', 'date_from', 'date_to']);

        $query = StockTransfer::with(['fromBranch', 'toBranch', 'product', 'requester', 'approver']);

        if (!$isAdmin) {
            $query->where(function ($q) use ($user) {
                $q->where('from_branch_id', $user->branch_id)
                  ->orWhere('to_branch_id', $user->branch_id);
            });
        } else {
            $query->when($filters['branch_id'] ?? null, function ($q, $v) {
                $q->where(function ($sq) use ($v) {
                    $sq->where('from_branch_id', $v)
                       ->orWhere('to_branch_id', $v);
                });
            });
        }

        $query->when($filters['status'] ?? null, fn($q, $v) => $q->where('status', $v))
            ->when($filters['product_id'] ?? null, fn($q, $v) => $q->where('product_id', $v))
            ->when($filters['date_from'] ?? null, fn($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn($q, $v) => $q->whereDate('created_at', '<=', $v));

        $transfers = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Transfers/Index', [
            'transfers' => $transfers,
            'branches' => Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'products' => Product::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'filters' => $filters,
            'pendingCount' => StockTransfer::where('status', 'pending')->count(),
        ]);
    }
//:3
    public function create()
    {
        $user = Auth::user();
        $isAdmin = $user->role->name === 'admin';

        return Inertia::render('Transfers/Create', [
            'branches' => Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'products' => Product::where('is_active', true)->where('has_inventory', true)->with('branchProducts')->orderBy('name')->get(),
            'defaultToBranch' => !$isAdmin ? $user->branch_id : null,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user->role->name === 'admin';

        $validated = $request->validate([
            'from_branch_id' => 'required|exists:branches,id',
            'to_branch_id' => 'required|exists:branches,id|different:from_branch_id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:500',
        ]);

        if (!$isAdmin && $validated['to_branch_id'] != $user->branch_id) {
            abort(403, 'Solo puedes solicitar transferencias hacia tu propia sucursal.');
        }

        // Check if origin branch has enough stock
        $product = Product::findOrFail($validated['product_id']);
        if (!$product->has_inventory) {
            return back()->with('error', 'El producto seleccionado no maneja inventario.');
        }

        $branchProduct = $product->branchProducts()->where('branch_id', $validated['from_branch_id'])->first();
        $currentStock = $branchProduct ? $branchProduct->current_stock : 0;

        if ($validated['quantity'] > $currentStock) {
            return back()->with('error', "Stock insuficiente en la sucursal origen. Stock actual: {$currentStock}");
        }

        try {
            DB::transaction(function () use ($validated, $user, $request) {
                $transfer = StockTransfer::create([
                    'from_branch_id' => $validated['from_branch_id'],
                    'to_branch_id' => $validated['to_branch_id'],
                    'product_id' => $validated['product_id'],
                    'requested_by' => $user->id,
                    'quantity' => $validated['quantity'],
                    'status' => 'pending',
                    'notes' => $validated['notes'],
                ]);

                ActivityLog::create([
                    'user_id' => $user->id,
                    'branch_id' => $user->branch_id,
                    'action' => 'transfer.requested',
                    'model_type' => 'StockTransfer',
                    'model_id' => $transfer->id,
                    'description' => "Solicitud de transferencia de {$validated['quantity']} unidades creada.",
                    'ip_address' => $request->ip(),
                ]);
            });

            return redirect()->route('transfers.index')->with('success', 'Transferencia solicitada correctamente. Pendiente de aprobación.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al solicitar transferencia: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $user = Auth::user();
        $isAdmin = $user->role->name === 'admin';

        $transfer = StockTransfer::with(['fromBranch', 'toBranch', 'product', 'requester', 'approver'])->findOrFail($id);

        if (!$isAdmin && !in_array($user->branch_id, [$transfer->from_branch_id, $transfer->to_branch_id])) {
            abort(403, 'No tienes permiso para ver esta transferencia.');
        }

        // Load inventory movements related to this transfer if completed
        $movements = [];
        if ($transfer->status === 'completed') {
            $movements = \App\Models\InventoryMovement::where('reference_type', 'stock_transfer')
                ->where('reference_id', $transfer->id)
                ->with(['branch', 'user'])
                ->get();
        }

        return Inertia::render('Transfers/Show', [
            'transfer' => $transfer,
            'movements' => $movements,
        ]);
    }

    public function approve(Request $request, $id)
    {
        $user = Auth::user();
        if ($user->role->name !== 'admin') {
            abort(403, 'Solo administradores pueden aprobar transferencias.');
        }

        $transfer = StockTransfer::findOrFail($id);

        if ($transfer->status !== 'pending') {
            return back()->with('error', 'La transferencia ya no está pendiente.');
        }

        // Verify stock again before moving it
        $branchProduct = \App\Models\BranchProduct::where('product_id', $transfer->product_id)
            ->where('branch_id', $transfer->from_branch_id)
            ->first();
            
        $currentStock = $branchProduct ? $branchProduct->current_stock : 0;

        if ($transfer->quantity > $currentStock) {
            return back()->with('error', "No se puede aprobar. Stock insuficiente en la sucursal origen ahora mismo. Stock actual: {$currentStock}");
        }

        try {
            DB::transaction(function () use ($transfer, $user, $request) {
                // 1. Remove stock from origin
                $this->inventoryService->removeStock(
                    $transfer->product_id,
                    $transfer->from_branch_id,
                    $transfer->quantity,
                    'transfer_out',
                    $user->id,
                    $transfer->notes,
                    $transfer->id,
                    'stock_transfer'
                );

                // 2. Add stock to destination
                $this->inventoryService->addStock(
                    $transfer->product_id,
                    $transfer->to_branch_id,
                    $transfer->quantity,
                    'transfer_in',
                    $user->id,
                    $transfer->notes,
                    $transfer->id,
                    'stock_transfer'
                );

                // 3. Update transfer status
                $transfer->update([
                    'status' => 'completed',
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                    'completed_at' => now(),
                ]);

                ActivityLog::create([
                    'user_id' => $user->id,
                    'branch_id' => $user->branch_id,
                    'action' => 'transfer.approved_and_completed',
                    'model_type' => 'StockTransfer',
                    'model_id' => $transfer->id,
                    'description' => "Transferencia aprobada y completada. Stock movido con éxito.",
                    'ip_address' => $request->ip(),
                ]);
            });

            return back()->with('success', 'Transferencia aprobada y stock movido correctamente.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al procesar transferencia: ' . $e->getMessage());
        }
    }

    public function reject(Request $request, $id)
    {
        $user = Auth::user();
        if ($user->role->name !== 'admin') {
            abort(403, 'Solo administradores pueden rechazar transferencias.');
        }

        $transfer = StockTransfer::findOrFail($id);

        if ($transfer->status !== 'pending') {
            return back()->with('error', 'La transferencia ya no está pendiente.');
        }

        $validated = $request->validate([
            'notes' => 'required|string|max:500',
        ]);

        try {
            DB::transaction(function () use ($transfer, $user, $validated, $request) {
                $transfer->update([
                    'status' => 'rejected',
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                    'notes' => $validated['notes'],
                ]);

                ActivityLog::create([
                    'user_id' => $user->id,
                    'branch_id' => $user->branch_id,
                    'action' => 'transfer.rejected',
                    'model_type' => 'StockTransfer',
                    'model_id' => $transfer->id,
                    'description' => "Transferencia rechazada. Motivo: {$validated['notes']}",
                    'ip_address' => $request->ip(),
                ]);
            });

            return back()->with('success', 'Transferencia rechazada correctamente.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al rechazar transferencia: ' . $e->getMessage());
        }
    }
}
