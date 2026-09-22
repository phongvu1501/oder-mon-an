<?php

namespace App\Enums;

enum InventoryTransactionType: string
{
    case In = 'in';
    case Out = 'out';
    case Adjustment = 'adjustment';
    case Return = 'return';
    case AdjustmentIn = 'adjustment_in';
    case AdjustmentOut = 'adjustment_out';
    case TransferOut = 'transfer_out';
    case TransferIn = 'transfer_in';
    case StocktakeAdjustment = 'stocktake_adjustment';
    case Waste = 'waste';
    case ReturnToSupplier = 'return_to_supplier';
    case CustomerReturn = 'customer_return';

    public function direction(): string
    {
        return match ($this) {
            self::In, self::AdjustmentIn, self::TransferIn, self::CustomerReturn, self::Return => 'in',
            self::Out, self::AdjustmentOut, self::TransferOut, self::Waste, self::ReturnToSupplier => 'out',
            self::StocktakeAdjustment, self::Adjustment => 'mixed',
        };
    }

    public function prefix(): string
    {
        return match ($this) {
            self::In => 'RCV', self::Out => 'ISS',
            self::Adjustment, self::AdjustmentIn, self::AdjustmentOut => 'ADJ',
            self::TransferOut, self::TransferIn => 'TRF', self::StocktakeAdjustment => 'STK',
            self::Waste => 'WST', self::ReturnToSupplier => 'RTS', self::CustomerReturn, self::Return => 'CRT',
        };
    }
}
