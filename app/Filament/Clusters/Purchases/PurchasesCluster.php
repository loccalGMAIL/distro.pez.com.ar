<?php

namespace App\Filament\Clusters\Purchases;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class PurchasesCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static ?string $navigationLabel = 'Compras';

    protected static ?string $clusterBreadcrumb = 'Compras';

    protected static ?int $navigationSort = 3;
}
