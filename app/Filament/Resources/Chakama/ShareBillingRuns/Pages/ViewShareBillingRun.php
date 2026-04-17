<?php

namespace App\Filament\Resources\Chakama\ShareBillingRuns\Pages;

use App\Filament\Resources\Chakama\ShareBillingRuns\ShareBillingRunResource;
use App\Jobs\ProcessShareBillingRunJob;
use App\Models\ShareBillingRun;
use Filament\Actions\Action;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ViewShareBillingRun extends ViewRecord
{
    protected static string $resource = ShareBillingRunResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('run_billing')
                ->label('Run Billing Now')
                ->icon(Heroicon::OutlinedBolt)
                ->color('success')
                ->visible(fn (): bool => $this->getRecord()->status === 'draft')
                ->requiresConfirmation()
                ->modalHeading('Run Share Billing')
                ->modalDescription(fn (): string => "This will generate invoices for all Chakama members with active allocations for '{$this->getRecord()->billingSchedule?->name}'.")
                ->action(function (): void {
                    ProcessShareBillingRunJob::dispatch($this->getRecord()->id);

                    Notification::make()
                        ->title('Billing run queued')
                        ->body('Invoices are being generated. You will be notified when complete.')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Billing Run Details')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('title')
                            ->columnSpanFull()
                            ->weight('bold'),
                        TextEntry::make('billingSchedule.name')
                            ->label('Schedule'),
                        TextEntry::make('billing_date')
                            ->label('Billing Date')
                            ->date('d M Y'),
                        TextEntry::make('due_date')
                            ->label('Due Date')
                            ->date('d M Y')
                            ->placeholder('30 days from billing date'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'draft' => 'gray',
                                'processing' => 'warning',
                                'completed' => 'success',
                                'failed' => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('member_count')
                            ->label('Members Billed')
                            ->numeric(),
                        TextEntry::make('total_invoiced')
                            ->label('Total Invoiced')
                            ->money('KES'),
                        TextEntry::make('processed_at')
                            ->label('Processed At')
                            ->dateTime('d M Y H:i')
                            ->placeholder('Not yet processed'),
                        IconEntry::make('notify_members')
                            ->label('In-app Notifications')
                            ->boolean(),
                        IconEntry::make('send_email')
                            ->label('Email Notifications')
                            ->boolean(),
                        TextEntry::make('createdBy.name')
                            ->label('Created By'),
                    ]),
                Section::make('Error Log')
                    ->visible(fn (ShareBillingRun $record): bool => filled($record->error_log))
                    ->schema([
                        TextEntry::make('error_log')
                            ->hiddenLabel()
                            ->columnSpanFull()
                            ->prose(),
                    ]),
            ]);
    }
}
