<?php

namespace App\Http\Controllers;

use App\Models\PreSale;
use App\Models\PreSaleItem;
use App\Models\Product;
use App\Models\Client;
use App\Models\Branch;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PreSaleController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user->role->name === 'admin';

        $filters = $request->only(['status', 'branch_id', 'client_id', 'date_from', 'date_to']);

        $query = PreSale::with(['branch', 'client', 'requester', 'approver'])
            ->withCount('items')
            ->when($filters['status'] ?? null, fn($q, $v) => $q->where('status', $v))
            ->when($filters['client_id'] ?? null, fn($q, $v) => $q->where('client_id', $v))
            ->when($filters['date_from'] ?? null, fn($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn($q, $v) => $q->whereDate('created_at', '<=', $v));

        if ($isAdmin) {
            $query->when($filters['branch_id'] ?? null, fn($q, $v) => $q->where('branch_id', $v));
        } else {
            $query->where('branch_id', $user->branch_id);
        }

        $preSales = $query->latest()->paginate(15)->withQueryString();

        // Calculate total for each presale from its items
        $preSales->getCollection()->transform(function ($preSale) {
            $preSale->total_estimated = $preSale->items->sum('subtotal');
            return $preSale;
        });

        $pendingCount = PreSale::where('status', 'pending')->count();

        return Inertia::render('PreSales/Index', [
            'preSales'     => $preSales,
            'branches'     => $isAdmin ? Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']) : [],
            'filters'      => $filters,
            'pendingCount' => $pendingCount,
        ]);
    }

    public function create()
    {
        $user = Auth::user();
        $isAdmin = $user->role->name === 'admin';

        $clientsQuery = Client::orderBy('name');
        $productsQuery = Product::where('is_active', true)->with('category')->orderBy('name');

        if (!$isAdmin) {
            $clientsQuery->where('branch_id', $user->branch_id);
            // For products, get those with stock in user's branch
            $productsQuery->whereHas('branchProducts', function ($q) use ($user) {
                $q->where('branch_id', $user->branch_id);
            });
        }

        return Inertia::render('PreSales/Create', [
            'clients'  => $clientsQuery->get(['id', 'name', 'phone']),
            'products' => $productsQuery->get(['id', 'name', 'sale_price', 'product_type', 'units_per_box', 'category_id']),
            'branches' => $isAdmin ? Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']) : [],
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user->role->name === 'admin';

        $validated = $request->validate([
            'branch_id'           => 'required|exists:branches,id',
            'client_id'           => 'nullable|exists:clients,id',
            'notes'               => 'nullable|string|max:500',
            'items'               => 'required|array|min:1',
            'items.*.product_id'  => 'required|exists:products,id',
            'items.*.quantity'    => 'required|numeric|min:0.01',
            'items.*.sale_price'  => 'required|numeric|min:0',
            'items.*.discount'    => 'nullable|numeric|min:0|max:100',
        ]);

        if (!$isAdmin) {
            $validated['branch_id'] = $user->branch_id;
        }

        DB::transaction(function () use ($validated, $user, $request) {
            $preSale = PreSale::create([
                'branch_id'    => $validated['branch_id'],
                'client_id'    => $validated['client_id'],
                'requested_by' => $user->id,
                'status'       => 'pending',
                'notes'        => $validated['notes'],
            ]);

            foreach ($validated['items'] as $item) {
                $discount = $item['discount'] ?? 0;
                $subtotal = $item['quantity'] * $item['sale_price'] * (1 - $discount / 100);

                PreSaleItem::create([
                    'pre_sale_id' => $preSale->id,
                    'product_id'  => $item['product_id'],
                    'quantity'    => $item['quantity'],
                    'sale_price'  => $item['sale_price'],
                    'discount'    => $discount,
                    'subtotal'    => round($subtotal, 2),
                ]);
            }

            ActivityLog::create([
                'user_id'     => $user->id,
                'branch_id'   => $user->branch_id,
                'action'      => 'presale.created',
                'model_type'  => 'PreSale',
                'model_id'    => $preSale->id,
                'description' => "Preventa #{$preSale->id} creada.",
                'ip_address'  => $request->ip(),
            ]);
        });

        return redirect('/pre-sales')->with('success', 'Preventa creada correctamente. Queda pendiente de aprobación.');
    }

    public function show($id)
    {
        $user = Auth::user();
        $isAdmin = $user->role->name === 'admin';

        $preSale = PreSale::with(['branch', 'client', 'requester', 'approver', 'items.product'])->findOrFail($id);

        if (!$isAdmin && $preSale->branch_id !== $user->branch_id) {
            abort(403, 'No tienes permiso para ver esta preventa.');
        }

        return Inertia::render('PreSales/Show', [
            'preSale' => $preSale,
        ]);
    }

    public function approve($id)
    {
        $preSale = PreSale::findOrFail($id);

        if ($preSale->status !== 'pending') {
            return back()->with('error', 'Solo se pueden aprobar preventas pendientes.');
        }

        $preSale->update([
            'status'      => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        ActivityLog::create([
            'user_id'     => Auth::id(),
            'branch_id'   => Auth::user()->branch_id,
            'action'      => 'presale.approved',
            'model_type'  => 'PreSale',
            'model_id'    => $preSale->id,
            'description' => "Preventa #{$preSale->id} aprobada.",
            'ip_address'  => request()->ip(),
        ]);

        return back()->with('success', "Preventa #{$preSale->id} aprobada correctamente.");
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'notes' => 'required|string|max:500',
        ]);

        $preSale = PreSale::findOrFail($id);

        if ($preSale->status !== 'pending') {
            return back()->with('error', 'Solo se pueden rechazar preventas pendientes.');
        }

        $preSale->update([
            'status'      => 'rejected',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'notes'       => $request->notes,
        ]);

        ActivityLog::create([
            'user_id'     => Auth::id(),
            'branch_id'   => Auth::user()->branch_id,
            'action'      => 'presale.rejected',
            'model_type'  => 'PreSale',
            'model_id'    => $preSale->id,
            'description' => "Preventa #{$preSale->id} rechazada. Motivo: {$request->notes}",
            'ip_address'  => $request->ip(),
        ]);

        return back()->with('success', "Preventa #{$preSale->id} rechazada.");
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $isAdmin = $user->role->name === 'admin';

        $preSale = PreSale::findOrFail($id);

        if ($preSale->status !== 'pending') {
            return back()->with('error', 'Solo se pueden eliminar preventas pendientes.');
        }

        if (!$isAdmin && $preSale->requested_by !== $user->id) {
            abort(403, 'No tienes permiso para eliminar esta preventa.');
        }

        $preSale->items()->delete();
        $preSale->delete();

        ActivityLog::create([
            'user_id'     => Auth::id(),
            'branch_id'   => $user->branch_id,
            'action'      => 'presale.deleted',
            'model_type'  => 'PreSale',
            'model_id'    => $preSale->id,
            'description' => "Preventa #{$preSale->id} eliminada.",
            'ip_address'  => request()->ip(),
        ]);

        return redirect('/pre-sales')->with('success', "Preventa #{$preSale->id} eliminada.");
    }
}
