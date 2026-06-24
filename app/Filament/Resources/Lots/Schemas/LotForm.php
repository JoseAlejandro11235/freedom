<?php

namespace App\Filament\Resources\Lots\Schemas;

use App\Models\PurchaseStatus;
use App\Models\PurchaseLine;
use App\Support\LotPurchaseImporter;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class LotForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Lote')
                    ->columnSpanFull()
                    ->columns(2)
                    ->headerActions([
                        self::importFromPurchaseAction(),
                    ])
                    ->schema([
                        TextInput::make('lot_number')
                            ->label('Número de lote')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('Déjalo vacío para generar uno automáticamente.'),
                        DateTimePicker::make('received_at')
                            ->label('Fecha de recepción')
                            ->default(now())
                            ->seconds(false),
                        Repeater::make('lines')
                            ->label('Líneas de compra')
                            ->columnSpanFull()
                            ->schema([
                                Hidden::make('id'),
                                Select::make('purchase_line_id')
                                    ->label('Línea de compra')
                                    ->options(fn (Get $get): array => PurchaseLine::query()
                                        ->with(['purchase', 'product', 'size'])
                                        ->whereHas(
                                            'purchase.status',
                                            fn ($query) => $query->where('code', PurchaseStatus::PAID),
                                        )
                                        ->where(function ($query) use ($get): void {
                                            $query->where('pending_quantity', '>', 0);

                                            if (filled($get('purchase_line_id'))) {
                                                $query->orWhere('id', $get('purchase_line_id'));
                                            }
                                        })
                                        ->get()
                                        ->mapWithKeys(fn (PurchaseLine $line): array => [
                                            $line->id => self::purchaseLineLabel($line),
                                        ])
                                        ->all())
                                    ->searchable()
                                    ->required()
                                    ->native(false)
                                    ->live()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                    ->afterStateUpdated(function ($state, Set $set): void {
                                        $line = PurchaseLine::query()->with('product')->find($state);

                                        if ($line === null) {
                                            $set('quantity_received', null);

                                            return;
                                        }

                                        $quantity = max(1, (int) $line->pending_quantity);

                                        $set('quantity_received', $quantity);
                                    }),
                                TextInput::make('quantity_received')
                                    ->label('Cantidad recibida')
                                    ->numeric()
                                    ->minValue(1)
                                    ->required(),
                            ])
                            ->columns(2)
                            ->minItems(1)
                            ->defaultItems(1)
                            ->addable()
                            ->deletable()
                            ->reorderable(false),
                    ]),
            ]);
    }

    private static function purchaseLineLabel(PurchaseLine $line): string
    {
        $size = $line->size?->name !== null ? " · talla: {$line->size->name}" : '';

        return "{$line->purchase->purchase_id} · {$line->product->name}{$size} · pendiente: {$line->pending_quantity}";
    }

    protected static function importFromPurchaseAction(): Action
    {
        return Action::make('importFromPurchase')
            ->label('Importar de compra')
            ->icon('heroicon-o-arrow-down-on-square')
            ->color('gray')
            ->visible(fn (?object $record): bool => $record === null)
            ->modalHeading('Importar líneas de una compra')
            ->modalDescription('Selecciona una compra pagada con líneas pendientes. Se importarán todas sus líneas pendientes y reemplazarán las líneas actuales.')
            ->modalSubmitActionLabel('Importar líneas')
            ->schema([
                Select::make('purchase_id')
                    ->label('Compra')
                    ->options(fn (): array => LotPurchaseImporter::options())
                    ->searchable()
                    ->native(false)
                    ->required()
                    ->placeholder('Selecciona una compra')
                    ->helperText('Solo se muestran compras pagadas con líneas pendientes de recibir.'),
            ])
            ->action(function (array $data, Set $set): void {
                $items = LotPurchaseImporter::pendingLineItems($data['purchase_id'] ?? null);

                if ($items === []) {
                    Notification::make()
                        ->title('Sin líneas pendientes')
                        ->body('La compra seleccionada no tiene líneas pendientes de recibir.')
                        ->warning()
                        ->send();

                    return;
                }

                $set('lines', $items);

                Notification::make()
                    ->title('Líneas importadas')
                    ->body(count($items).' línea(s) pendiente(s) importada(s).')
                    ->success()
                    ->send();
            });
    }
}
