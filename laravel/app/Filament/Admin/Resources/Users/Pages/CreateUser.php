<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\Actions\ResendVerificationEmailAction;
use App\Filament\Admin\Resources\Users\Schemas\UserForm;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    private bool $shouldSendVerificationEmail = false;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $markEmailVerified = (bool) ($data[UserForm::FIELD_MARK_EMAIL_VERIFIED] ?? true);
        unset($data[UserForm::FIELD_MARK_EMAIL_VERIFIED]);

        $this->shouldSendVerificationEmail = !$markEmailVerified;
        $data[User::email_verified_at] = $markEmailVerified ? now() : null;

        return $data;
    }

    protected function afterCreate(): void
    {
        if (!$this->shouldSendVerificationEmail) {
            return;
        }

        /** @var User $user */
        $user = $this->record;

        ResendVerificationEmailAction::send($user);
    }
}
