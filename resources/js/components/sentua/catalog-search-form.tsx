import { Search } from 'lucide-react';
import { type FormEvent } from 'react';

interface CatalogSearchFormProps {
    searchQuery?: string;
    variant?: 'desktop' | 'mobile';
}

function goToCatalog(event: FormEvent<HTMLFormElement>): void {
    event.preventDefault();

    const query = new FormData(event.currentTarget).get('q')?.toString().trim() ?? '';

    if (query === '') {
        window.location.assign('/catalog');
        return;
    }

    window.location.assign(`/catalog?q=${encodeURIComponent(query)}`);
}

export function CatalogSearchForm({ searchQuery = '', variant = 'desktop' }: CatalogSearchFormProps) {
    if (variant === 'mobile') {
        return (
            <form action="/catalog" method="get" className="relative mt-3 lg:hidden" role="search" onSubmit={goToCatalog}>
                <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-neutral-400" />
                <input
                    type="search"
                    name="q"
                    defaultValue={searchQuery}
                    placeholder="Buscar..."
                    className="w-full rounded-full border border-neutral-200 bg-neutral-50 py-2 pr-4 pl-10 text-sm"
                />
            </form>
        );
    }

    return (
        <form
            action="/catalog"
            method="get"
            className="relative hidden min-w-0 flex-1 items-center lg:flex"
            role="search"
            onSubmit={goToCatalog}
        >
            <input
                type="search"
                name="q"
                defaultValue={searchQuery}
                placeholder="Buscar perfumes, maquillaje, skincare..."
                className="w-full rounded-full border border-neutral-900 bg-white py-2.5 pr-12 pl-4 text-sm text-neutral-900 outline-none placeholder:text-neutral-500 focus:ring-1 focus:ring-neutral-900"
            />
            <button
                type="submit"
                className="absolute top-1/2 right-1 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-black text-white transition-colors hover:bg-neutral-800"
                aria-label="Buscar"
            >
                <Search className="h-4 w-4" strokeWidth={2} />
            </button>
        </form>
    );
}
