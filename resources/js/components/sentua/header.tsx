import { FreedomLogo } from '@/components/freedom-logo';
import { MobileMenu } from '@/components/sentua/mobile-menu';
import { navItems } from '@/data/sentua-products';
import type { SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { ChevronDown, MapPin, Menu, Search, ShoppingBag, Sparkles, User } from 'lucide-react';
import { useState } from 'react';

export function SentuaHeader() {
    const { auth, logoUrl } = usePage<SharedData>().props;
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

    return (
        <header className="sticky top-0 z-50 w-full max-w-full overflow-x-hidden bg-white shadow-sm">
            {/* Top promo bar */}
            <div className="bg-black text-center text-[11px] tracking-wide text-white">
                <div className="flex w-full flex-col items-center gap-1 px-4 py-2 sm:flex-row sm:flex-wrap sm:justify-center sm:gap-2 lg:px-10 xl:px-16">
                    <div className="flex flex-wrap items-center justify-center gap-2">
                        <Sparkles className="h-3 w-3 shrink-0 text-amber-400" />
                        <span className="font-semibold uppercase">Venta Flash</span>
                        <span className="hidden text-neutral-300 sm:inline">|</span>
                        <span className="sm:hidden">
                            <strong className="text-white">08</strong>h : <strong>42</strong>m
                        </span>
                        <span className="hidden sm:inline">
                            Quedan <strong className="text-white">00</strong> Días : <strong>08</strong> Horas :{' '}
                            <strong>42</strong> Min
                        </span>
                    </div>
                    <span className="hidden text-neutral-300 sm:inline">|</span>
                    <Link href="#" className="font-medium underline-offset-2 hover:underline">
                        Freedom Beauty Club
                    </Link>
                </div>
            </div>

            {/* Utility bar */}
            <div className="border-b border-neutral-100">
                <div className="flex w-full flex-wrap items-center justify-end gap-x-4 gap-y-1 px-4 py-2 text-xs text-neutral-600 sm:justify-between lg:px-10 xl:px-16">
                    <div className="hidden items-center gap-1 md:flex">
                        <MapPin className="h-3.5 w-3.5" />
                        <span>Tiendas</span>
                    </div>
                    <p className="hidden text-center sm:block">
                        Cupón <strong className="text-black">SOYFREEDOM</strong> — 10% adicional en tu primera compra
                    </p>
                    <div className="flex items-center gap-4">
                        {auth.user ? (
                            <Link href="/dashboard" className="hover:text-black">
                                Hola, {auth.user.name}
                            </Link>
                        ) : (
                            <>
                                <Link href="/login" className="hover:text-black">
                                    Iniciar sesión
                                </Link>
                                <Link href="/register" className="font-semibold text-black hover:underline">
                                    Crear cuenta
                                </Link>
                            </>
                        )}
                    </div>
                </div>
            </div>

            {/* Main header */}
            <div className="w-full px-4 py-4 lg:px-10 xl:px-16">
                <div className="flex min-w-0 items-center gap-3 sm:gap-4 lg:gap-6">
                    <div className="min-w-0 shrink-0">
                        {logoUrl ? (
                            <FreedomLogo url={logoUrl} variant="light" />
                        ) : (
                            <Link href="/" className="block min-w-0">
                                <span
                                    className="block truncate text-xl font-bold tracking-[0.2em] text-black sm:text-2xl sm:tracking-[0.3em] lg:text-3xl lg:tracking-[0.35em]"
                                    style={{ fontFamily: "'Playfair Display', serif" }}
                                >
                                    FREEDOM
                                </span>
                            </Link>
                        )}
                    </div>

                    <button
                        type="button"
                        className="shrink-0 lg:hidden"
                        onClick={() => setMobileMenuOpen(true)}
                        aria-label="Menú"
                        aria-expanded={mobileMenuOpen}
                    >
                        <span className="flex h-5 w-6 flex-col justify-between">
                            <span className="block h-0.5 w-full bg-black" />
                            <span className="block h-0.5 w-full bg-black" />
                            <span className="block h-0.5 w-full bg-black" />
                        </span>
                    </button>

                    <button
                        type="button"
                        className="hidden shrink-0 items-center gap-2 text-sm font-medium text-neutral-900 transition-opacity hover:opacity-70 lg:flex"
                        onClick={() => setMobileMenuOpen(true)}
                        aria-label="Menú"
                        aria-expanded={mobileMenuOpen}
                    >
                        <Menu className="h-5 w-5" strokeWidth={1.75} />
                        <span>Menú</span>
                    </button>

                    {/* Desktop: full-width pill search with button inside */}
                    <form
                        className="relative hidden min-w-0 flex-1 items-center lg:flex"
                        onSubmit={(e) => e.preventDefault()}
                        role="search"
                    >
                        <input
                            type="search"
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

                    <div className="ml-auto flex shrink-0 items-center gap-3 lg:ml-0 lg:gap-6">
                        <Link href="#" className="hidden items-center gap-1 text-xs font-medium lg:flex">
                            <Sparkles className="h-4 w-4" />
                            Beauty Club
                        </Link>
                        <button type="button" className="relative shrink-0 p-1" aria-label="Carrito">
                            <ShoppingBag className="h-5 w-5" />
                            <span className="absolute top-0 right-0 flex h-4 w-4 translate-x-1/4 -translate-y-1/4 items-center justify-center rounded-full bg-black text-[9px] font-bold text-white">
                                0
                            </span>
                        </button>
                        <Link href={auth.user ? '/dashboard' : '/login'} className="hidden sm:block" aria-label="Cuenta">
                            <User className="h-5 w-5" />
                        </Link>
                    </div>
                </div>

                {/* Search mobile */}
                <div className="relative mt-3 lg:hidden">
                    <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-neutral-400" />
                    <input
                        type="search"
                        placeholder="Buscar..."
                        className="w-full rounded-full border border-neutral-200 bg-neutral-50 py-2 pr-4 pl-10 text-sm"
                    />
                </div>
            </div>

            <MobileMenu open={mobileMenuOpen} onOpenChange={setMobileMenuOpen} />

            {/* Navigation — desktop only; mobile uses slide-out drawer */}
            <nav className="hidden border-t border-neutral-100 bg-white lg:block">
                <ul className="flex w-full flex-col gap-0 px-4 lg:flex-row lg:items-center lg:justify-center lg:gap-1 lg:px-10 lg:py-0 xl:px-16">
                    {navItems.map((item) => (
                        <li key={item.label}>
                            <a
                                href={item.href}
                                className={`flex items-center gap-1 px-3 py-3 text-xs font-medium tracking-wide uppercase transition-colors hover:bg-neutral-50 lg:py-3.5 ${
                                    item.highlight ? 'font-bold text-[#c41e3a]' : 'text-neutral-800'
                                }`}
                            >
                                {item.label}
                                {!item.highlight && (
                                    <ChevronDown className="hidden h-3 w-3 opacity-40 lg:inline" />
                                )}
                            </a>
                        </li>
                    ))}
                </ul>
            </nav>
        </header>
    );
}
