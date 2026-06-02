<?php

namespace App\Services;

use App\Models\BranchProduct;
use App\Models\InventoryMovement;
use Exception;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Incrementa stock y registra el movimiento de entrada.
     */
    public function addStock(
        $branch_id, 
        $product_id, 
        $quantity, 
        $purchase_price = null, 
        $user_id, 
        $notes = null, 
        $reference_id = null, 
        $reference_type = null
    ) {
        if ($quantity <= 0) {
            throw new Exception("La cantidad debe ser mayor a 0.");
        }

        return DB::transaction(function () use (
            $branch_id, $product_id, $quantity, $purchase_price, 
            $user_id, $notes, $reference_id, $reference_type
        ) {
            $branchProduct = BranchProduct::firstOrCreate(
                ['branch_id' => $branch_id, 'product_id' => $product_id],
                ['current_stock' => 0, 'min_stock' => 0]
            );

            $stock_before = $branchProduct->current_stock;
            $stock_after = $stock_before + $quantity;

            $branchProduct->update(['current_stock' => $stock_after]);

            $movementType = $reference_type == 'transfer' ? 'transfer_in' : 'in';

            InventoryMovement::create([
                'branch_id' => $branch_id,
                'product_id' => $product_id,
                'user_id' => $user_id,
                'movement_type' => $movementType,
                'quantity' => $quantity,
                'purchase_price' => $purchase_price,
                'stock_before' => $stock_before,
                'stock_after' => $stock_after,
                'reference_id' => $reference_id,
                'reference_type' => $reference_type,
                'notes' => $notes,
            ]);

            return $branchProduct;
        });
    }

    /**
     * Decrementa stock y registra el movimiento de salida.
     */
    public function removeStock(
        $branch_id, 
        $product_id, 
        $quantity, 
        $user_id, 
        $notes = null, 
        $reference_id = null, 
        $reference_type = null
    ) {
        if ($quantity <= 0) {
            throw new Exception("La cantidad debe ser mayor a 0.");
        }

        return DB::transaction(function () use (
            $branch_id, $product_id, $quantity, 
            $user_id, $notes, $reference_id, $reference_type
        ) {
            $branchProduct = BranchProduct::where('branch_id', $branch_id)
                ->where('product_id', $product_id)
                ->first();

            if (!$branchProduct || $branchProduct->current_stock < $quantity) {
                $productName = $branchProduct ? $branchProduct->product->name : 'Producto no encontrado';
                throw new Exception("Stock insuficiente para {$productName}. Stock actual: " . ($branchProduct ? $branchProduct->current_stock : 0));
            }

            $stock_before = $branchProduct->current_stock;
            $stock_after = $stock_before - $quantity;

            $branchProduct->update(['current_stock' => $stock_after]);

            $movementType = $reference_type == 'transfer' ? 'transfer_out' : 'out';

            InventoryMovement::create([
                'branch_id' => $branch_id,
                'product_id' => $product_id,
                'user_id' => $user_id,
                'movement_type' => $movementType,
                'quantity' => $quantity,
                'stock_before' => $stock_before,
                'stock_after' => $stock_after,
                'reference_id' => $reference_id,
                'reference_type' => $reference_type,
                'notes' => $notes,
            ]);

            return $branchProduct;
        });
    }

    /**
     * Ajusta el stock directamente (puede ser positivo o negativo).
     */
    public function adjustStock(
        $branch_id, 
        $product_id, 
        $quantity, 
        $user_id, 
        $notes
    ) {
        if ($quantity == 0) {
            throw new Exception("La cantidad de ajuste no puede ser 0.");
        }

        if (empty($notes)) {
            throw new Exception("Debe proveer un motivo para el ajuste de stock.");
        }

        return DB::transaction(function () use (
            $branch_id, $product_id, $quantity, $user_id, $notes
        ) {
            $branchProduct = BranchProduct::firstOrCreate(
                ['branch_id' => $branch_id, 'product_id' => $product_id],
                ['current_stock' => 0, 'min_stock' => 0]
            );

            $stock_before = $branchProduct->current_stock;
            $stock_after = $stock_before + $quantity;

            if ($stock_after < 0) {
                throw new Exception("El ajuste resultaría en un stock negativo ({$stock_after}). Operación cancelada.");
            }

            $branchProduct->update(['current_stock' => $stock_after]);

            InventoryMovement::create([
                'branch_id' => $branch_id,
                'product_id' => $product_id,
                'user_id' => $user_id,
                'movement_type' => 'adjustment',
                'quantity' => $quantity,
                'stock_before' => $stock_before,
                'stock_after' => $stock_after,
                'notes' => $notes,
            ]);

            return $branchProduct;
        });
    }

    /**
     * Obtiene el stock actual de un producto en una sucursal.
     */
    public function getStock($branch_id, $product_id)
    {
        $branchProduct = BranchProduct::where('branch_id', $branch_id)
            ->where('product_id', $product_id)
            ->first();

        return $branchProduct ? $branchProduct->current_stock : 0;
    }

    /**
     * Verifica si hay stock suficiente.
     */
    public function hasStock($branch_id, $product_id, $quantity)
    {
        return $this->getStock($branch_id, $product_id) >= $quantity;
    }
}
