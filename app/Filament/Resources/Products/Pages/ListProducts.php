<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\ProductPreview;
use App\Services\ProductImportService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Support\Enums\Width;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    public function deleteProductPreviewRow(string $previewId): void
    {
        app(ProductImportService::class)->deletePreviewRow(auth()->user(), $previewId);
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->importProductsAction(),
            CreateAction::make(),
        ];
    }

    protected function importProductsAction(): Action
    {
        return Action::make('importProducts')
            ->label('Importar')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('gray')
            ->modalHeading('Importar productos')
            ->modalDescription('Sube un archivo Excel (.xlsx) con las columnas CODIGO y DESCRIPCION. Revisa la vista previa antes de confirmar.')
            ->modalWidth(Width::SevenExtraLarge)
            ->closeModalByClickingAway(false)
            ->steps([
                Step::make('Archivo')
                    ->description('Sube el archivo Excel (.xlsx).')
                    ->schema([
                        FileUpload::make('file')
                            ->label('Archivo Excel')
                            ->disk('local')
                            ->directory('imports/products')
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            ])
                            ->helperText('Solo archivos .xlsx. Columnas esperadas: CODIGO y DESCRIPCION.')
                            ->required(),
                    ])
                    ->afterValidation(function (Get $get, ProductImportService $importService): void {
                        $importService->stageFromFile(
                            auth()->user(),
                            $get('file'),
                        );
                    }),
                Step::make('Vista previa')
                    ->description('Revisa los productos antes de importarlos. Elimina los códigos que ya existen.')
                    ->schema([
                        View::make('filament.products.import-preview')
                            ->viewData(function (): array {
                                $rows = ProductPreview::query()
                                    ->forUser(auth()->user())
                                    ->orderBy('row_number')
                                    ->get();

                                return [
                                    'rows' => $rows,
                                    'invalidCount' => $rows->filter(fn (ProductPreview $row): bool => ! $row->isValid())->count(),
                                ];
                            }),
                    ]),
            ])
            ->modalSubmitAction(function (Action $action): Action {
                return $action
                    ->label('Confirmar importación')
                    ->disabled(fn (): bool => app(ProductImportService::class)->userHasInvalidPreviewRows(auth()->user()));
            })
            ->modalCancelAction(function (Action $action, ProductImportService $importService): Action {
                return $action
                    ->action(function () use ($importService): void {
                        $importService->clearForUser(auth()->user());
                    });
            })
            ->action(function (ProductImportService $importService): void {
                try {
                    $result = $importService->commit(auth()->user());

                    Notification::make()
                        ->title('Importación completada')
                        ->body("{$result['created']} producto(s) importado(s).")
                        ->success()
                        ->send();
                } catch (ValidationException $exception) {
                    Notification::make()
                        ->title('No se pudo importar')
                        ->body(collect($exception->errors())->flatten()->first())
                        ->danger()
                        ->send();

                    throw $exception;
                } catch (QueryException $exception) {
                    Notification::make()
                        ->title('No se pudo importar')
                        ->body('Ocurrió un error al guardar los productos. Revisa que no existan códigos duplicados.')
                        ->danger()
                        ->send();

                    throw $exception;
                }
            });
    }
}
