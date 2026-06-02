<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'is_active']);

        $suppliers = Supplier::withCount('products')
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('contact_name', 'like', "%{$search}%");
            })
            ->when(isset($filters['is_active']) && $filters['is_active'] !== '', function ($q) use ($filters) {
                $q->where('is_active', $filters['is_active']);
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Suppliers/Index', [
            'suppliers' => $suppliers,
            'filters'   => $filters,
        ]);
    }

    public function create()
    {
        return Inertia::render('Suppliers/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:150',
            'contact_name' => 'nullable|string|max:150',
            'phone'        => 'nullable|string|max:30',
            'email'        => 'nullable|email|max:100',
            'address'      => 'nullable|string|max:255',
            'notes'        => 'nullable|string|max:500',
            'is_active'    => 'boolean',
        ]);

        $supplier = Supplier::create($validated);

        ActivityLog::create([
            'user_id'     => Auth::id(),
            'branch_id'   => Auth::user()->branch_id,
            'action'      => 'supplier.created',
            'model_type'  => 'Supplier',
            'model_id'    => $supplier->id,
            'description' => "Proveedor '{$supplier->name}' creado.",
            'ip_address'  => $request->ip(),
        ]);

        return redirect('/suppliers')->with('success', "Proveedor '{$supplier->name}' creado correctamente.");
    }

    public function edit($id)
    {
        $supplier = Supplier::findOrFail($id);

        return Inertia::render('Suppliers/Edit', [
            'supplier' => $supplier,
        ]);
    }

    public function update(Request $request, $id)
    {
        $supplier = Supplier::findOrFail($id);

        $validated = $request->validate([
            'name'         => 'required|string|max:150',
            'contact_name' => 'nullable|string|max:150',
            'phone'        => 'nullable|string|max:30',
            'email'        => 'nullable|email|max:100',
            'address'      => 'nullable|string|max:255',
            'notes'        => 'nullable|string|max:500',
            'is_active'    => 'boolean',
        ]);

        $supplier->update($validated);

        ActivityLog::create([
            'user_id'     => Auth::id(),
            'branch_id'   => Auth::user()->branch_id,
            'action'      => 'supplier.updated',
            'model_type'  => 'Supplier',
            'model_id'    => $supplier->id,
            'description' => "Proveedor '{$supplier->name}' actualizado.",
            'ip_address'  => $request->ip(),
        ]);

        return redirect('/suppliers')->with('success', "Proveedor '{$supplier->name}' actualizado correctamente.");
    }

    public function destroy($id)
    {
        $supplier = Supplier::findOrFail($id);

        if ($supplier->products()->withTrashed()->exists()) {
            return back()->with('error', 'No se puede eliminar el proveedor porque tiene productos asociados.');
        }

        $supplier->delete();

        ActivityLog::create([
            'user_id'     => Auth::id(),
            'branch_id'   => Auth::user()->branch_id,
            'action'      => 'supplier.deleted',
            'model_type'  => 'Supplier',
            'model_id'    => $supplier->id,
            'description' => "Proveedor '{$supplier->name}' eliminado.",
            'ip_address'  => request()->ip(),
        ]);

        return redirect('/suppliers')->with('success', "Proveedor '{$supplier->name}' eliminado correctamente.");
    }

    public function toggleStatus($id)
    {
        $supplier = Supplier::findOrFail($id);

        $supplier->update(['is_active' => !$supplier->is_active]);

        $status = $supplier->is_active ? 'activado' : 'desactivado';

        ActivityLog::create([
            'user_id'     => Auth::id(),
            'branch_id'   => Auth::user()->branch_id,
            'action'      => 'supplier.toggled',
            'model_type'  => 'Supplier',
            'model_id'    => $supplier->id,
            'description' => "Proveedor '{$supplier->name}' {$status}.",
            'ip_address'  => request()->ip(),
        ]);

        return back()->with('success', "Proveedor '{$supplier->name}' {$status}.");
    }
}
