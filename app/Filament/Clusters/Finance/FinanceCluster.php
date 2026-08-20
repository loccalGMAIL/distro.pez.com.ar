<?php

namespace App\Filament\Clusters\Finance;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class FinanceCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Finanzas';

    protected static ?string $clusterBreadcrumb = 'Finanzas';

    protected static ?int $navigationSort = 5;
}
