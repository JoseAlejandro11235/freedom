import { Sheet, SheetClose, SheetContent } from '@/components/ui/sheet';
import type { SharedData } from '@/types';
import { Link, router, usePage } from '@inertiajs/react';
import { Minus, Plus, Trash2, X } from 'lucide-react';

interface CartDrawerProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

export function CartDrawer({ open, onOpenChange }: CartDrawerProps) {
    const { cart } = usePage<SharedData>().props;
    const items = cart?.items ?? [];
    const count = cart?.count ?? 0;
    const subtotal = cart?.subtotal ?? 0;

    const updateQuantity = (productId: string, quantity: number) => {
        router.patch(
            `/cart/items/${productId}`,
            { quantity },
            { preserveScroll: true, preserveState: true },
        );
    };

    const removeItem = (productId: string) => {
        router.delete(`/cart/items/${productId}`, {
            preserveScroll: true,
            preserveState: true,
        });
    };

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent
                side="right"
                className="flex h-full w-[90%] max-w-md flex-col gap-0 overflow-hidden border-l border-neutral-200 bg-white p-0 text-neutral-900 [&>button]:hidden"
            >
                <div className="flex shrink-0 items-center justify-between border-b border-neutral-200 px-4 py-4">
                    <h2 className="text-sm font-semibold tracking-wide uppercase">
                        Carrito ({count})
                    </h2>
                    <SheetClose asChild>
                        <button type="button" className="p-1 text-neutral-600 hover:text-black" aria-label="Cerrar">
                            <X className="h-5 w-5" />
                        </button>
                    </SheetClose>
                </div>

                {items.length === 0 ? (
                    <div className="flex flex-1 flex-col items-center justify-center gap-3 px-6 text-center">
                        <p className="text-sm text-neutral-600">Tu carrito está vacío.</p>
                        <SheetClose asChild>
                            <button
                                type="button"
                                className="border border-black bg-black px-6 py-2.5 text-xs font-semibold tracking-wide text-white uppercase"
                            >
                                Seguir comprando
                            </button>
                        </SheetClose>
                    </div>
                ) : (
                    <>
                        <ul className="flex-1 space-y-4 overflow-y-auto px-4 py-4">
                            {items.map((item) => (
                                <li key={item.id} className="flex gap-3">
                                    <div className="h-20 w-16 shrink-0 overflow-hidden bg-white">
                                        {item.image ? (
                                            <img
                                                src={item.image}
                                                alt={item.name}
                                                className={`h-full w-full ${
                                                    item.imageFit === 'cover' ? 'object-cover' : 'object-contain p-1'
                                                }`}
                                            />
                                        ) : null}
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate text-[10px] font-semibold tracking-widest text-neutral-500 uppercase">
                                            {item.brand}
                                        </p>
                                        <p className="mt-0.5 line-clamp-2 text-sm leading-snug text-neutral-900">
                                            {item.name}
                                        </p>
                                        <p className="mt-1 text-sm font-semibold">S/{item.price.toFixed(2)}</p>
                                        <div className="mt-2 flex items-center justify-between gap-2">
                                            <div className="flex items-center border border-neutral-300">
                                                <button
                                                    type="button"
                                                    className="p-1.5 text-neutral-700 hover:bg-neutral-50 disabled:opacity-40"
                                                    aria-label="Disminuir cantidad"
                                                    disabled={item.quantity <= 1}
                                                    onClick={() => updateQuantity(item.id, item.quantity - 1)}
                                                >
                                                    <Minus className="h-3.5 w-3.5" />
                                                </button>
                                                <span className="min-w-8 text-center text-xs font-medium">
                                                    {item.quantity}
                                                </span>
                                                <button
                                                    type="button"
                                                    className="p-1.5 text-neutral-700 hover:bg-neutral-50 disabled:opacity-40"
                                                    aria-label="Aumentar cantidad"
                                                    disabled={
                                                        item.maxQuantity !== null && item.quantity >= item.maxQuantity
                                                    }
                                                    onClick={() => updateQuantity(item.id, item.quantity + 1)}
                                                >
                                                    <Plus className="h-3.5 w-3.5" />
                                                </button>
                                            </div>
                                            <button
                                                type="button"
                                                className="p-1.5 text-neutral-500 hover:text-black"
                                                aria-label="Quitar del carrito"
                                                onClick={() => removeItem(item.id)}
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </button>
                                        </div>
                                    </div>
                                </li>
                            ))}
                        </ul>

                        <div className="shrink-0 border-t border-neutral-200 px-4 py-4">
                            <div className="mb-4 flex items-center justify-between text-sm">
                                <span className="text-neutral-600">Subtotal</span>
                                <span className="font-semibold">S/{subtotal.toFixed(2)}</span>
                            </div>
                            <SheetClose asChild>
                                <Link
                                    href="/checkout"
                                    className="block w-full border border-black bg-black px-4 py-3 text-center text-xs font-semibold tracking-wide text-white uppercase hover:bg-neutral-800"
                                >
                                    Finalizar compra
                                </Link>
                            </SheetClose>
                        </div>
                    </>
                )}
            </SheetContent>
        </Sheet>
    );
}
