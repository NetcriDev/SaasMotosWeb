<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class WorkOrderProduct extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'is_billable' => 'boolean',
            'line_total' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (WorkOrderProduct $line): void {
            $line->quantity = max(0, (float) $line->quantity);
            $line->unit_price = max(0, (float) $line->unit_price);
            $line->line_total = $line->is_billable ? round($line->quantity * $line->unit_price, 2) : 0;

            if ($line->work_order_id && blank($line->branch_id)) {
                $line->branch_id = WorkOrder::query()->whereKey($line->work_order_id)->value('branch_id');
            }

            self::ensureBelongsToOrderContext($line);
            self::ensureStockAvailable($line);
        });

        static::created(function (WorkOrderProduct $line): void {
            self::decreaseStock($line->inventory_product_id, $line->branch_id, (float) $line->quantity);
            $line->workOrder?->recalculateEstimatedTotal();
        });

        static::updated(function (WorkOrderProduct $line): void {
            self::restoreOriginalStock($line);
            self::decreaseStock($line->inventory_product_id, $line->branch_id, (float) $line->quantity);
            $line->workOrder?->recalculateEstimatedTotal();
        });

        static::deleted(function (WorkOrderProduct $line): void {
            self::restoreStock($line->inventory_product_id, $line->branch_id, (float) $line->quantity);
            $line->workOrder?->recalculateEstimatedTotal();
        });
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(InventoryProduct::class, 'inventory_product_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    private static function ensureStockAvailable(WorkOrderProduct $line): void
    {
        if (blank($line->inventory_product_id) || blank($line->branch_id)) {
            return;
        }

        $available = (float) InventoryStock::query()
            ->where('inventory_product_id', $line->inventory_product_id)
            ->where('branch_id', $line->branch_id)
            ->value('quantity');

        if ($line->exists && (int) $line->getOriginal('inventory_product_id') === (int) $line->inventory_product_id && (int) $line->getOriginal('branch_id') === (int) $line->branch_id) {
            $available += (float) $line->getOriginal('quantity');
        }

        if ($available < (float) $line->quantity) {
            throw ValidationException::withMessages([
                'inventory_product_id' => 'No hay stock suficiente para este producto en la sucursal de la orden.',
            ]);
        }
    }

    private static function ensureBelongsToOrderContext(WorkOrderProduct $line): void
    {
        if (blank($line->work_order_id) || blank($line->inventory_product_id) || blank($line->branch_id)) {
            return;
        }

        $order = WorkOrder::query()->find($line->work_order_id);
        $product = InventoryProduct::query()->find($line->inventory_product_id);

        if (! $order || ! $product || (int) $product->team_id !== (int) $order->team_id || (int) $line->branch_id !== (int) $order->branch_id) {
            throw ValidationException::withMessages([
                'inventory_product_id' => 'El producto debe pertenecer al taller y a la sucursal de la orden.',
            ]);
        }
    }

    private static function restoreOriginalStock(WorkOrderProduct $line): void
    {
        self::restoreStock(
            (int) $line->getOriginal('inventory_product_id'),
            (int) $line->getOriginal('branch_id'),
            (float) $line->getOriginal('quantity'),
        );
    }

    private static function decreaseStock(int $productId, int $branchId, float $quantity): void
    {
        self::adjustStock($productId, $branchId, -$quantity);
    }

    private static function restoreStock(int $productId, int $branchId, float $quantity): void
    {
        self::adjustStock($productId, $branchId, $quantity);
    }

    private static function adjustStock(int $productId, int $branchId, float $quantity): void
    {
        InventoryStock::query()
            ->where('inventory_product_id', $productId)
            ->where('branch_id', $branchId)
            ->increment('quantity', $quantity);
    }
}
