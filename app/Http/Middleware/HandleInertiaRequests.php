<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => fn () => $request->user() ? [
                    'id'               => $request->user()->id,
                    'name'             => $request->user()->name,
                    'username'         => $request->user()->username,
                    'photo'            => $request->user()->photo,
                    'role'             => $request->user()->role->name,
                    'branch_id'        => $request->user()->branch_id,
                    'branch_name'      => $request->user()->branch?->name,
                    'can_view_profits' => $request->user()->can_view_profits,
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error'   => fn () => $request->session()->get('error'),
                'print_url' => fn () => $request->session()->get('print_url'),
            ],
            'system' => fn () => SystemSetting::first()?->only([
                'name', 'alias', 'logo', 'currency', 'print_format',
            ]),
            'pendingPreSales' => fn () => auth()->check() && auth()->user()->role->name === 'admin'
                ? \App\Models\PreSale::where('status', 'pending')->count()
                : 0,
            'pendingTransfers' => fn() => auth()->check() && auth()->user()->role->name === 'admin'
                ? \App\Models\StockTransfer::where('status', 'pending')->count()
                : 0,
            'overdueCredits' => fn() => auth()->check()
                ? \App\Models\Credit::where('status', 'overdue')->when(auth()->user()->role->name === 'encargado', fn($q) => $q->where('branch_id', auth()->user()->branch_id))->count()
                : 0,
        ];
    }
}
