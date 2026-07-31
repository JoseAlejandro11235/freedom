<?php

namespace App\Filament\Resources\CashMovements\Tables;

use App\Enums\CashMovementSource;
use App\Enums\CashMovementType;
use App\Models\CashMovement;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CashMovementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('occurred_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (CashMovementType $state): string => $state->label())
                    ->color(fn (CashMovementType $state): string => $state->color()),
                TextColumn::make('description')
                    ->label('Descripción')
                    ->searchable()
                    ->wrap()
                    ->limit(60),
                TextColumn::make('source')
                    ->label('Origen')
                    ->badge()
                    ->formatStateUsing(fn (CashMovementSource $state): string => $state->label())
                    ->color(fn (CashMovementSource $state): string => match ($state) {
                        CashMovementSource::Manual => 'gray',
                        CashMovementSource::Selling => 'success',
                        CashMovementSource::Order => 'info',
                        CashMovementSource::Purchase => 'warning',
                    }),
                TextColumn::make('amount')
                    ->label('Monto')
                    ->alignEnd()
                    ->sortable()
                    ->formatStateUsing(function ($state, CashMovement $record): string {
                        $prefix = $record->isIncome() ? '+' : '−';

                        return $prefix.' S/ '.number_format((float) $state, 2);
                    })
                    ->color(fn (CashMovement $record): string => $record->isIncome() ? 'success' : 'danger'),
                TextColumn::make('user.name')
                    ->label('Usuario')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('occurred_at', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options(CashMovementType::options()),
                SelectFilter::make('source')
                    ->label('Origen')
                    ->options(CashMovementSource::options()),
                Filter::make('occurred_at')
                    ->label('Rango de fechas')
                    ->schema([
                        DatePicker::make('from')->label('Desde'),
                        DatePicker::make('until')->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('occurred_at', '>=', $date),
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('occurred_at', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (CashMovement $record): bool => $record->isManual()),
                DeleteAction::make()
                    ->visible(fn (CashMovement $record): bool => $record->isManual()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->using(function (Collection $records): void {
                            $records
                                ->filter(fn (CashMovement $record): bool => $record->isManual())
                                ->each(fn (CashMovement $record) => $record->delete());
                        }),
                ]),
            ]);
    }
}
