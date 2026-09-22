<?php

namespace App\Enums;

enum InventoryDocumentStatus: string
{
    case Draft = 'draft';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
}

