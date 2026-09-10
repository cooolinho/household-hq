<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->add('fixed_costs.matching_category_weight', 15);
        $this->migrator->add('fixed_costs.matching_category_mismatch_blocks_auto_link', true);
    }
};
