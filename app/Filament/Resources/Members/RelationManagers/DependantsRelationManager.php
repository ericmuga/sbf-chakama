<?php

namespace App\Filament\Resources\Members\RelationManagers;

use App\Models\Dependant;
use App\Models\Member;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class DependantsRelationManager extends RelationManager
{
    protected static string $relationship = 'dependants';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Select::make('identity_type')
                    ->label('Identity Type')
                    ->options([
                        'national_id' => 'National ID',
                        'passport_no' => 'Passport No',
                        'birth_cert_no' => 'Birth Certificate No',
                        'driving_licence_no' => 'Driving Licence No',
                        'pin_no' => 'PIN No',
                    ])
                    ->required(),
                TextInput::make('identity_no')
                    ->label('Identity Number')
                    ->required()
                    ->maxLength(50),
                TextInput::make('phone')
                    ->tel()
                    ->maxLength(20),
                TextInput::make('email')
                    ->email()
                    ->maxLength(255),
                DatePicker::make('date_of_birth')
                    ->label('Date of Birth'),
                TextInput::make('relationship')
                    ->maxLength(100),
                Section::make('Documents')
                    ->schema([
                        Repeater::make('documents')
                            ->relationship('documents')
                            ->schema([
                                Select::make('document_type')
                                    ->label('Document Type')
                                    ->options([
                                        'national_id' => 'National ID',
                                        'pin' => 'PIN Certificate',
                                        'passport' => 'Passport',
                                        'birth_cert' => 'Birth Certificate',
                                    ]),
                                TextInput::make('document_no')
                                    ->label('Document Number')
                                    ->maxLength(100),
                                FileUpload::make('file_path')
                                    ->label('File')
                                    ->disk('local')
                                    ->directory('member-documents')
                                    ->maxSize(5120),
                            ])
                            ->columns(3)
                            ->addActionLabel('Add Document'),
                    ])
                    ->visible(fn (string $operation): bool => $operation === 'edit')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('relationship')
                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('date_of_birth')
                    ->label('Date of Birth')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
                Action::make('downloadTemplate')
                    ->label('Download Template')
                    ->icon(Heroicon::ArrowDownTray)
                    ->color('gray')
                    ->url(route('admin.templates.dependants'))
                    ->openUrlInNewTab(),
                Action::make('import')
                    ->label('Import from CSV')
                    ->icon(Heroicon::ArrowUpTray)
                    ->schema([
                        FileUpload::make('file')
                            ->label('CSV File')
                            ->disk('local')
                            ->directory('imports')
                            ->acceptedFileTypes(['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'])
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $path = Storage::disk('local')->path($data['file']);
                        $handle = fopen($path, 'r');
                        $headers = fgetcsv($handle);

                        while (($row = fgetcsv($handle)) !== false) {
                            $record = array_combine($headers, $row);
                            $member = Member::where('no', $record['member_no'])->first();
                            if (! $member) {
                                continue;
                            }

                            Dependant::create(array_filter([
                                'member_id' => $member->id,
                                'name' => $record['name'] ?: null,
                                'identity_type' => $record['identity_type'] ?: null,
                                'identity_no' => $record['identity_no'] ?: null,
                                'phone' => $record['phone'] ?: null,
                                'email' => $record['email'] ?: null,
                                'date_of_birth' => $record['date_of_birth'] ?: null,
                                'relationship' => $record['relationship'] ?: null,
                            ], fn ($v) => $v !== null && $v !== ''));
                        }
                        fclose($handle);
                        Storage::disk('local')->delete($data['file']);
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
