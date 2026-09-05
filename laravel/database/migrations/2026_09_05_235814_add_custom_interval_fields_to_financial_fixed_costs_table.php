<?php

use App\Models\Enums\FixedCostIntervalEnum;
use App\Models\Enums\FixedCostIntervalUnitEnum;
use App\Models\Financial\FixedCost;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table(FixedCost::TABLE, function (Blueprint $table) {
            $table->unsignedInteger(FixedCost::custom_interval_value)
                ->nullable()
                ->after(FixedCost::interval);
            $table->enum(FixedCost::custom_interval_unit, FixedCostIntervalUnitEnum::allNames())
                ->nullable()
                ->after(FixedCost::custom_interval_value);
            $table->unsignedInteger(FixedCost::custom_extended_interval_value)
                ->nullable()
                ->after(FixedCost::extended_interval);
            $table->enum(FixedCost::custom_extended_interval_unit, FixedCostIntervalUnitEnum::allNames())
                ->nullable()
                ->after(FixedCost::custom_extended_interval_value);
        });

        self::alterIntervalEnum(FixedCost::interval, false);
        self::alterIntervalEnum(FixedCost::extended_interval, true);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        self::alterIntervalEnum(FixedCost::interval, false, false);
        self::alterIntervalEnum(FixedCost::extended_interval, true, false);

        Schema::table(FixedCost::TABLE, function (Blueprint $table) {
            $table->dropColumn([
                FixedCost::custom_interval_value,
                FixedCost::custom_interval_unit,
                FixedCost::custom_extended_interval_value,
                FixedCost::custom_extended_interval_unit,
            ]);
        });
    }

    private static function alterIntervalEnum(string $column, bool $hasDefault, bool $includeCustom = true): void
    {
        $names = FixedCostIntervalEnum::allNames();

        if (!$includeCustom) {
            $names = array_values(array_filter(
                $names,
                static fn(string $name): bool => $name !== FixedCostIntervalEnum::CUSTOM->name,
            ));
        }

        $values = implode("','", $names);
        $default = $hasDefault ? " DEFAULT '" . FixedCostIntervalEnum::default() . "'" : '';

        DB::statement(sprintf(
            "ALTER TABLE `%s` MODIFY `%s` ENUM('%s') NOT NULL%s",
            FixedCost::TABLE,
            $column,
            $values,
            $default,
        ));
    }
};
