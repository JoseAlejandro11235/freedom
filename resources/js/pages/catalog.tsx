import { SentuaFooter } from '@/components/sentua/footer';
import { SentuaHeader } from '@/components/sentua/header';
import { ProductCard } from '@/components/sentua/product-card';
import type { SentuaProduct } from '@/types/sentua';
import { Head, Link, usePage } from '@inertiajs/react';
import { X } from 'lucide-react';

interface CatalogFilterOption {
    name: string;
    slug: string;
    href: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedProducts {
    data: SentuaProduct[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: PaginationLink[];
}

interface CatalogProps {
    meta: {
        title: string;
    };
    products: PaginatedProducts;
    filters: {
        q: string;
        brand: string | null;
        category: string | null;
    };
    activeBrand: { name: string; slug: string } | null;
    activeCategory: { name: string; slug: string } | null;
    brands: CatalogFilterOption[];
    categories: CatalogFilterOption[];
}

function buildCatalogHref(filters: CatalogProps['filters'], overrides: Partial<CatalogProps['filters']> = {}): string {
    const next = { ...filters, ...overrides };
    const params = new URLSearchParams();

    if (next.q.trim() !== '') {
        params.set('q', next.q.trim());
    }

    if (next.brand) {
        params.set('brand', next.brand);
    }

    if (next.category) {
        params.set('category', next.category);
    }

    const query = params.toString();

    return query ? `/catalog?${query}` : '/catalog';
}

function catalogTitle(
    filters: CatalogProps['filters'],
    activeBrand: CatalogProps['activeBrand'],
    activeCategory: CatalogProps['activeCategory'],
): string {
    if (activeCategory) {
        return activeCategory.name;
    }

    if (activeBrand) {
        return activeBrand.name;
    }

    if (filters.q.trim() !== '') {
        return `Resultados para “${filters.q.trim()}”`;
    }

    return 'Catálogo';
}

function decodePaginationLabel(label: string): string {
    return label
        .replace(/&laquo;/g, '«')
        .replace(/&raquo;/g, '»')
        .replace(/&amp;/g, '&');
}

export default function Catalog() {
    const { products, filters, activeBrand, activeCategory, brands, categories, meta } =
        usePage<CatalogProps>().props;

    const items = products.data ?? [];
    const hasActiveFilters = filters.q.trim() !== '' || activeBrand !== null || activeCategory !== null;
    const title = catalogTitle(filters, activeBrand, activeCategory);
    const showPagination = products.last_page > 1;

    return (
        <>
            <Head title={meta.title}>
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link
                    href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|playfair-display:400,500,600,700"
                    rel="stylesheet"
                />
            </Head>

            <div
                className="min-h-screen w-full max-w-full overflow-x-hidden bg-white text-neutral-900"
                style={{ fontFamily: "'DM Sans', sans-serif" }}
            >
                <SentuaHeader searchQuery={filters.q} />

                <div className="mx-auto max-w-7xl px-4 py-8 lg:py-12">
                    <div className="mb-8 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <p className="text-xs font-semibold tracking-[0.25em] text-neutral-500 uppercase">Tienda</p>
                            <h1 className="mt-2 font-serif text-3xl tracking-wide text-neutral-900 uppercase lg:text-4xl">
                                {title}
                            </h1>
                            <p className="mt-2 text-sm text-neutral-500">
                                {products.total} {products.total === 1 ? 'producto' : 'productos'}
                                {showPagination && products.from !== null && products.to !== null
                                    ? ` · mostrando ${products.from}–${products.to}`
                                    : ''}
                            </p>
                        </div>

                        {hasActiveFilters && (
                            <div className="flex flex-wrap items-center gap-2">
                                {filters.q.trim() !== '' && (
                                    <Link
                                        href={buildCatalogHref(filters, { q: '' })}
                                        className="inline-flex items-center gap-1 rounded-full border border-neutral-300 px-3 py-1.5 text-xs font-medium text-neutral-700 hover:border-neutral-900"
                                    >
                                        Búsqueda: {filters.q.trim()}
                                        <X className="h-3 w-3" />
                                    </Link>
                                )}
                                {activeBrand && (
                                    <Link
                                        href={buildCatalogHref(filters, { brand: null })}
                                        className="inline-flex items-center gap-1 rounded-full border border-neutral-300 px-3 py-1.5 text-xs font-medium text-neutral-700 hover:border-neutral-900"
                                    >
                                        Marca: {activeBrand.name}
                                        <X className="h-3 w-3" />
                                    </Link>
                                )}
                                {activeCategory && (
                                    <Link
                                        href={buildCatalogHref(filters, { category: null })}
                                        className="inline-flex items-center gap-1 rounded-full border border-neutral-300 px-3 py-1.5 text-xs font-medium text-neutral-700 hover:border-neutral-900"
                                    >
                                        Categoría: {activeCategory.name}
                                        <X className="h-3 w-3" />
                                    </Link>
                                )}
                                <Link
                                    href="/catalog"
                                    className="text-xs font-semibold tracking-wide text-black uppercase hover:underline"
                                >
                                    Limpiar filtros
                                </Link>
                            </div>
                        )}
                    </div>

                    <div className="grid gap-10 lg:grid-cols-[240px_minmax(0,1fr)]">
                        <aside className="space-y-8">
                            <div>
                                <h2 className="text-xs font-bold tracking-[0.2em] text-neutral-900 uppercase">Categorías</h2>
                                <ul className="mt-3 space-y-2">
                                    {categories.map((category) => (
                                        <li key={category.slug}>
                                            <Link
                                                href={category.href}
                                                className={`text-sm transition-colors hover:text-black ${
                                                    activeCategory?.slug === category.slug
                                                        ? 'font-semibold text-black'
                                                        : 'text-neutral-600'
                                                }`}
                                            >
                                                {category.name}
                                            </Link>
                                        </li>
                                    ))}
                                </ul>
                            </div>

                            <div>
                                <h2 className="text-xs font-bold tracking-[0.2em] text-neutral-900 uppercase">Marcas</h2>
                                <ul className="mt-3 max-h-72 space-y-2 overflow-y-auto">
                                    {brands.map((brand) => (
                                        <li key={brand.slug}>
                                            <Link
                                                href={brand.href}
                                                className={`text-sm transition-colors hover:text-black ${
                                                    activeBrand?.slug === brand.slug
                                                        ? 'font-semibold text-black'
                                                        : 'text-neutral-600'
                                                }`}
                                            >
                                                {brand.name}
                                            </Link>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        </aside>

                        <div>
                            {items.length > 0 ? (
                                <>
                                    <div className="grid w-full min-w-0 grid-cols-2 items-stretch gap-3 sm:gap-6 md:grid-cols-3 xl:grid-cols-4 [&>*]:min-w-0">
                                        {items.map((product) => (
                                            <ProductCard key={product.id} product={product} />
                                        ))}
                                    </div>

                                    {showPagination && (
                                        <nav
                                            className="mt-10 flex flex-wrap items-center justify-center gap-2"
                                            aria-label="Paginación del catálogo"
                                        >
                                            {products.links.map((link, index) => {
                                                const label = decodePaginationLabel(link.label);
                                                const className = `min-w-9 border px-3 py-2 text-xs font-medium transition-colors ${
                                                    link.active
                                                        ? 'border-black bg-black text-white'
                                                        : 'border-neutral-300 text-neutral-700 hover:border-black hover:text-black'
                                                } ${!link.url ? 'pointer-events-none opacity-40' : ''}`;

                                                if (!link.url) {
                                                    return (
                                                        <span
                                                            key={`${label}-${index}`}
                                                            className={className}
                                                            aria-disabled="true"
                                                            dangerouslySetInnerHTML={{ __html: label }}
                                                        />
                                                    );
                                                }

                                                return (
                                                    <Link
                                                        key={`${label}-${index}`}
                                                        href={link.url}
                                                        className={className}
                                                        aria-current={link.active ? 'page' : undefined}
                                                        dangerouslySetInnerHTML={{ __html: label }}
                                                    />
                                                );
                                            })}
                                        </nav>
                                    )}
                                </>
                            ) : (
                                <div className="rounded-2xl border border-dashed border-neutral-300 px-6 py-16 text-center">
                                    <p className="font-serif text-2xl tracking-wide text-neutral-900 uppercase">
                                        Sin resultados
                                    </p>
                                    <p className="mt-3 text-sm text-neutral-500">
                                        Prueba con otro término, marca o categoría.
                                    </p>
                                    <Link
                                        href="/catalog"
                                        className="mt-6 inline-block border border-black px-6 py-3 text-xs font-bold tracking-widest uppercase hover:bg-black hover:text-white"
                                    >
                                        Ver catálogo completo
                                    </Link>
                                </div>
                            )}
                        </div>
                    </div>
                </div>

                <SentuaFooter />
            </div>
        </>
    );
}
