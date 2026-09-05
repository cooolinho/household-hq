<?php

namespace App\Filament\Admin\Resources\Financial\TransactionCategories\Actions;

use App\Filament\Admin\Resources\Financial\TransactionCategories\TransactionCategoryResource;
use App\Models\Financial\TransactionCategory;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class CreateSubcategoryAction
{
    public static function make(): Action
    {
        $action = Action::make('createSubcategory')
            ->label('Unterkategorie erstellen')
            ->icon(Heroicon::Plus)
            ->schema([
                TextInput::make(TransactionCategory::name)
                    ->label('Name')
                    ->required()
                    ->maxLength(255)
                    ->autofocus(),
            ])
            ->modalHeading(fn(TransactionCategory $record): string => 'Unterkategorie für ' . $record->{TransactionCategory::name} . ' erstellen')
            ->modalSubmitActionLabel('Erstellen')
            ->modalCancelActionLabel('Abbrechen');

        $action
            ->extraModalFooterActions(function () use ($action): array {
                return [
                    $action->makeModalSubmitAction('createAndNew', arguments: ['mode' => 'new'])
                        ->label('Erstellen & Neu'),
                    $action->makeModalSubmitAction('createAndEdit', arguments: ['mode' => 'edit'])
                        ->label('Erstellen und Bearbeiten'),
                ];
            })
            ->action(function (
                array               $arguments,
                array               $data,
                Schema              $schema,
                TransactionCategory $record,
            ) use ($action): void {
                $subcategory = TransactionCategory::query()->create([
                    TransactionCategory::user_id => auth()->id(),
                    TransactionCategory::name => $data[TransactionCategory::name],
                    TransactionCategory::parent_id => $record->getKey(),
                    TransactionCategory::active => true,
                ]);

                Notification::make()
                    ->title('Unterkategorie erstellt')
                    ->success()
                    ->send();

                if (($arguments['mode'] ?? null) === 'new') {
                    $schema->fill();
                    $schema->dispatchClientSideStateReset();
                    $action->halt();
                }

                if (($arguments['mode'] ?? null) === 'edit') {
                    redirect(TransactionCategoryResource::getUrl('edit', [
                        'record' => $subcategory,
                    ]));
                }
            });

        return $action;
    }
}
