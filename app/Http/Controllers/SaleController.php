<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Credit;
use App\Models\DeliveryNote;
use App\Models\CashRegister;
use App\Models\PreSale;
use App\Models\Product;
use App\Models\Client;
use App\Models\Branch;
use App\Models\ActivityLog;
use App\Models\SystemSetting;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SaleController extends Controller
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

        $filters = $request->only(['branch_id', 'client_id', 'payment_type', 'status', 'date_from', 'date_to', 'search']);

        $query = Sale::with(['branch', 'client', 'user'])
            ->withCount('items')
            ->when($filters['client_id'] ?? null, fn($q, $v) => $q->where('client_id', $v))
            ->when($filters['payment_type'] ?? null, fn($q, $v) => $q->where('payment_type', $v))
            ->when($filters['status'] ?? null, fn($q, $v) => $q->where('status', $v))
            ->when($filters['date_from'] ?? null, fn($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->when($filters['search'] ?? null, fn($q, $v) => $q->where('code', 'like', "%{$v}%"));

        if ($isAdmin) {
            $query->when($filters['branch_id'] ?? null, fn($q, $v) => $q->where('branch_id', $v));
        } else {
            $query->where('branch_id', $user->branch_id);
        }

        $sales = $query->latest()->paginate(20)->withQueryString();

        // Calculate totals based on filters
        $totalsQuery = clone $query;
        $allSales = $totalsQuery->get();
        
        $totals = [
            'total' => $allSales->where('status', 'active')->sum('total'),
            'cash' => $allSales->where('status', 'active')->where('payment_type', 'cash')->sum('total'),
            'credit' => $allSales->where('status', 'active')->where('payment_type', 'credit')->sum('total'),
            'cancelled' => $allSales->where('status', 'cancelled')->sum('total'),
        ];

        return Inertia::render('Sales/Index', [
            'sales' => $sales,
            'branches' => $isAdmin ? Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']) : [],
            'filters' => $filters,
            'totals' => $totals,
        ]);
    }

    public function create(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user->role->name === 'admin';

        // Check active cash register
        $branchId = $isAdmin ? $request->branch_id : $user->branch_id;
        
        if ($isAdmin && !$branchId) {
            // For admin, if no branch is selected, they should select one first, but usually the UI handles it.
            // Let's get branches for them to choose or default to first if none.
            $branches = Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']);
            if ($branches->isEmpty()) {
                return redirect('/dashboard')->with('error', 'No hay sucursales activas.');
            }
            if (!$branchId) {
                $branchId = $branches->first()->id;
            }
        } else {
            $branches = [];
        }

        $cashRegister = CashRegister::where('branch_id', $branchId)
            ->where('status', 'open')
            ->where(function ($q) use ($user, $isAdmin) {
                if (!$isAdmin) {
                    $q->where('user_id', $user->id);
                }
            })
            ->first();

        if (!$cashRegister) {
            return redirect('/cash-registers')->with('error', 'Debe abrir una caja antes de registrar ventas.');
        }

        $productsQuery = Product::where('is_active', true)
            ->with(['category', 'branchProducts' => function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            }])->orderBy('name');
            
        $products = $productsQuery->get()->map(function ($product) use ($branchId) {
            $branchProduct = $product->branchProducts->first();
            $product->current_stock = $branchProduct ? $branchProduct->current_stock : 0;
            return $product;
        });

        $clients = Client::where('branch_id', $branchId)->orderBy('name')->get(['id', 'name', 'phone']);

        $preSale = null;
        if ($request->presale_id) {
            $preSale = PreSale::with(['items.product', 'client'])->find($request->presale_id);
            if ($preSale && $preSale->status !== 'approved') {
                return redirect('/sales/create')->with('error', 'La preventa seleccionada no está aprobada.');
            }
        }

        $approvedPreSales = PreSale::with('client')
            ->where('branch_id', $branchId)
            ->where('status', 'approved')
            ->orderBy('updated_at', 'desc')
            ->get(['id', 'client_id', 'total', 'payment_type']);

        return Inertia::render('Sales/Create', [
            'products' => $products,
            'clients' => $clients,
            'cashRegister' => $cashRegister,
            'preSale' => $preSale,
            'branch' => Branch::find($branchId),
            'branches' => $isAdmin ? Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']) : [],
            'approvedPreSales' => $approvedPreSales,
            'selectedBranchId' => $branchId,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user->role->name === 'admin';

        $preSale = null;
        if ($request->filled('pre_sale_id')) {
            $preSale = \App\Models\PreSale::find($request->pre_sale_id);
            if ($preSale && $preSale->payment_type === 'credit') {
                $request->merge(['payment_type' => 'credit']);
            }
        }

        if (!$request->filled('pre_sale_id') || ($preSale && $preSale->payment_type !== 'credit')) {
            if ($request->payment_type === 'credit') {
                return back()->withErrors(['payment_type' => 'No se puede realizar una venta a crédito sin una preventa aprobada.']);
            }
        }

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'cash_register_id' => 'required|exists:cash_registers,id',
            'client_id' => 'required_if:payment_type,credit|nullable|exists:clients,id',
            'pre_sale_id' => 'nullable|exists:pre_sales,id',
            'sale_type' => 'required|in:direct,presale',
            'payment_type' => 'required|in:cash,credit,transfer,other',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.sale_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0|max:100',
            'items.*.purchase_price' => 'required|numeric|min:0',
        ]);

        if (!$isAdmin) {
            $validated['branch_id'] = $user->branch_id;
        }

        $cashRegister = CashRegister::find($validated['cash_register_id']);
        if (!$cashRegister || $cashRegister->status !== 'open') {
            return back()->with('error', 'La caja seleccionada no está abierta.');
        }

        try {
            $sale = DB::transaction(function () use ($validated, $user, $request) {
                // 1. Verificar stock
                foreach ($validated['items'] as $item) {
                    $product = Product::find($item['product_id']);
                    if ($product->has_inventory) {
                        if (!$this->inventoryService->hasStock($validated['branch_id'], $product->id, $item['quantity'])) {
                            throw new \Exception("Stock insuficiente para: {$product->name}");
                        }
                    }
                }

                // 2. Generar código
                $lastSale = Sale::orderBy('id', 'desc')->first();
                $nextId = $lastSale ? $lastSale->id + 1 : 1;
                $code = 'V-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);

                // 3. Calcular totales
                $subtotal = 0;
                $discount = 0;
                $total = 0;

                foreach ($validated['items'] as $item) {
                    $itemDiscount = $item['discount'] ?? 0;
                    $itemSub = $item['quantity'] * $item['sale_price'];
                    $itemDiscAmount = $itemSub * ($itemDiscount / 100);
                    
                    $subtotal += $itemSub;
                    $discount += $itemDiscAmount;
                    $total += ($itemSub - $itemDiscAmount);
                }

                // 4. Crear sale
                $sale = Sale::create([
                    'code' => $code,
                    'branch_id' => $validated['branch_id'],
                    'cash_register_id' => $validated['cash_register_id'],
                    'client_id' => $validated['client_id'],
                    'user_id' => $user->id,
                    'pre_sale_id' => $validated['pre_sale_id'],
                    'sale_type' => $validated['sale_type'],
                    'payment_type' => $validated['payment_type'],
                    'subtotal' => $subtotal,
                    'discount' => $discount,
                    'total' => $total,
                    'status' => 'active',
                    'notes' => $validated['notes'],
                ]);

                // 5. Crear sale_items y 6. Descontar stock
                foreach ($validated['items'] as $item) {
                    $itemDiscount = $item['discount'] ?? 0;
                    $itemSub = $item['quantity'] * $item['sale_price'] * (1 - $itemDiscount / 100);

                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'purchase_price' => $item['purchase_price'],
                        'sale_price' => $item['sale_price'],
                        'discount' => $itemDiscount,
                        'subtotal' => $itemSub,
                    ]);

                    $product = Product::find($item['product_id']);
                    if ($product->has_inventory) {
                        $this->inventoryService->removeStock(
                            $validated['branch_id'],
                            $product->id,
                            $item['quantity'],
                            $user->id,
                            "Venta {$code}",
                            $sale->id,
                            'sale'
                        );
                    }
                }

                // 7. Si crédito
                if ($validated['payment_type'] === 'credit' && isset($preSale)) {
                    $creditService = app(\App\Services\CreditService::class);
                    $creditService->createFromSale($sale, $preSale);
                }

                // 8. Actualizar preventa
                if ($validated['pre_sale_id']) {
                    PreSale::where('id', $validated['pre_sale_id'])->update([
                        'status' => 'converted',
                        'updated_at' => now(), // converted_at no existe en migration pero status si
                    ]);
                }

                // 9. Activity Log
                ActivityLog::create([
                    'user_id' => $user->id,
                    'branch_id' => $user->branch_id,
                    'action' => 'sale.created',
                    'model_type' => 'Sale',
                    'model_id' => $sale->id,
                    'description' => "Venta {$code} registrada.",
                    'ip_address' => $request->ip(),
                ]);

                return $sale;
            });

            $system = SystemSetting::first();
            $format = $system->print_format ?? '80mm';

            return redirect("/sales/{$sale->id}")->with([
                'success' => "Venta {$sale->code} registrada correctamente.",
                'print_url' => "/sales/{$sale->id}/print?format={$format}",
            ]);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function show($id)
    {
        $user = Auth::user();
        $isAdmin = $user->role->name === 'admin';

        $sale = Sale::with(['branch', 'client', 'user', 'cashRegister', 'canceller'])->findOrFail($id);

        if (!$isAdmin && $sale->branch_id !== $user->branch_id) {
            abort(403, 'No tienes permiso para ver esta venta.');
        }

        $items = SaleItem::with('product')->where('sale_id', $id)->get();
        $credit = Credit::where('sale_id', $id)->first();

        return Inertia::render('Sales/Show', [
            'sale' => $sale,
            'items' => $items,
            'credit' => $credit,
        ]);
    }

    public function cancel(Request $request, $id)
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'cancel_reason' => 'required|string|max:500',
        ]);

        $sale = Sale::findOrFail($id);

        if ($sale->status !== 'active') {
            return back()->with('error', 'La venta ya no está activa.');
        }

        $cashRegister = CashRegister::find($sale->cash_register_id);
        if ($cashRegister && $cashRegister->status !== 'open') {
            return back()->with('error', 'No se puede anular la venta porque la caja asociada ya está cerrada.');
        }

        try {
            DB::transaction(function () use ($sale, $validated, $user, $request) {
                // 1. Actualizar sale
                $sale->update([
                    'status' => 'cancelled',
                    'cancel_reason' => $validated['cancel_reason'],
                    'cancelled_by' => $user->id,
                    'cancelled_at' => now(),
                ]);

                // 2. Devolver stock
                $items = SaleItem::where('sale_id', $sale->id)->get();
                foreach ($items as $item) {
                    $product = Product::find($item->product_id);
                    if ($product->has_inventory) {
                        $this->inventoryService->addStock(
                            $sale->branch_id,
                            $product->id,
                            $item->quantity,
                            $item->purchase_price,
                            $user->id,
                            "Devolución por anulación de venta {$sale->code}",
                            $sale->id,
                            'sale_cancel'
                        );
                    }
                }

                // 3. Cancelar crédito
                if ($sale->payment_type === 'credit') {
                    Credit::where('sale_id', $sale->id)->update([
                        'status' => 'paid',
                        'notes' => 'Crédito cancelado por anulación de venta.',
                    ]);
                }

                // 4. Activity log
                ActivityLog::create([
                    'user_id' => $user->id,
                    'branch_id' => $user->branch_id,
                    'action' => 'sale.cancelled',
                    'model_type' => 'Sale',
                    'model_id' => $sale->id,
                    'description' => "Venta {$sale->code} anulada. Motivo: {$validated['cancel_reason']}",
                    'ip_address' => $request->ip(),
                ]);
            });

            return back()->with('success', "Venta {$sale->code} anulada correctamente.");
        } catch (\Exception $e) {
            return back()->with('error', 'Error al anular la venta: ' . $e->getMessage());
        }
    }

    public function printNote(Request $request, $id)
    {
        $user = Auth::user();
        $isAdmin = $user->role->name === 'admin';

        $format = $request->query('format', '80mm');
        if (!in_array($format, ['58mm', '80mm', 'A4'])) {
            $format = '80mm';
        }

        $sale = Sale::with(['branch', 'client', 'user', 'items.product'])->findOrFail($id);

        if (!$isAdmin && $sale->branch_id !== $user->branch_id) {
            abort(403, 'No tienes permiso para ver esta venta.');
        }

        DeliveryNote::create([
            'sale_id' => $sale->id,
            'branch_id' => $sale->branch_id,
            'format' => $format,
            'printed_by' => $user->id,
            'printed_at' => now(),
        ]);

        $system = SystemSetting::first();

        return view("print.note-".strtolower($format), [
            'sale' => $sale,
            'system' => $system,
        ]);
    }
}
