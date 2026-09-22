<?php

namespace App\Enums;

enum InventoryDocumentType: string
{
    case Receipt = 'receipt';
    case Issue = 'issue';
    case Adjustment = 'adjustment';
    case Waste = 'waste';
    case Transfer = 'transfer';
    case ReturnToSupplier = 'return_to_supplier';

    public function prefix(): string
    {
        return match ($this) {
            self::Receipt => 'PN',
            self::Issue => 'PX',
            self::Adjustment => 'DC',
            self::Waste => 'HH',
            self::Transfer => 'CK',
            self::ReturnToSupplier => 'TNCC',
        };
    }
}
