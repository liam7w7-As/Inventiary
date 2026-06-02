<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'role_id', 'is_active']);

        $users = User::with(['role', 'branch'])
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('username', 'like', "%{$search}%");
                });
            })
            ->when(isset($filters['role_id']) && $filters['role_id'] !== '', function ($q) use ($filters) {
                $q->where('role_id', $filters['role_id']);
            })
            ->when(isset($filters['is_active']) && $filters['is_active'] !== '', function ($q) use ($filters) {
                $q->where('is_active', $filters['is_active']);
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Users/Index', [
            'users'   => $users,
            'roles'   => Role::all(),
            'filters' => $filters,
        ]);
    }

    public function create()
    {
        return Inertia::render('Users/Create', [
            'roles'    => Role::all(),
            'branches' => Branch::where('is_active', true)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:150',
            'username'         => 'required|string|max:50|unique:users',
            'email'            => 'nullable|email|unique:users',
            'password'         => 'required|string|min:6|confirmed',
            'role_id'          => 'required|exists:roles,id',
            'branch_id'        => 'nullable|exists:branches,id',
            'bypass_schedule'  => 'boolean',
            'is_active'        => 'boolean',
            'can_view_profits' => 'boolean',
            'photo'            => 'nullable|image|max:2048',
        ]);

        $role = Role::find($validated['role_id']);

        if ($role->name === 'encargado' && empty($validated['branch_id'])) {
            return back()->withErrors(['branch_id' => 'El encargado debe tener una sucursal asignada.']);
        }

        if ($role->name === 'admin') {
            $validated['branch_id'] = null;
        }

        $validated['password'] = Hash::make($validated['password']);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('users', 'public');
        }

        unset($validated['password_confirmation']);

        $user = User::create($validated);

        ActivityLog::create([
            'user_id'     => Auth::id(),
            'branch_id'   => Auth::user()->branch_id,
            'action'      => 'user.created',
            'model_type'  => 'User',
            'model_id'    => $user->id,
            'description' => "Usuario '{$user->name}' creado.",
            'ip_address'  => $request->ip(),
        ]);

        return redirect('/users')->with('success', "Usuario '{$user->name}' creado correctamente.");
    }

    public function edit($id)
    {
        $user = User::with(['role', 'branch'])->findOrFail($id);

        return Inertia::render('Users/Edit', [
            'user'     => $user,
            'roles'    => Role::all(),
            'branches' => Branch::where('is_active', true)->get(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name'             => 'required|string|max:150',
            'username'         => ['required', 'string', 'max:50', Rule::unique('users')->ignore($user->id)],
            'email'            => ['nullable', 'email', Rule::unique('users')->ignore($user->id)],
            'password'         => 'nullable|string|min:6|confirmed',
            'role_id'          => 'required|exists:roles,id',
            'branch_id'        => 'nullable|exists:branches,id',
            'bypass_schedule'  => 'boolean',
            'is_active'        => 'boolean',
            'can_view_profits' => 'boolean',
            'photo'            => 'nullable|image|max:2048',
        ]);

        $role = Role::find($validated['role_id']);

        if ($role->name === 'encargado' && empty($validated['branch_id'])) {
            return back()->withErrors(['branch_id' => 'El encargado debe tener una sucursal asignada.']);
        }

        if ($role->name === 'admin') {
            $validated['branch_id'] = null;
        }

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        unset($validated['password_confirmation']);

        if ($request->hasFile('photo')) {
            if ($user->photo && Storage::disk('public')->exists($user->photo)) {
                Storage::disk('public')->delete($user->photo);
            }
            $validated['photo'] = $request->file('photo')->store('users', 'public');
        }

        $user->update($validated);

        ActivityLog::create([
            'user_id'     => Auth::id(),
            'branch_id'   => Auth::user()->branch_id,
            'action'      => 'user.updated',
            'model_type'  => 'User',
            'model_id'    => $user->id,
            'description' => "Usuario '{$user->name}' actualizado.",
            'ip_address'  => $request->ip(),
        ]);

        return redirect('/users')->with('success', "Usuario '{$user->name}' actualizado correctamente.");
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === Auth::id()) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        // Check for associated sales or inventory movements
        $hasSales = $user->id && \App\Models\Sale::where('user_id', $user->id)->exists();
        $hasMovements = $user->id && \App\Models\InventoryMovement::where('user_id', $user->id)->exists();

        if ($hasSales || $hasMovements) {
            return back()->with('error', 'No se puede eliminar este usuario porque tiene ventas o movimientos asociados.');
        }

        $user->delete();

        ActivityLog::create([
            'user_id'     => Auth::id(),
            'branch_id'   => Auth::user()->branch_id,
            'action'      => 'user.deleted',
            'model_type'  => 'User',
            'model_id'    => $user->id,
            'description' => "Usuario '{$user->name}' eliminado.",
            'ip_address'  => request()->ip(),
        ]);

        return redirect('/users')->with('success', "Usuario '{$user->name}' eliminado correctamente.");
    }

    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === Auth::id()) {
            return back()->with('error', 'No puedes desactivar tu propia cuenta.');
        }

        $user->update(['is_active' => !$user->is_active]);

        $status = $user->is_active ? 'activado' : 'desactivado';

        ActivityLog::create([
            'user_id'     => Auth::id(),
            'branch_id'   => Auth::user()->branch_id,
            'action'      => 'user.toggled',
            'model_type'  => 'User',
            'model_id'    => $user->id,
            'description' => "Usuario '{$user->name}' {$status}.",
            'ip_address'  => request()->ip(),
        ]);

        return back()->with('success', "Usuario '{$user->name}' {$status}.");
    }

    public function resetPassword(Request $request, $id)
    {
        $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::findOrFail($id);
        $user->update(['password' => Hash::make($request->password)]);

        ActivityLog::create([
            'user_id'     => Auth::id(),
            'branch_id'   => Auth::user()->branch_id,
            'action'      => 'user.password_reset',
            'model_type'  => 'User',
            'model_id'    => $user->id,
            'description' => "Contraseña de '{$user->name}' restablecida.",
            'ip_address'  => $request->ip(),
        ]);

        return back()->with('success', "Contraseña de '{$user->name}' restablecida correctamente.");
    }
}
