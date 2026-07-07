<?php

namespace App\Filament\Admin\Resources\Financial\Transactions\Actions;

use App\Filament\Admin\Resources\Financial\FixedCosts\FixedCostResource;
use App\Filament\Admin\Resources\Financial\FixedCosts\Schemas\FixedCostForm;
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
            ->label('erstelle Fixkosten')
            ->icon(Heroicon::Plus)
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
            $fixedCostId = FixedCost::query()->create(array_merge($data, [
                FixedCost::user_id => auth()->id(),
            ]))->id;

            Notification::make()
                ->title('Fixed cost created for transaction: ' . $record->id)
                ->success()
                ->send();

            redirect(FixedCostResource::getViewUrl($fixedCostId));
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
