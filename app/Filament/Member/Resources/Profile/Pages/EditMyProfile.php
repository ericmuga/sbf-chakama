<?php

namespace App\Filament\Member\Resources\Profile\Pages;

use App\Filament\Member\Resources\Profile\MyProfileResource;
use App\Models\Member;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditMyProfile extends EditRecord
{
    protected static string $resource = MyProfileResource::class;

    public function mount(int|string|null $record = null): void
    {
        $member = auth()->user()?->member;

        abort_unless($member instanceof Member, 403);

        $this->record = $member;
        $this->fillForm();
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Profile updated');
    }

    protected function getRedirectUrl(): string
    {
        return MyProfileResource::getUrl('index');
    }

    protected function afterSave(): void
    {
        $member = $this->record;
        $user = $member->user;

        if (! $user) {
            return;
        }

        $userUpdates = [];

        if (filled($member->name) && $user->name !== $member->name) {
            $userUpdates['name'] = $member->name;
        }

        if (filled($member->email) && $user->email !== $member->email) {
            $userUpdates['email'] = $member->email;
        }

        if (! empty($userUpdates)) {
            $user->update($userUpdates);
        }
    }
}
