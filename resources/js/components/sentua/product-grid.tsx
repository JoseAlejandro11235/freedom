import type { SentuaProduct } from '@/types/sentua';
import { ProductCard } from './product-card';

interface ProductGridProps {
    title: string;
    subtitle?: string;
    products: SentuaProduct[];
    cta?: { label: string; href: string };
    /** Carousel on mobile/tablet; grid from `lg` up (e.g. "Lo mejor para ellos"). */
    layout?: 'grid' | 'carousel';
}

const productGridClassName =
    'grid w-full min-w-0 grid-cols-2 items-stretch gap-3 sm:gap-6 md:grid-cols-3 lg:grid-cols-4 [&>*]:min-w-0';

export function ProductGrid({ title, subtitle, products, cta, layout = 'grid' }: ProductGridProps) {
    return (
        <section className="py-10 lg:py-14">
            <div className="mx-auto max-w-7xl px-4">
                <div className="mb-8 flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <h2 className="font-serif text-2xl tracking-wide text-neutral-900 uppercase lg:text-3xl">{title}</h2>
                        {subtitle && <p className="mt-1 text-sm text-neutral-500">{subtitle}</p>}
                    </div>
                    {cta && (
                        <a
                            href={cta.href}
                            className="border-b border-black pb-0.5 text-xs font-semibold tracking-wide text-black uppercase hover:opacity-70"
                        >
                            {cta.label}
                        </a>
                    )}
                </div>

                {layout === 'carousel' ? (
                    <>
                        {/* Mobile / tablet: swipeable carousel */}
                        <div className="relative min-w-0 lg:hidden">
                            <div className="-mx-4 flex min-w-0 snap-x snap-mandatory gap-3 overflow-x-auto scroll-smooth px-4 pb-1 [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden">
                                {products.map((product) => (
                                    <div
                                        key={product.id}
                                        data-carousel-item
                                        className="w-[42vw] max-w-[11.5rem] shrink-0 snap-start sm:w-52 md:w-56"
                                    >
                                        <ProductCard product={product} />
                                    </div>
                                ))}
                            </div>
                        </div>

                        {/* Desktop: same grid as other product sections */}
                        <div className={productGridClassName + ' hidden lg:grid'}>
                            {products.map((product) => (
                                <ProductCard key={product.id} product={product} />
                            ))}
                        </div>
                    </>
                ) : (
                    <div className={productGridClassName}>
                        {products.map((product) => (
                            <ProductCard key={product.id} product={product} />
                        ))}
                    </div>
                )}
            </div>
        </section>
    );
}
