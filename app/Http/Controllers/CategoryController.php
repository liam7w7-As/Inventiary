<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'is_active']);

        $categories = Category::withCount('products')
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where('name', 'like', "%{$search}%");
            })
            ->when(isset($filters['is_active']) && $filters['is_active'] !== '', function ($q) use ($filters) {
                $q->where('is_active', $filters['is_active']);
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Categories/Index', [
            'categories' => $categories,
            'filters'    => $filters,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:100|unique:categories',
            'description' => 'nullable|string|max:255',
            'is_active'   => 'boolean',
        ]);

        $category = Category::create($validated);

        ActivityLog::create([
            'user_id'     => Auth::id(),
            'branch_id'   => Auth::user()->branch_id,
            'action'      => 'category.created',
            'model_type'  => 'Category',
            'model_id'    => $category->id,
            'description' => "Categoría '{$category->name}' creada.",
            'ip_address'  => $request->ip(),
        ]);

        return redirect()->back()->with('success', "Categoría '{$category->name}' creada correctamente.");
    }

    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:100', Rule::unique('categories')->ignore($category->id)],
            'description' => 'nullable|string|max:255',
            'is_active'   => 'boolean',
        ]);

        $category->update($validated);

        ActivityLog::create([
            'user_id'     => Auth::id(),
            'branch_id'   => Auth::user()->branch_id,
            'action'      => 'category.updated',
            'model_type'  => 'Category',
            'model_id'    => $category->id,
            'description' => "Categoría '{$category->name}' actualizada.",
            'ip_address'  => $request->ip(),
        ]);

        return redirect()->back()->with('success', "Categoría '{$category->name}' actualizada correctamente.");
    }

    public function destroy($id)
    {
        $category = Category::findOrFail($id);

        if ($category->products()->withTrashed()->exists()) {
            return back()->with('error', 'No se puede eliminar la categoría porque tiene productos asociados.');
        }

        $category->delete();

        ActivityLog::create([
            'user_id'     => Auth::id(),
            'branch_id'   => Auth::user()->branch_id,
            'action'      => 'category.deleted',
            'model_type'  => 'Category',
            'model_id'    => $category->id,
            'description' => "Categoría '{$category->name}' eliminada.",
            'ip_address'  => request()->ip(),
        ]);

        return redirect()->back()->with('success', "Categoría '{$category->name}' eliminada correctamente.");
    }

    public function toggleStatus($id)
    {
        $category = Category::findOrFail($id);

        $category->update(['is_active' => !$category->is_active]);

        $status = $category->is_active ? 'activada' : 'desactivada';

        ActivityLog::create([
            'user_id'     => Auth::id(),
            'branch_id'   => Auth::user()->branch_id,
            'action'      => 'category.toggled',
            'model_type'  => 'Category',
            'model_id'    => $category->id,
            'description' => "Categoría '{$category->name}' {$status}.",
            'ip_address'  => request()->ip(),
        ]);

        return back()->with('success', "Categoría '{$category->name}' {$status}.");
    }
}
