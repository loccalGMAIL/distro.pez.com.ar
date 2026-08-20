<?php

namespace App\Filament\Clusters\Catalog;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class CatalogCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static ?string $navigationLabel = 'Catálogo';

    protected static ?string $clusterBreadcrumb = 'Catálogo';

    protected static ?int $navigationSort = 1;
}
