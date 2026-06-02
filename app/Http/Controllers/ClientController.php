<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Branch;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user->role->name === 'admin';

        $filters = $request->only(['search', 'branch_id']);

        $query = Client::with('branch')
            ->withCount('sales')
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($filters['branch_id'] ?? null, function ($q, $branch_id) {
                $q->where('branch_id', $branch_id);
            });

        if (!$isAdmin) {
            $query->where('branch_id', $user->branch_id);
        }

        $clients = $query->orderBy('name')->paginate(15)->withQueryString();

        return Inertia::render('Clients/Index', [
            'clients'  => $clients,
            'branches' => $isAdmin ? Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']) : [],
            'filters'  => $filters,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user->role->name === 'admin';

        $validated = $request->validate([
            'name'      => 'required|string|max:150',
            'phone'     => 'nullable|string|max:30',
            'email'     => 'nullable|email|max:100',
            'notes'     => 'nullable|string|max:500',
            'branch_id' => 'required|exists:branches,id',
        ]);

        // Encargado can only create clients for their own branch
        if (!$isAdmin) {
            $validated['branch_id'] = $user->branch_id;
        }

        $client = Client::create($validated);

        ActivityLog::create([
            'user_id'     => Auth::id(),
            'branch_id'   => $user->branch_id,
            'action'      => 'client.created',
            'model_type'  => 'Client',
            'model_id'    => $client->id,
            'description' => "Cliente '{$client->name}' creado.",
            'ip_address'  => $request->ip(),
        ]);

        return back()->with('success', "Cliente '{$client->name}' creado correctamente.");
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $isAdmin = $user->role->name === 'admin';
        $client = Client::findOrFail($id);

        // Encargado can only edit clients from their own branch
        if (!$isAdmin && $client->branch_id !== $user->branch_id) {
            abort(403, 'No tienes permiso para editar este cliente.');
        }

        $validated = $request->validate([
            'name'      => 'required|string|max:150',
            'phone'     => 'nullable|string|max:30',
            'email'     => 'nullable|email|max:100',
            'notes'     => 'nullable|string|max:500',
            'branch_id' => 'required|exists:branches,id',
        ]);

        if (!$isAdmin) {
            $validated['branch_id'] = $user->branch_id;
        }

        $client->update($validated);

        ActivityLog::create([
            'user_id'     => Auth::id(),
            'branch_id'   => $user->branch_id,
            'action'      => 'client.updated',
            'model_type'  => 'Client',
            'model_id'    => $client->id,
            'description' => "Cliente '{$client->name}' actualizado.",
            'ip_address'  => $request->ip(),
        ]);

        return back()->with('success', "Cliente '{$client->name}' actualizado correctamente.");
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $isAdmin = $user->role->name === 'admin';
        $client = Client::findOrFail($id);

        if (!$isAdmin && $client->branch_id !== $user->branch_id) {
            abort(403, 'No tienes permiso para eliminar este cliente.');
        }

        if ($client->sales()->exists()) {
            return back()->with('error', 'No se puede eliminar el cliente porque tiene ventas asociadas.');
        }

        if ($client->credits()->exists()) {
            return back()->with('error', 'No se puede eliminar el cliente porque tiene créditos asociados.');
        }

        $client->delete();

        ActivityLog::create([
            'user_id'     => Auth::id(),
            'branch_id'   => $user->branch_id,
            'action'      => 'client.deleted',
            'model_type'  => 'Client',
            'model_id'    => $client->id,
            'description' => "Cliente '{$client->name}' eliminado.",
            'ip_address'  => request()->ip(),
        ]);

        return back()->with('success', "Cliente '{$client->name}' eliminado correctamente.");
    }

    public function search(Request $request)
    {
        $request->validate(['q' => 'required|string|min:2']);

        $user = Auth::user();
        $isAdmin = $user->role->name === 'admin';

        $query = Client::where(function ($q) use ($request) {
            $q->where('name', 'like', "%{$request->q}%")
              ->orWhere('phone', 'like', "%{$request->q}%")
              ->orWhere('email', 'like', "%{$request->q}%");
        });

        if (!$isAdmin) {
            $query->where('branch_id', $user->branch_id);
        }

        return $query->limit(10)->get(['id', 'name', 'phone', 'email']);
    }
}
