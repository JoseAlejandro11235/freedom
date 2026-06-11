import type { SentuaProduct } from '@/types/sentua';
import { ShoppingBag } from 'lucide-react';

interface ProductCardProps {
    product: SentuaProduct;
}

export function ProductCard({ product }: ProductCardProps) {
    const hasDiscount = product.originalPrice && product.originalPrice > product.price;
    const inStock = product.inStock !== false;

    return (
        <article className="group flex h-full min-w-0 flex-col overflow-hidden">
            <a href={product.href} className="relative block overflow-hidden bg-[#f5f5f5]">
                {product.badge && (
                    <div className="absolute top-2 left-2 z-10 flex max-w-[calc(100%-0.5rem)] flex-col gap-1">
                        <span className="w-fit max-w-full truncate bg-black px-1.5 py-0.5 text-[9px] font-semibold tracking-wide text-white uppercase sm:px-2 sm:text-[10px]">
                            {product.badge}
                        </span>
                        {product.discount && (
                            <span className="w-fit bg-[#c41e3a] px-1.5 py-0.5 text-[9px] font-bold text-white sm:px-2 sm:text-[10px]">
                                {product.discount}%
                            </span>
                        )}
                        {product.exclusiveWeb && (
                            <span className="w-fit max-w-full truncate bg-white px-1.5 py-0.5 text-[8px] font-medium text-black ring-1 ring-black/10 sm:text-[9px]">
                                Exclusivo web
                            </span>
                        )}
                        {!inStock && (
                            <span className="w-fit bg-neutral-700 px-1.5 py-0.5 text-[9px] font-semibold text-white uppercase sm:px-2 sm:text-[10px]">
                                Agotado
                            </span>
                        )}
                    </div>
                )}
                <img
                    src={product.image}
                    alt={product.name}
                    className={`aspect-[4/5] w-full transition-transform duration-500 group-hover:scale-105 ${
                        product.imageFit === 'contain' ? 'object-contain p-4' : 'object-cover'
                    }`}
                    loading="lazy"
                />
            </a>

            <div className="flex min-w-0 flex-1 flex-col pt-3">
                <p className="truncate text-[11px] font-semibold tracking-widest text-neutral-500 uppercase">{product.brand}</p>
                <a
                    href={product.href}
                    className="mt-1 line-clamp-2 min-h-10 min-w-0 text-sm leading-snug text-neutral-900 hover:underline"
                >
                    {product.name}
                </a>
                <p className="mt-1 min-h-4 text-xs text-neutral-500">{product.size ?? '\u00A0'}</p>

                <div className="mt-2 flex min-h-10 flex-wrap items-baseline gap-2">
                    {hasDiscount && (
                        <span className="text-xs text-neutral-400 line-through">S/{product.originalPrice!.toFixed(2)}</span>
                    )}
                    <span className="text-base font-semibold text-neutral-900">S/{product.price.toFixed(2)}</span>
                </div>

                <button
                    type="button"
                    disabled={!inStock}
                    className="mt-3 mt-auto flex w-full min-w-0 max-w-full items-center justify-center gap-1 border border-black bg-black px-1 py-2.5 text-[10px] leading-tight font-semibold tracking-wide whitespace-normal text-white uppercase transition-colors enabled:hover:bg-neutral-800 disabled:cursor-not-allowed disabled:border-neutral-300 disabled:bg-neutral-300 sm:gap-2 sm:px-2 sm:text-xs"
                >
                    <ShoppingBag className="h-3.5 w-3.5 shrink-0" />
                    <span className="min-w-0 text-center sm:hidden">Añadir</span>
                    <span className="hidden min-w-0 text-center sm:inline">Añadir al carrito</span>
                </button>
            </div>
        </article>
    );
}
