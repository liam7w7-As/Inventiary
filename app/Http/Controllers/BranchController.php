<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'is_active']);

        $branches = Branch::with(['users' => function ($query) {
                // Get users with role 'encargado' for this branch
                $query->whereHas('role', function ($q) {
                    $q->where('name', 'encargado');
                });
            }])
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where('name', 'like', "%{$search}%");
            })
            ->when(isset($filters['is_active']) && $filters['is_active'] !== '', function ($q) use ($filters) {
                $q->where('is_active', $filters['is_active']);
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(function ($branch) {
                $manager = $branch->users->first();
                return [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'address' => $branch->address,
                    'phone' => $branch->phone,
                    'email' => $branch->email,
                    'is_active' => $branch->is_active,
                    'manager' => $manager ? [
                        'id' => $manager->id,
                        'name' => $manager->name,
                    ] : null,
                ];
            });

        return Inertia::render('Branches/Index', [
            'branches' => $branches,
            'filters'  => $filters,
        ]);
    }

    public function create()
    {
        return Inertia::render('Branches/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:150|unique:branches',
            'address'   => 'nullable|string|max:255',
            'phone'     => 'nullable|string|max:30',
            'email'     => 'nullable|email|max:100',
            'is_active' => 'boolean',
        ]);

        $branch = Branch::create($validated);

        ActivityLog::create([
            'user_id'     => Auth::id(),
            'branch_id'   => Auth::user()->branch_id,
            'action'      => 'branch.created',
            'model_type'  => 'Branch',
            'model_id'    => $branch->id,
            'description' => "Sucursal '{$branch->name}' creada.",
            'ip_address'  => $request->ip(),
        ]);

        return redirect('/branches')->with('success', "Sucursal '{$branch->name}' creada correctamente.");
    }

    public function edit($id)
    {
        $branch = Branch::findOrFail($id);
        
        $manager = User::where('branch_id', $branch->id)
            ->whereHas('role', function ($q) {
                $q->where('name', 'encargado');
            })->first();

        return Inertia::render('Branches/Edit', [
            'branch'  => $branch,
            'manager' => $manager ? [
                'id' => $manager->id,
                'name' => $manager->name,
                'username' => $manager->username,
            ] : null,
        ]);
    }

    public function update(Request $request, $id)
    {
        $branch = Branch::findOrFail($id);

        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:150', Rule::unique('branches')->ignore($branch->id)],
            'address'   => 'nullable|string|max:255',
            'phone'     => 'nullable|string|max:30',
            'email'     => 'nullable|email|max:100',
            'is_active' => 'boolean',
        ]);

        $branch->update($validated);

        ActivityLog::create([
            'user_id'     => Auth::id(),
            'branch_id'   => Auth::user()->branch_id,
            'action'      => 'branch.updated',
            'model_type'  => 'Branch',
            'model_id'    => $branch->id,
            'description' => "Sucursal '{$branch->name}' actualizada.",
            'ip_address'  => $request->ip(),
        ]);

        return redirect('/branches')->with('success', "Sucursal '{$branch->name}' actualizada correctamente.");
    }

    public function destroy($id)
    {
        $branch = Branch::findOrFail($id);

        // Check for users
        if ($branch->users()->exists()) {
            return back()->with('error', 'No se puede eliminar la sucursal porque tiene usuarios asociados.');
        }

        // Check for products
        if (\App\Models\Product::where('branch_id', $branch->id)->exists()) {
            return back()->with('error', 'No se puede eliminar la sucursal porque tiene productos asociados.');
        }

        // Check for sales
        if (\App\Models\Sale::where('branch_id', $branch->id)->exists()) {
            return back()->with('error', 'No se puede eliminar la sucursal porque tiene ventas asociadas.');
        }

        // Check for inventory movements
        if (\App\Models\InventoryMovement::where('branch_id', $branch->id)->exists()) {
            return back()->with('error', 'No se puede eliminar la sucursal porque tiene movimientos de inventario asociados.');
        }

        $branch->delete();

        ActivityLog::create([
            'user_id'     => Auth::id(),
            'branch_id'   => Auth::user()->branch_id,
            'action'      => 'branch.deleted',
            'model_type'  => 'Branch',
            'model_id'    => $branch->id,
            'description' => "Sucursal '{$branch->name}' eliminada.",
            'ip_address'  => request()->ip(),
        ]);

        return redirect('/branches')->with('success', "Sucursal '{$branch->name}' eliminada correctamente.");
    }

    public function toggleStatus($id)
    {
        $branch = Branch::findOrFail($id);

        // Si se va a desactivar, verificar si tiene usuarios activos asignados
        if ($branch->is_active) {
            $activeUsersCount = $branch->users()->where('is_active', true)->count();
            if ($activeUsersCount > 0) {
                return back()->with('error', 'No se puede desactivar la sucursal porque tiene usuarios activos.');
            }
        }

        $branch->update(['is_active' => !$branch->is_active]);

        $status = $branch->is_active ? 'activada' : 'desactivada';

        ActivityLog::create([
            'user_id'     => Auth::id(),
            'branch_id'   => Auth::user()->branch_id,
            'action'      => 'branch.toggled',
            'model_type'  => 'Branch',
            'model_id'    => $branch->id,
            'description' => "Sucursal '{$branch->name}' {$status}.",
            'ip_address'  => request()->ip(),
        ]);

        return back()->with('success', "Sucursal '{$branch->name}' {$status}.");
    }
}
