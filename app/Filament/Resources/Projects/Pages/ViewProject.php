<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Enums\DirectCostType;
use App\Enums\ProjectMemberRole;
use App\Enums\ProjectStatus;
use App\Filament\Resources\Finance\PurchaseHeaders\PurchaseHeaderResource;
use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Widgets\Projects\BudgetVsActualChart;
use App\Filament\Widgets\Projects\CostBreakdownChart;
use App\Filament\Widgets\Projects\MonthlySpendTrend;
use App\Filament\Widgets\Projects\ProjectStatsWidget;
use App\Models\Finance\BankAccount;
use App\Models\Finance\GlAccount;
use App\Models\Project;
use App\Models\ProjectAttachment;
use App\Models\User;
use App\Services\ProjectCostService;
use App\Services\ProjectService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;

class ViewProject extends ViewRecord
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('create_purchase_order')
                ->label('Purchase Invoice')
                ->icon('heroicon-o-shopping-cart')
                ->url(fn (Project $record): string => PurchaseHeaderResource::getUrl('create', [
                    'project' => $record->id,
                ])),
            Action::make('add_direct_cost')
                ->label('Direct Cost')
                ->icon('heroicon-o-banknotes')
                ->schema([
                    Textarea::make('description')
                        ->required()
                        ->columnSpanFull(),
                    TextInput::make('amount')
                        ->numeric()
                        ->required()
                        ->minValue(0),
                    Select::make('cost_type')
                        ->options(DirectCostType::class)
                        ->default(DirectCostType::Other->value)
                        ->required(),
                    Select::make('gl_account_no')
                        ->label('Expense G/L Account')
                        ->options(
                            GlAccount::query()
                                ->where('account_type', 'Posting')
                                ->orderBy('no')
                                ->get()
                                ->mapWithKeys(fn (GlAccount $account): array => [$account->no => $account->no.' - '.$account->name])
                        )
                        ->searchable()
                        ->required(),
                    Select::make('bank_account_id')
                        ->label('Paying Bank Account')
                        ->options(BankAccount::query()->orderBy('name')->pluck('name', 'id'))
                        ->searchable(),
                    TextInput::make('receipt_number')
                        ->maxLength(100),
                    FileUpload::make('receipt_path')
                        ->disk('public')
                        ->directory('project-receipts/'.$this->getRecord()->no)
                        ->visibility('public')
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png']),
                    DatePicker::make('posting_date')
                        ->default(today())
                        ->required(),
                ])
                ->action(function (Project $record, array $data): void {
                    app(ProjectCostService::class)->submitDirectCost($record, $data, auth()->user());

                    Notification::make()
                        ->success()
                        ->title('Project direct cost captured.')
                        ->send();
                }),
            Action::make('add_milestone')
                ->label('Milestone')
                ->icon('heroicon-o-flag')
                ->schema([
                    TextInput::make('title')
                        ->required()
                        ->maxLength(255),
                    Textarea::make('description')
                        ->rows(3)
                        ->columnSpanFull(),
                    DatePicker::make('due_date'),
                    TextInput::make('sort_order')
                        ->numeric()
                        ->default(0),
                ])
                ->action(function (Project $record, array $data): void {
                    $record->milestones()->create($data);

                    Notification::make()
                        ->success()
                        ->title('Project milestone added.')
                        ->send();
                }),
            Action::make('add_member')
                ->label('Member')
                ->icon('heroicon-o-user-plus')
                ->schema([
                    Select::make('user_id')
                        ->label('User')
                        ->options(User::query()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->required(),
                    Select::make('role')
                        ->options(ProjectMemberRole::class)
                        ->default(ProjectMemberRole::Contributor->value)
                        ->required(),
                ])
                ->action(function (Project $record, array $data): void {
                    $member = User::query()->findOrFail($data['user_id']);

                    app(ProjectService::class)->addMember(
                        $record,
                        $member,
                        $data['role'] instanceof ProjectMemberRole ? $data['role'] : ProjectMemberRole::from($data['role']),
                        auth()->user(),
                    );

                    Notification::make()
                        ->success()
                        ->title('Project member saved.')
                        ->body('Newly assigned members receive a notification.')
                        ->send();
                }),
            Action::make('upload_attachment')
                ->label('Attachment')
                ->icon('heroicon-o-paper-clip')
                ->schema([
                    FileUpload::make('file_path')
                        ->disk('public')
                        ->directory('project-attachments/'.$this->getRecord()->no)
                        ->visibility('public')
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                        ->required(),
                ])
                ->action(function (Project $record, array $data): void {
                    $path = $data['file_path'];

                    ProjectAttachment::create([
                        'project_id' => $record->id,
                        'uploaded_by' => auth()->id(),
                        'file_name' => basename($path),
                        'file_path' => $path,
                        'file_size' => (int) Storage::disk('public')->size($path),
                        'mime_type' => (string) Storage::disk('public')->mimeType($path),
                    ]);

                    Notification::make()
                        ->success()
                        ->title('Project attachment uploaded.')
                        ->send();
                }),
            Action::make('change_status')
                ->label('Change Status')
                ->icon(Heroicon::OutlinedArrowPath)
                ->visible(function (Project $record): bool {
                    return count(ProjectStatus::allowedTransitions($record->status)) > 0;
                })
                ->schema(function (Project $record): array {
                    $transitions = ProjectStatus::allowedTransitions($record->status);
                    $options = collect($transitions)
                        ->mapWithKeys(fn (ProjectStatus $status) => [$status->value => $status->label()])
                        ->all();

                    return [
                        Select::make('new_status')
                            ->options($options)
                            ->required(),
                        Textarea::make('reason')
                            ->label('Reason (optional)'),
                    ];
                })
                ->action(function (Project $record, array $data): void {
                    app(ProjectService::class)->changeStatus(
                        $record,
                        ProjectStatus::from($data['new_status']),
                        auth()->user(),
                        $data['reason'] ?? null,
                    );

                    Notification::make()->success()->title('Status updated.')->send();

                    $this->refreshFormData(['status']);
                }),
        ];
    }

    public function getHeaderWidgets(): array
    {
        return [
            ProjectStatsWidget::make([
                'record' => $this->getRecord(),
            ]),
        ];
    }

    public function getFooterWidgets(): array
    {
        return [
            BudgetVsActualChart::make([
                'record' => $this->getRecord(),
            ]),
            CostBreakdownChart::make([
                'record' => $this->getRecord(),
            ]),
            MonthlySpendTrend::make([
                'record' => $this->getRecord(),
            ]),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Overview')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('no'),
                        TextEntry::make('module')
                            ->badge(),
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('priority')
                            ->badge(),
                        TextEntry::make('budget')
                            ->numeric(decimalPlaces: 2),
                        TextEntry::make('spent')
                            ->numeric(decimalPlaces: 2),
                        TextEntry::make('utilisation_percent')
                            ->label('Utilisation %')
                            ->state(fn (Project $record): float => $record->utilisationPercent()),
                        TextEntry::make('start_date')
                            ->date(),
                        TextEntry::make('due_date')
                            ->date(),
                        TextEntry::make('completed_at')
                            ->dateTime(),
                        TextEntry::make('creator.name')
                            ->label('Created By'),
                        TextEntry::make('members_count')
                            ->label('Members')
                            ->state(fn (Project $record): int => $record->members()->count()),
                        TextEntry::make('milestones_count')
                            ->label('Milestones')
                            ->state(fn (Project $record): int => $record->milestones()->count()),
                        TextEntry::make('attachments_count')
                            ->label('Attachments')
                            ->state(fn (Project $record): int => $record->attachments()->count()),
                        TextEntry::make('description')
                            ->columnSpanFull(),
                    ]),
                Section::make('Progress')
                    ->schema([
                        TextEntry::make('utilisation_percent')
                            ->label('Utilisation %')
                            ->state(fn (Project $record): float => $record->utilisationPercent())
                            ->suffix('%'),
                    ]),
            ]);
    }
}
