<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Product;
use App\Models\BranchProduct;
use App\Models\InventoryMovement;
use App\Services\InventoryService;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Exception;

class InventoryController extends Controller
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

        $filters = $request->only(['search', 'branch_id', 'low_stock']);

        $query = BranchProduct::with(['product.category', 'branch'])
            ->whereHas('product', function ($q) use ($filters) {
                if (!empty($filters['search'])) {
                    $q->where('name', 'like', "%{$filters['search']}%");
                }
            });

        if ($isAdmin) {
            if (!empty($filters['branch_id'])) {
                $query->where('branch_id', $filters['branch_id']);
            }
        } else {
            $query->where('branch_id', $user->branch_id);
        }

        if (!empty($filters['low_stock']) && filter_var($filters['low_stock'], FILTER_VALIDATE_BOOLEAN)) {
            $query->whereColumn('current_stock', '<=', 'min_stock');
        }

        $stocks = $query->paginate(15)->withQueryString();

        // Calculate summary stats
        $summaryQuery = BranchProduct::query();
        if (!$isAdmin) {
            $summaryQuery->where('branch_id', $user->branch_id);
        }

        $totalWithStock = (clone $summaryQuery)->where('current_stock', '>', 0)->count();
        $totalLowStock = (clone $summaryQuery)->whereColumn('current_stock', '<=', 'min_stock')->where('current_stock', '>', 0)->count();
        $totalNoStock = (clone $summaryQuery)->where('current_stock', '<=', 0)->count();

        return Inertia::render('Inventory/Index', [
            'stocks'   => $stocks,
            'products' => Product::where('has_inventory', true)->orderBy('name')->get(['id', 'name']),
            'branches' => $isAdmin ? Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']) : [],
            'filters'  => $filters,
            'summary'  => [
                'withStock' => $totalWithStock,
                'lowStock'  => $totalLowStock,
                'noStock'   => $totalNoStock,
            ],
        ]);
    }

    public function movements(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user->role->name === 'admin';

        $filters = $request->only(['product_id', 'branch_id', 'movement_type', 'date_from', 'date_to']);

        $query = InventoryMovement::with(['product', 'branch', 'user']);

        if (!$isAdmin) {
            $query->where('branch_id', $user->branch_id);
        } else if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (!empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (!empty($filters['movement_type'])) {
            $query->where('movement_type', $filters['movement_type']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $movements = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('Inventory/Movements', [
            'movements' => $movements,
            'products'  => Product::where('has_inventory', true)->orderBy('name')->get(['id', 'name']),
            'branches'  => $isAdmin ? Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']) : [],
            'filters'   => $filters,
        ]);
    }

    public function addStock(Request $request)
    {
        $validated = $request->validate([
            'branch_id'      => 'required|exists:branches,id',
            'product_id'     => 'required|exists:products,id',
            'quantity'       => 'required|numeric|min:0.01',
            'purchase_price' => 'nullable|numeric|min:0',
            'notes'          => 'nullable|string|max:500',
        ]);

        try {
            $this->inventoryService->addStock(
                $validated['branch_id'],
                $validated['product_id'],
                $validated['quantity'],
                $validated['purchase_price'],
                Auth::id(),
                $validated['notes']
            );

            $product = Product::find($validated['product_id']);

            ActivityLog::create([
                'user_id'     => Auth::id(),
                'branch_id'   => Auth::user()->branch_id,
                'action'      => 'inventory.stock_added',
                'model_type'  => 'InventoryMovement',
                'model_id'    => null, // Can't easily get the movement ID without returning it from service, it's fine
                'description' => "Ingresados {$validated['quantity']} al stock de '{$product->name}'.",
                'ip_address'  => $request->ip(),
            ]);

            return back()->with('success', 'Stock agregado correctamente.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function adjustStock(Request $request)
    {
        $validated = $request->validate([
            'branch_id'  => 'required|exists:branches,id',
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|numeric',
            'notes'      => 'required|string|max:500',
        ]);

        try {
            $this->inventoryService->adjustStock(
                $validated['branch_id'],
                $validated['product_id'],
                $validated['quantity'],
                Auth::id(),
                $validated['notes']
            );

            $product = Product::find($validated['product_id']);

            ActivityLog::create([
                'user_id'     => Auth::id(),
                'branch_id'   => Auth::user()->branch_id,
                'action'      => 'inventory.adjusted',
                'model_type'  => 'InventoryMovement',
                'model_id'    => null,
                'description' => "Ajuste de stock ({$validated['quantity']}) en '{$product->name}'.",
                'ip_address'  => $request->ip(),
            ]);

            return back()->with('success', 'Stock ajustado correctamente.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function updateMinStock(Request $request, $id)
    {
        $validated = $request->validate([
            'min_stock' => 'required|numeric|min:0',
        ]);

        $branchProduct = BranchProduct::findOrFail($id);
        
        // Ensure user can only update their own branch's min stock, or is admin
        $user = Auth::user();
        if ($user->role->name !== 'admin' && $branchProduct->branch_id !== $user->branch_id) {
            abort(403, 'Unauthorized action.');
        }

        $branchProduct->update(['min_stock' => $validated['min_stock']]);

        return back()->with('success', 'Stock mínimo actualizado.');
    }
}
