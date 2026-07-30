<?php

namespace App\Filament\Admin\Resources\Financial\Transactions\Actions;

use App\Filament\Admin\Resources\Financial\FixedCosts\FixedCostResource;
use App\Filament\Admin\Resources\Financial\FixedCosts\Schemas\FixedCostForm;
use App\Models\Enums\FixedCostEndsModeEnum;
use App\Models\Enums\FixedCostIntervalEnum;
use App\Models\Financial\FixedCost;
use App\Models\Financial\Transaction;
use Closure;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class CreateFixedCostAction
{
    public static function make(): Action
    {
        return Action::make('createFixedCost')
            ->label('Fixkosten erstellen & verknüpfen')
            ->icon(Heroicon::Plus)
            ->visible(fn(Transaction $record): bool => blank($record->fixed_cost_id))
            ->schema(self::schema())
            ->fillForm(self::fillForm())
            ->action(self::action());
    }

    /**
     * @return Closure
     */
    private static function action(): Closure
    {
        return function (Transaction $record, array $data) {
            /** @var FixedCost $fixedCost */
            $fixedCost = FixedCost::query()->create(array_merge($data, [
                FixedCost::user_id => auth()->id(),
            ]));

            // Transaktion direkt mit der neuen Fixkost verknüpfen
            $record->update([Transaction::fixed_cost_id => $fixedCost->id]);

            Notification::make()
                ->title('Fixkosten erstellt und Transaktion verknüpft.')
                ->success()
                ->send();

            redirect(FixedCostResource::getViewUrl($fixedCost->id));
        };
    }

    /**
     * @return Closure
     */
    private static function fillForm(): Closure
    {
        return function (Transaction $record) {
            return [
                FixedCost::name => $record->purpose,
                FixedCost::amount => $record->amount,
                FixedCost::interval => FixedCostIntervalEnum::MONTHLY->name,
                FixedCost::next_booking_date => now()->addMonth()->startOfMonth(),
                FixedCost::ends_mode => FixedCostEndsModeEnum::default(),
            ];
        };
    }

    /**
     * @return array
     */
    private static function schema(): array
    {
        return FixedCostForm::getSchema();
    }
}
