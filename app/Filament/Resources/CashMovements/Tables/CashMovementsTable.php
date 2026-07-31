<?php

namespace App\Filament\Resources\CashMovements\Tables;

use App\Enums\CashMovementSource;
use App\Enums\CashMovementType;
use App\Enums\SellingLineStatus;
use App\Enums\StockDocumentStatus;
use App\Models\CashMovement;
use App\Models\Selling;
use App\Models\SellingLine;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\Width;
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
                ViewAction::make()
                    ->label('Ver venta')
                    ->modalHeading(fn (CashMovement $record): string => 'Detalle de venta '.($record->meta['selling_id'] ?? ''))
                    ->modalWidth(Width::FourExtraLarge)
                    ->visible(fn (CashMovement $record): bool => $record->source === CashMovementSource::Selling)
                    ->schema(fn (CashMovement $record): array => static::sellingDetailSchema($record)),
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

    /**
     * @return array<int, Section>
     */
    private static function sellingDetailSchema(CashMovement $record): array
    {
        $selling = $record->sourceable;

        if (! $selling instanceof Selling) {
            return [
                Section::make('Venta')
                    ->schema([
                        TextEntry::make('missing')
                            ->label('')
                            ->state('No se encontró la venta asociada.'),
                    ]),
            ];
        }

        $selling->loadMissing([
            'customer.persona',
            'user',
            'lines.product',
            'lines.size',
            'lines.lotLine.lot',
        ]);

        return [
            Section::make('Venta')
                ->columns([
                    'default' => 2,
                ])
                ->schema([
                    TextEntry::make('selling_id')
                        ->label('Nº venta')
                        ->state($selling->selling_id),
                    TextEntry::make('customer_display')
                        ->label('Cliente')
                        ->state($selling->customer?->displayName() ?? '—'),
                    TextEntry::make('status')
                        ->label('Estado')
                        ->badge()
                        ->state($selling->status)
                        ->formatStateUsing(fn (StockDocumentStatus $state): string => $state->label())
                        ->color(fn (StockDocumentStatus $state): string => $state->color()),
                    TextEntry::make('user_name')
                        ->label('Usuario')
                        ->state($selling->user?->name ?? '—'),
                    TextEntry::make('created_at')
                        ->label('Creado')
                        ->state($selling->created_at?->format('d/m/Y H:i') ?? '—'),
                    TextEntry::make('total')
                        ->label('Total')
                        ->state('S/ '.number_format($selling->total(), 2))
                        ->weight('bold'),
                    TextEntry::make('note')
                        ->label('Nota')
                        ->state($selling->note ?: '—')
                        ->columnSpanFull(),
                ]),
            Section::make('Líneas')
                ->schema([
                    RepeatableEntry::make('lines')
                        ->label('')
                        ->state($selling->lines)
                        ->table([
                            TableColumn::make('Producto'),
                            TableColumn::make('Talla'),
                            TableColumn::make('Lote'),
                            TableColumn::make('Estado'),
                            TableColumn::make('Cantidad'),
                            TableColumn::make('Precio unitario'),
                        ])
                        ->schema([
                            TextEntry::make('product_display')
                                ->hiddenLabel()
                                ->state(fn (SellingLine $record): string => $record->product?->displayName() ?? '—'),
                            TextEntry::make('size.name')->hiddenLabel()->placeholder('—'),
                            TextEntry::make('lotLine.lot.lot_number')->hiddenLabel()->placeholder('—'),
                            TextEntry::make('state')
                                ->hiddenLabel()
                                ->formatStateUsing(fn (SellingLineStatus $state): string => $state->label()),
                            TextEntry::make('quantity')->hiddenLabel(),
                            TextEntry::make('unit_price')->hiddenLabel()->money('PEN'),
                        ]),
                ]),
        ];
    }
}
