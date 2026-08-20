<?php

namespace App\Filament\Clusters\Sales;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class SalesCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static ?string $navigationLabel = 'Ventas';

    protected static ?string $clusterBreadcrumb = 'Ventas';

    protected static ?int $navigationSort = 4;

    protected static bool $shouldRegisterSubNavigation = false;
}
