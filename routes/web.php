<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CashRegisterController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PreSaleController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CreditController;
use App\Http\Controllers\StockTransferController;
use Illuminate\Support\Facades\Route;

// Redirect root to dashboard
Route::get('/', fn () => redirect()->route('dashboard'));

// Auth routes (guest)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected routes
Route::middleware(['auth', 'schedule'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Admin-only routes
    Route::middleware(['role:admin'])->group(function () {
        // Settings
        Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');

        // Users
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{id}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::patch('/users/{id}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle');
        Route::put('/users/{id}/reset-password', [UserController::class, 'resetPassword'])->name('users.resetPassword');

        // Branches
        Route::get('/branches', [BranchController::class, 'index'])->name('branches.index');
        Route::get('/branches/create', [BranchController::class, 'create'])->name('branches.create');
        Route::post('/branches', [BranchController::class, 'store'])->name('branches.store');
        Route::get('/branches/{id}/edit', [BranchController::class, 'edit'])->name('branches.edit');
        Route::put('/branches/{id}', [BranchController::class, 'update'])->name('branches.update');
        Route::delete('/branches/{id}', [BranchController::class, 'destroy'])->name('branches.destroy');
        Route::patch('/branches/{id}/toggle-status', [BranchController::class, 'toggleStatus'])->name('branches.toggle');

        // Categories
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{id}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy'])->name('categories.destroy');
        Route::patch('/categories/{id}/toggle-status', [CategoryController::class, 'toggleStatus'])->name('categories.toggle');

        // Suppliers
        Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
        Route::get('/suppliers/create', [SupplierController::class, 'create'])->name('suppliers.create');
        Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
        Route::get('/suppliers/{id}/edit', [SupplierController::class, 'edit'])->name('suppliers.edit');
        Route::put('/suppliers/{id}', [SupplierController::class, 'update'])->name('suppliers.update');
        Route::delete('/suppliers/{id}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');
        Route::patch('/suppliers/{id}/toggle-status', [SupplierController::class, 'toggleStatus'])->name('suppliers.toggle');

        // Products (Admin only actions)
        Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::get('/products/{id}/edit', [ProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/{id}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{id}', [ProductController::class, 'destroy'])->name('products.destroy');
        Route::patch('/products/{id}/toggle-status', [ProductController::class, 'toggleStatus'])->name('products.toggle');

        // Inventory (Admin only actions)
        Route::post('/inventory/add-stock', [InventoryController::class, 'addStock'])->name('inventory.addStock');
        Route::post('/inventory/adjust-stock', [InventoryController::class, 'adjustStock'])->name('inventory.adjustStock');

        // Cash Registers (Admin only actions)
        Route::get('/cash-registers/create', [CashRegisterController::class, 'create'])->name('cash-registers.create');
        Route::post('/cash-registers', [CashRegisterController::class, 'store'])->name('cash-registers.store');
        Route::get('/cash-registers/users-by-branch/{branchId}', [CashRegisterController::class, 'usersByBranch'])->name('cash-registers.usersByBranch');

        // PreSales (Admin only actions)
        Route::patch('/pre-sales/{id}/approve', [PreSaleController::class, 'approve'])->name('pre-sales.approve');
        Route::patch('/pre-sales/{id}/reject', [PreSaleController::class, 'reject'])->name('pre-sales.reject');

        // Sales (Admin only actions)
        Route::patch('/sales/{id}/cancel', [SaleController::class, 'cancel'])->name('sales.cancel');

        // Credits (Admin only actions)
        Route::patch('/credits/{id}/due-date', [CreditController::class, 'updateDueDate'])->name('credits.updateDueDate');

        // Transfers (Admin only actions)
        Route::patch('/transfers/{id}/approve', [StockTransferController::class, 'approve'])->name('transfers.approve');
        Route::patch('/transfers/{id}/reject', [StockTransferController::class, 'reject'])->name('transfers.reject');
    });

    Route::middleware(['role:admin,encargado'])->group(function () {
        // Products (Admin & Encargado)
        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::get('/products/{id}', [ProductController::class, 'show'])->name('products.show');

        // Inventory (Admin & Encargado)
        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::get('/inventory/movements', [InventoryController::class, 'movements'])->name('inventory.movements');
        Route::patch('/inventory/{id}/min-stock', [InventoryController::class, 'updateMinStock'])->name('inventory.updateMinStock');

        // Clients (Admin & Encargado)
        Route::get('/clients/search', [ClientController::class, 'search'])->name('clients.search');
        Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
        Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');
        Route::put('/clients/{id}', [ClientController::class, 'update'])->name('clients.update');
        Route::delete('/clients/{id}', [ClientController::class, 'destroy'])->name('clients.destroy');

        // Cash Registers (Admin & Encargado)
        Route::get('/cash-registers', [CashRegisterController::class, 'index'])->name('cash-registers.index');
        Route::get('/cash-registers/active', [CashRegisterController::class, 'getActive'])->name('cash-registers.active');
        Route::get('/cash-registers/{id}', [CashRegisterController::class, 'show'])->name('cash-registers.show');

        // PreSales (Admin & Encargado)
        Route::get('/pre-sales', [PreSaleController::class, 'index'])->name('pre-sales.index');
        Route::get('/pre-sales/create', [PreSaleController::class, 'create'])->name('pre-sales.create');
        Route::post('/pre-sales', [PreSaleController::class, 'store'])->name('pre-sales.store');
        Route::get('/pre-sales/{id}', [PreSaleController::class, 'show'])->name('pre-sales.show');
        Route::delete('/pre-sales/{id}', [PreSaleController::class, 'destroy'])->name('pre-sales.destroy');

        // Sales (Admin & Encargado)
        Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
        Route::get('/sales/create', [SaleController::class, 'create'])->name('sales.create');
        Route::post('/sales', [SaleController::class, 'store'])->name('sales.store');
        Route::get('/sales/{id}', [SaleController::class, 'show'])->name('sales.show');
        Route::get('/sales/{id}/print', [SaleController::class, 'printNote'])->name('sales.print');

        // Credits (Admin & Encargado)
        Route::get('/credits', [CreditController::class, 'index'])->name('credits.index');
        Route::get('/credits/{id}', [CreditController::class, 'show'])->name('credits.show');
        Route::post('/credits/{id}/payment', [CreditController::class, 'addPayment'])->name('credits.addPayment');

        // Transfers (Admin & Encargado)
        Route::get('/transfers', [StockTransferController::class, 'index'])->name('transfers.index');
        Route::get('/transfers/create', [StockTransferController::class, 'create'])->name('transfers.create');
        Route::post('/transfers', [StockTransferController::class, 'store'])->name('transfers.store');
        Route::get('/transfers/{id}', [StockTransferController::class, 'show'])->name('transfers.show');
    });
});

