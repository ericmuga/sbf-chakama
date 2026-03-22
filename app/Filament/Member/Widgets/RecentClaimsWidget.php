<?php

namespace App\Filament\Member\Widgets;

use App\Models\Claim;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentClaimsWidget extends TableWidget
{
    protected static ?string $heading = 'Recent Claims';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $member = auth()->user()->member;

        return $table
            ->query(fn (): Builder => Claim::query()
                ->when($member, fn ($q) => $q->where('member_id', $member->id))
                ->latest()
                ->limit(5)
            )
            ->columns([
                TextColumn::make('no')
                    ->label('Claim No'),
                TextColumn::make('claim_type')
                    ->badge(),
                TextColumn::make('subject')
                    ->limit(30),
                TextColumn::make('claimed_amount')
                    ->label('Amount (KES)')
                    ->numeric(decimalPlaces: 2),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->date('d M Y'),
            ])
            ->filters([])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
