<?php

namespace App\Filament\Admin\Clusters\Settings;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Support\Icons\Heroicon;

class SettingsCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;
    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Start;
    protected static ?string $clusterBreadcrumb = 'Einstellungen';
    protected static ?string $navigationLabel = 'Einstellungen';
}
