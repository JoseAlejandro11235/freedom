import { FreedomLogo } from '@/components/freedom-logo';
import { CartDrawer } from '@/components/sentua/cart-drawer';
import { CatalogSearchForm } from '@/components/sentua/catalog-search-form';
import { MobileMenu } from '@/components/sentua/mobile-menu';
import type { SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { MapPin, Menu, ShoppingBag, Sparkles, User } from 'lucide-react';
import { useEffect, useState } from 'react';

interface SentuaHeaderProps {
    searchQuery?: string;
}

export function SentuaHeader({ searchQuery = '' }: SentuaHeaderProps) {
    const { auth, logoUrl, cart, flash } = usePage<SharedData>().props;
    const cartCount = cart?.count ?? 0;
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
    const [cartOpen, setCartOpen] = useState(false);
    const [toast, setToast] = useState<string | null>(null);

    useEffect(() => {
        if (flash?.success) {
            setToast(flash.success);
            setCartOpen(true);
        } else if (flash?.error) {
            setToast(flash.error);
        }
    }, [flash?.success, flash?.error]);

    useEffect(() => {
        if (!toast) {
            return;
        }

        const timer = window.setTimeout(() => setToast(null), 2800);

        return () => window.clearTimeout(timer);
    }, [toast]);

    return (
        <header className="sticky top-0 z-50 w-full max-w-full overflow-x-hidden bg-white shadow-sm">
            {/* Top promo bar */}
            <div className="bg-black text-center text-[11px] tracking-wide text-white">
                <div className="flex w-full flex-col items-center gap-1 px-4 py-2 sm:flex-row sm:flex-wrap sm:justify-center sm:gap-2 lg:px-10 xl:px-16">
                    <div className="flex flex-wrap items-center justify-center gap-2">
                        <Sparkles className="h-3 w-3 shrink-0 text-amber-400" />
                        <span className="font-semibold uppercase">Envío gratis</span>
                        <span className="hidden text-neutral-300 sm:inline">|</span>
                        <span>En compras desde S/199</span>
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

                    <CatalogSearchForm searchQuery={searchQuery} variant="desktop" />

                    <div className="ml-auto flex shrink-0 items-center gap-3 lg:ml-0 lg:gap-6">
                        <Link href="#" className="hidden items-center gap-1 text-xs font-medium lg:flex">
                            <Sparkles className="h-4 w-4" />
                            Beauty Club
                        </Link>
                        <button
                            type="button"
                            className="relative shrink-0 p-1"
                            aria-label="Carrito"
                            onClick={() => setCartOpen(true)}
                        >
                            <ShoppingBag className="h-5 w-5" />
                            {cartCount > 0 && (
                                <span className="absolute top-0 right-0 flex h-4 min-w-4 translate-x-1/4 -translate-y-1/4 items-center justify-center rounded-full bg-black px-1 text-[9px] font-bold text-white">
                                    {cartCount > 99 ? '99+' : cartCount}
                                </span>
                            )}
                        </button>
                        <Link href={auth.user ? '/dashboard' : '/login'} className="hidden sm:block" aria-label="Cuenta">
                            <User className="h-5 w-5" />
                        </Link>
                    </div>
                </div>

                <CatalogSearchForm searchQuery={searchQuery} variant="mobile" />
            </div>

            <MobileMenu open={mobileMenuOpen} onOpenChange={setMobileMenuOpen} />
            <CartDrawer open={cartOpen} onOpenChange={setCartOpen} />

            {toast && (
                <div
                    role="status"
                    className="fixed right-4 bottom-20 z-[60] max-w-xs border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 shadow-lg"
                >
                    {toast}
                </div>
            )}
        </header>
    );
}
