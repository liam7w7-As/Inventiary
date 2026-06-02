<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\Branch;
use App\Models\BranchProduct;
use App\Models\InventoryMovement;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user->role->name === 'admin';
        
        $filters = $request->only(['search', 'category_id', 'supplier_id', 'product_type', 'is_active']);

        $query = Product::with(['category', 'supplier'])
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where('name', 'like', "%{$search}%");
            })
            ->when($filters['category_id'] ?? null, function ($q, $category_id) {
                $q->where('category_id', $category_id);
            })
            ->when($filters['supplier_id'] ?? null, function ($q, $supplier_id) {
                $q->where('supplier_id', $supplier_id);
            })
            ->when($filters['product_type'] ?? null, function ($q, $type) {
                $q->where('product_type', $type);
            })
            ->when(isset($filters['is_active']) && $filters['is_active'] !== '' && $isAdmin, function ($q) use ($filters) {
                $q->where('is_active', $filters['is_active']);
            });

        if ($isAdmin) {
            // Admin sees all products and total stock
            $products = $query->withSum('branchProducts', 'current_stock')
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString();
        } else {
            // Encargado sees only active products and stock for their branch
            $products = $query->where('is_active', true)
                ->with(['branchProducts' => function ($q) use ($user) {
                    $q->where('branch_id', $user->branch_id);
                }])
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString()
                ->through(function ($product) {
                    $branchProduct = $product->branchProducts->first();
                    $product->current_stock = $branchProduct ? $branchProduct->current_stock : 0;
                    $product->min_stock = $branchProduct ? $branchProduct->min_stock : 0;
                    // Remove the relation to avoid sending unnecessary data
                    unset($product->branchProducts);
                    return $product;
                });
        }

        return Inertia::render('Products/Index', [
            'products'   => $products,
            'categories' => Category::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'suppliers'  => Supplier::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'filters'    => $filters,
        ]);
    }

    public function create()
    {
        return Inertia::render('Products/Create', [
            'categories' => Category::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'suppliers'  => Supplier::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'branches'   => Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:150',
            'description'    => 'nullable|string|max:500',
            'category_id'    => 'required|exists:categories,id',
            'supplier_id'    => 'nullable|exists:suppliers,id',
            'product_type'   => 'required|in:unit,box',
            'units_per_box'  => 'required_if:product_type,box|nullable|integer|min:1',
            'purchase_price' => 'required|numeric|min:0',
            'sale_price'     => 'required|numeric|min:0',
            'box_discount'   => 'nullable|numeric|min:0|max:100',
            'notes'          => 'nullable|string|max:500',
            'has_inventory'  => 'boolean',
            'is_active'      => 'boolean',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $product = Product::create($validated);

            // Initialize stock in all active branches
            if ($product->has_inventory) {
                $branches = Branch::where('is_active', true)->get();
                foreach ($branches as $branch) {
                    BranchProduct::create([
                        'branch_id'     => $branch->id,
                        'product_id'    => $product->id,
                        'current_stock' => 0,
                        'min_stock'     => 0,
                    ]);
                }
            }

            ActivityLog::create([
                'user_id'     => Auth::id(),
                'branch_id'   => Auth::user()->branch_id,
                'action'      => 'product.created',
                'model_type'  => 'Product',
                'model_id'    => $product->id,
                'description' => "Producto '{$product->name}' creado.",
                'ip_address'  => $request->ip(),
            ]);
        });

        return redirect('/products')->with('success', 'Producto creado correctamente e inventario inicializado.');
    }

    public function edit($id)
    {
        $product = Product::with('branchProducts.branch')->findOrFail($id);

        return Inertia::render('Products/Edit', [
            'product'    => $product,
            'categories' => Category::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'suppliers'  => Supplier::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name'           => 'required|string|max:150',
            'description'    => 'nullable|string|max:500',
            'category_id'    => 'required|exists:categories,id',
            'supplier_id'    => 'nullable|exists:suppliers,id',
            'product_type'   => 'required|in:unit,box',
            'units_per_box'  => 'required_if:product_type,box|nullable|integer|min:1',
            'purchase_price' => 'required|numeric|min:0',
            'sale_price'     => 'required|numeric|min:0',
            'box_discount'   => 'nullable|numeric|min:0|max:100',
            'notes'          => 'nullable|string|max:500',
            'has_inventory'  => 'boolean',
            'is_active'      => 'boolean',
        ]);

        if ($validated['product_type'] === 'unit') {
            $validated['units_per_box'] = null;
            $validated['box_discount'] = 0;
        }

        DB::transaction(function () use ($product, $validated, $request) {
            $product->update($validated);

            ActivityLog::create([
                'user_id'     => Auth::id(),
                'branch_id'   => Auth::user()->branch_id,
                'action'      => 'product.updated',
                'model_type'  => 'Product',
                'model_id'    => $product->id,
                'description' => "Producto '{$product->name}' actualizado.",
                'ip_address'  => $request->ip(),
            ]);
        });

        return redirect('/products')->with('success', "Producto '{$product->name}' actualizado correctamente.");
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        // Check if there are sales (Assuming SaleItem model exists for items)
        if (\App\Models\SaleItem::where('product_id', $product->id)->exists()) {
            return back()->with('error', 'No se puede eliminar el producto porque tiene ventas asociadas.');
        }

        // Check if there are pre-sales
        if (\App\Models\PreSaleItem::where('product_id', $product->id)->exists()) {
            return back()->with('error', 'No se puede eliminar el producto porque tiene preventas asociadas.');
        }

        // Check if there are inventory movements
        if (\App\Models\InventoryMovement::where('product_id', $product->id)->exists()) {
            return back()->with('error', 'No se puede eliminar el producto porque tiene movimientos de inventario asociados.');
        }

        DB::transaction(function () use ($product) {
            // Also delete branch_products relationships (Soft delete)
            $product->branchProducts()->delete();
            $product->delete();

            ActivityLog::create([
                'user_id'     => Auth::id(),
                'branch_id'   => Auth::user()->branch_id,
                'action'      => 'product.deleted',
                'model_type'  => 'Product',
                'model_id'    => $product->id,
                'description' => "Producto '{$product->name}' eliminado.",
                'ip_address'  => request()->ip(),
            ]);
        });

        return redirect('/products')->with('success', "Producto '{$product->name}' eliminado correctamente.");
    }

    public function toggleStatus($id)
    {
        $product = Product::findOrFail($id);
        $product->update(['is_active' => !$product->is_active]);
        $status = $product->is_active ? 'activado' : 'desactivado';

        ActivityLog::create([
            'user_id'     => Auth::id(),
            'branch_id'   => Auth::user()->branch_id,
            'action'      => 'product.toggled',
            'model_type'  => 'Product',
            'model_id'    => $product->id,
            'description' => "Producto '{$product->name}' {$status}.",
            'ip_address'  => request()->ip(),
        ]);

        return back()->with('success', "Producto '{$product->name}' {$status}.");
    }

    public function show($id)
    {
        $user = Auth::user();
        $isAdmin = $user->role->name === 'admin';

        $product = Product::with(['category', 'supplier'])->findOrFail($id);

        $branchStocksQuery = BranchProduct::with('branch')->where('product_id', $id);
        
        if (!$isAdmin) {
            $branchStocksQuery->where('branch_id', $user->branch_id);
        }

        $branchStocks = $branchStocksQuery->get();

        $movementsQuery = InventoryMovement::with(['branch', 'user'])
            ->where('product_id', $id)
            ->latest()
            ->take(10);

        if (!$isAdmin) {
            $movementsQuery->where('branch_id', $user->branch_id);
        }

        $movements = $movementsQuery->get();

        return Inertia::render('Products/Show', [
            'product'       => $product,
            'branch_stocks' => $branchStocks,
            'movements'     => $movements,
        ]);
    }
}
