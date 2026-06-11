<?php

namespace App\Filament\Schemas;

use App\Enums\SellingLineStatus;
use App\Models\Currency;
use App\Models\LotLine;
use App\Models\Product;
use App\Models\Size;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class StockDocumentLinesSchema
{
    /**
     * @return array<int, Repeater|Select|TextInput>
     */
    public static function repeater(
        string $unitPriceField,
        string $unitPriceLabel,
        bool $live = false,
        ?string $currencyField = null,
        bool $withLotSelection = false,
    ): array {
        $lineSchema = [];

        if ($withLotSelection) {
            $lineSchema[] = Hidden::make('product_id')->dehydrated();
            $lineSchema[] = Hidden::make('size_id')->dehydrated();
            $lineSchema[] = Hidden::make('state')
                ->default(SellingLineStatus::Pending->value)
                ->dehydrated();
            $lineSchema[] = Select::make('lot_line_id')
                ->label('Lote')
                ->options(fn (Get $get): array => LotLine::query()
                    ->with(['lot', 'product', 'purchaseLine.size'])
                    ->where(function ($query) use ($get): void {
                        $query->where('lot_lines.quantity_available', '>', 0);

                        if (filled($get('lot_line_id'))) {
                            $query->orWhere('lot_lines.id', $get('lot_line_id'));
                        }
                    })
                    ->join('lots', 'lots.id', '=', 'lot_lines.lot_id')
                    ->orderBy('lots.received_at')
                    ->select('lot_lines.*')
                    ->get()
                    ->mapWithKeys(fn (LotLine $lotLine): array => [$lotLine->id => $lotLine->displayName()])
                    ->all())
                ->searchable()
                ->native(false)
                ->required()
                ->live()
                ->afterStateUpdated(function ($state, Set $set): void {
                    $lotLine = filled($state)
                        ? LotLine::query()->with('purchaseLine')->find($state)
                        : null;

                    $set('product_id', $lotLine?->product_id);
                    $set('size_id', $lotLine?->purchaseLine?->size_id);
                    $set(
                        'state',
                        filled($state) ? SellingLineStatus::Assigned->value : SellingLineStatus::Pending->value,
                    );
                })
                ->disabled(fn (?object $record): bool => $record !== null && ! $record->isDraft());
        } else {
            $lineSchema[] = Select::make('product_id')
                    ->label('Producto')
                    ->options(fn (): array => Product::query()
                        ->where('track_inventory', true)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->required()
                    ->afterStateUpdated(fn (Set $set): mixed => $set('size_id', null))
                    ->disabled(fn (?object $record): bool => $record !== null && ! $record->isDraft());
            $lineSchema[] = Select::make('size_id')
                ->label('Talla')
                ->options(fn (): array => Size::query()
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->all())
                ->searchable()
                ->nullable()
                ->native(false)
                ->disabled(fn (?object $record): bool => $record !== null && ! $record->isDraft());
        }

        $lineSchema[] = TextInput::make('quantity')
            ->label('Cantidad')
            ->numeric()
            ->minValue(1)
            ->required()
            ->default(1)
            ->disabled(fn (?object $record): bool => $record !== null && ! $record->isDraft());

        $lineSchema[] = TextInput::make($unitPriceField)
            ->label($unitPriceLabel)
            ->numeric()
            ->prefix(
                $currencyField !== null
                    ? fn (Get $get): string => Currency::symbolFromFormState(
                        $get($currencyField) ?? $get('../../'.$currencyField),
                    )
                    : null,
            )
            ->minValue(0)
            ->nullable()
            ->disabled(fn (?object $record): bool => $record !== null && ! $record->isDraft());

        $repeater = Repeater::make('lines')
                ->label('Productos')
                ->columnSpanFull()
                ->schema($lineSchema)
                ->columns($withLotSelection ? 3 : 4)
                ->minItems(1)
                ->defaultItems(1)
                ->columnSpanFull()
                ->deletable(fn (?object $record): bool => $record === null || $record->isDraft())
                ->addable(fn (?object $record): bool => $record === null || $record->isDraft())
                ->reorderable(fn (?object $record): bool => $record === null || $record->isDraft());

        if ($live) {
            $repeater->live();
        }

        return [$repeater];
    }

}
