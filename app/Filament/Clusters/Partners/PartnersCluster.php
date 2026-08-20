<?php

namespace App\Filament\Clusters\Partners;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class PartnersCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Comercial';

    protected static ?string $clusterBreadcrumb = 'Comercial';

    protected static ?int $navigationSort = 2;
}
