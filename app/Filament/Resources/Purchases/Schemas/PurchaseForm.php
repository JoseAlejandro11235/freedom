<?php

namespace App\Filament\Resources\Purchases\Schemas;

use App\Filament\Schemas\PurchaseOtherExpensesSchema;
use App\Filament\Schemas\StockDocumentLinesSchema;
use App\Models\Currency;
use App\Models\Provider;
use App\Models\PurchaseLineImport;
use App\Services\PurchaseLineImportService;
use App\Services\StockDocumentService;
use App\Support\PurchaseTotals;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;

class PurchaseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Compra')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('purchase_id')
                            ->label('Nº de compra')
                            ->default(fn (): string => app(StockDocumentService::class)->generatePurchaseId())
                            ->required()
                            ->maxLength(64)
                            ->unique(ignoreRecord: true)
                            ->disabled(fn (?object $record): bool => $record !== null && ! $record->isDraft())
                            ->dehydrated()
                            ->columnSpan(1),
                        Select::make('provider_id')
                            ->label('Proveedor')
                            ->options(fn (): array => Provider::query()
                                ->with('persona')
                                ->get()
                                ->sortBy(fn (Provider $provider): string => $provider->displayName())
                                ->mapWithKeys(fn (Provider $provider): array => [$provider->id => $provider->displayName()])
                                ->all())
                            ->searchable()
                            ->native(false)
                            ->placeholder('Selecciona un proveedor')
                            ->disabled(fn (?object $record): bool => $record !== null && ! $record->isDraft())
                            ->columnSpan(1),
                        Select::make('currency_id')
                            ->label('Moneda')
                            ->options(Currency::options())
                            ->default(fn (): string => Currency::base()->id)
                            ->required()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function (?string $state, Set $set): void {
                                if (Currency::resolve($state)->isBase()) {
                                    $set('exchange_rate', 1);
                                }
                            })
                            ->disabled(fn (?object $record): bool => $record !== null && ! $record->isDraft())
                            ->columnSpan(1),
                        TextInput::make('exchange_rate')
                            ->label('Tipo de cambio')
                            ->numeric()
                            ->minValue(0.0001)
                            ->step(0.0001)
                            ->default(1)
                            ->required()
                            ->live()
                            ->suffix('PEN')
                            ->helperText(fn (Get $get): string => '1 '.Currency::resolve($get('currency_id'))->code.' = X soles (PEN)')
                            ->disabled(fn (Get $get, ?object $record): bool => (
                                Currency::resolve($get('currency_id'))->isBase()
                                || ($record !== null && ! $record->isDraft())
                            ))
                            ->dehydrated()
                            ->columnSpan(1),
                        TextInput::make('status_display')
                            ->label('Estado')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($state, ?object $record): string => $record?->status?->label() ?? 'Borrador')
                            ->visible(fn (?object $record): bool => $record !== null)
                            ->columnSpan(1),
                        Textarea::make('note')
                            ->label('Nota')
                            ->rows(2)
                            ->disabled(fn (?object $record): bool => $record !== null && ! $record->isDraft())
                            ->columnSpanFull(),
                    ]),
                Section::make('Líneas')
                    ->columnSpanFull()
                    ->headerActions([
                        self::importLinesAction(),
                    ])
                    ->schema(StockDocumentLinesSchema::repeater(
                        'unit_cost',
                        'Costo unitario',
                        live: true,
                        currencyField: 'currency_id',
                    )),
                Section::make('Otros gastos')
                    ->columnSpanFull()
                    ->schema(PurchaseOtherExpensesSchema::repeater()),
                Section::make('Total')
                    ->columnSpanFull()
                    ->schema([
                        Placeholder::make('subtotal_lines')
                            ->label('Subtotal productos')
                            ->content(fn (Get $get): string => PurchaseTotals::formatMoney(
                                PurchaseTotals::linesSubtotal($get('lines')),
                                $get('currency_id'),
                            )),
                        Placeholder::make('subtotal_expenses')
                            ->label('Subtotal otros gastos')
                            ->content(fn (Get $get): string => PurchaseTotals::formatMoney(
                                PurchaseTotals::expensesSubtotal($get('other_expenses')),
                                $get('currency_id'),
                            )),
                        Placeholder::make('purchase_total')
                            ->label('Total')
                            ->content(fn (Get $get): string => PurchaseTotals::formattedTotalFromGet($get))
                            ->extraAttributes(['class' => 'text-lg font-semibold']),
                        Placeholder::make('purchase_total_pen')
                            ->label('Total en soles (PEN)')
                            ->content(fn (Get $get): string => PurchaseTotals::formattedTotalInPenFromGet($get))
                            ->visible(fn (Get $get): bool => ! Currency::resolve($get('currency_id'))->isBase())
                            ->extraAttributes(['class' => 'text-lg font-semibold']),
                    ]),
            ]);
    }

    protected static function importLinesAction(): Action
    {
        return Action::make('importPurchaseLines')
            ->label('Importar líneas')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('gray')
            ->visible(fn (?object $record): bool => $record === null || $record->isDraft())
            ->modalHeading('Importar líneas')
            ->modalDescription('Sube un archivo Excel (.xlsx) con las columnas CODIGO, DESCRIPCION, CANTIDAD y PRECIO. Las líneas actuales serán reemplazadas.')
            ->modalWidth(Width::SevenExtraLarge)
            ->closeModalByClickingAway(false)
            ->steps([
                Step::make('Archivo')
                    ->description('Sube el archivo Excel (.xlsx).')
                    ->schema([
                        FileUpload::make('file')
                            ->label('Archivo Excel')
                            ->disk('local')
                            ->directory('imports/purchase-lines')
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            ])
                            ->helperText('Solo archivos .xlsx. Columnas esperadas: CODIGO, DESCRIPCION, CANTIDAD y PRECIO.')
                            ->required(),
                    ])
                    ->afterValidation(function (Get $get, PurchaseLineImportService $importService): void {
                        $importService->stageFromFile(
                            auth()->user(),
                            $get('file'),
                        );
                    }),
                Step::make('Vista previa')
                    ->description('Revisa las líneas antes de reemplazar las actuales.')
                    ->schema([
                        View::make('filament.purchases.line-import-preview')
                            ->viewData(function (PurchaseLineImportService $importService): array {
                                $rows = $importService->rowsForUser(auth()->user());

                                return [
                                    'rows' => $rows,
                                    'duplicateCount' => $rows->where('is_duplicate', true)->count(),
                                    'notFoundCount' => $rows->whereNull('product_id')->count(),
                                    'importableCount' => $rows->filter(fn (PurchaseLineImport $row): bool => $row->isImportable())->count(),
                                ];
                            }),
                    ]),
            ])
            ->modalSubmitAction(fn (Action $action): Action => $action->label('Importar líneas'))
            ->modalCancelAction(function (Action $action, PurchaseLineImportService $importService): Action {
                return $action->action(function () use ($importService): void {
                    $importService->clearForUser(auth()->user());
                });
            })
            ->action(function (PurchaseLineImportService $importService, Set $set): void {
                $items = $importService->toRepeaterItems(auth()->user());
                $importService->clearForUser(auth()->user());

                if ($items === []) {
                    Notification::make()
                        ->title('No se importaron líneas')
                        ->body('Ningún código del archivo coincide con un producto existente.')
                        ->warning()
                        ->send();

                    return;
                }

                $set('lines', $items);

                Notification::make()
                    ->title('Líneas importadas')
                    ->body(count($items).' línea(s) importada(s).')
                    ->success()
                    ->send();
            });
    }
}
