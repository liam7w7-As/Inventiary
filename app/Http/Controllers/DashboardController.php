<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Sale;
use App\Models\Product;
use App\Models\Client;
use App\Models\PreSale;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $isAdmin = $user->role->name === 'admin';

        $salesToday = Sale::whereDate('created_at', today())
            ->when(!$isAdmin, fn($q) => $q->where('branch_id', $user->branch_id))
            ->where('status', 'active')
            ->sum('total');

        $productsCount = Product::where('is_active', true)->count();

        $clientsCount = Client::when(!$isAdmin, fn($q) => $q->where('branch_id', $user->branch_id))->count();

        $preSalesPending = PreSale::where('status', 'pending')
            ->when(!$isAdmin, fn($q) => $q->where('branch_id', $user->branch_id))
            ->count();

        return Inertia::render('Dashboard/Index', [
            'stats' => [
                'sales_today' => $salesToday,
                'products_count' => $productsCount,
                'clients_count' => $clientsCount,
                'presales_pending' => $preSalesPending,
            ]
        ]);
    }
}
