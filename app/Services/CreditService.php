<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\PreSale;
use App\Models\Credit;
use App\Models\CreditInstallment;
use App\Models\CreditPayment;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;
use App\Services\InventoryService;
use App\Models\Product;

class CreditService
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Create a credit and its installments from an approved PreSale and Sale.
     */
    public function createFromSale(Sale $sale, PreSale $preSale): Credit
    {
        $installmentsCount = $preSale->approved_installments ?? $preSale->credit_installments ?? 1;
        $periodDays = $preSale->approved_period_days ?? $preSale->credit_period_days ?? 30;

        $installmentAmount = round($sale->total / $installmentsCount, 2);
        $totalCreditDuration = now()->addDays($periodDays);

        $credit = Credit::create([
            'sale_id' => $sale->id,
            'client_id' => $sale->client_id,
            'branch_id' => $sale->branch_id,
            'total_amount' => $sale->total,
            'paid_amount' => 0,
            'balance' => $sale->total,
            'installments_count' => $installmentsCount,
            'installment_amount' => $installmentAmount,
            'start_date' => now()->toDateString(),
            'due_date' => $totalCreditDuration->toDateString(),
            'status' => 'active',
            'notes' => $preSale->credit_notes,
        ]);

        $remainingBalance = $sale->total;

        for ($i = 1; $i <= $installmentsCount; $i++) {
            $daysToAdd = round(($periodDays / $installmentsCount) * $i);
            $dueDate = now()->addDays($daysToAdd)->toDateString();

            $amount = ($i === $installmentsCount) ? $remainingBalance : $installmentAmount;

            CreditInstallment::create([
                'credit_id' => $credit->id,
                'installment_number' => $i,
                'amount' => $amount,
                'due_date' => $dueDate,
                'paid_amount' => 0,
                'status' => 'pending',
            ]);

            $remainingBalance -= $amount;
        }

        return $credit;
    }

    /**
     * Apply a payment to a credit and distribute it among pending installments.
     */
    public function applyPayment(Credit $credit, float $amount, int $user_id, string $notes = null): array
    {
        if ($amount > $credit->balance) {
            throw new \Exception("El monto supera el saldo pendiente.");
        }

        return DB::transaction(function () use ($credit, $amount, $user_id, $notes) {
            // 1. Create credit payment record
            $payment = CreditPayment::create([
                'credit_id' => $credit->id,
                'user_id' => $user_id,
                'amount' => $amount,
                'payment_date' => now()->toDateString(),
                'notes' => $notes,
            ]);

            // 2. Distribute among installments
            $installmentsUpdated = 0;
            $amountToDistribute = $amount;

            $installments = $credit->installments()
                ->whereIn('status', ['pending', 'partial'])
                ->orderBy('installment_number')
                ->get();

            foreach ($installments as $installment) {
                if ($amountToDistribute <= 0) break;

                $remainingInInstallment = $installment->amount - $installment->paid_amount;

                if ($amountToDistribute >= $remainingInInstallment) {
                    // Pay this installment fully
                    $installment->paid_amount = $installment->amount;
                    $installment->paid_at = now();
                    $installment->status = 'paid';
                    $installment->save();

                    $amountToDistribute -= $remainingInInstallment;
                    $installmentsUpdated++;
                } else {
                    // Pay partially
                    $installment->paid_amount += $amountToDistribute;
                    $installment->status = 'partial';
                    $installment->save();

                    $amountToDistribute = 0;
                    $installmentsUpdated++;
                }
            }

            // 3. Update Credit
            $credit->paid_amount += $amount;
            $credit->balance -= $amount;

            if ($credit->balance <= 0) {
                $credit->status = 'paid';
            }

            $credit->save();

            // 4. Log
            ActivityLog::create([
                'user_id' => $user_id,
                'branch_id' => $credit->branch_id,
                'action' => 'credit.payment_applied',
                'model_type' => 'Credit',
                'model_id' => $credit->id,
                'description' => "Pago de Bs. {$amount} aplicado al crédito #{$credit->id}. Cuotas afectadas: {$installmentsUpdated}.",
                'ip_address' => request()->ip(),
            ]);

            return [
                'credit' => $credit,
                'installments_updated' => $installmentsUpdated,
            ];
        });
    }

    /**
     * Mark installments and credits as overdue if they are past their due date.
     */
    public function markOverdueInstallments(): int
    {
        $updatedInstallments = CreditInstallment::where('due_date', '<', today())
            ->whereIn('status', ['pending', 'partial'])
            ->update(['status' => 'overdue']);

        Credit::where('due_date', '<', today())
            ->where('status', 'active')
            ->update(['status' => 'overdue']);

        return $updatedInstallments;
    }

    /**
     * Check if all items in a PreSale have enough physical stock.
     */
    public function checkStockForPreSale(array $items, int $branch_id): bool
    {
        foreach ($items as $item) {
            $product = Product::find($item['product_id']);
            if ($product && $product->has_inventory) {
                if (!$this->inventoryService->hasStock($branch_id, $product->id, $item['quantity'])) {
                    return false;
                }
            }
        }
        return true;
    }
}
