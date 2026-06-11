import { navItems } from '@/data/sentua-products';
import type { SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { ChevronRight, HelpCircle, LogIn, MapPin, Package, Sparkles, User, UserPlus, X, type LucideIcon } from 'lucide-react';
import { Sheet, SheetClose, SheetContent } from '@/components/ui/sheet';

interface MobileMenuProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

interface MenuLinkProps {
    href: string;
    label: string;
    icon: LucideIcon;
    showChevron?: boolean;
    onNavigate?: () => void;
}

function MenuLink({ href, label, icon: Icon, showChevron = true, onNavigate }: MenuLinkProps) {
    return (
        <li>
            <Link
                href={href}
                onClick={onNavigate}
                className="flex items-center gap-3 px-4 py-3.5 text-sm text-neutral-800 transition-colors hover:bg-neutral-50"
            >
                <Icon className="h-5 w-5 shrink-0 text-neutral-600" strokeWidth={1.5} />
                <span className="min-w-0 flex-1">{label}</span>
                {showChevron && <ChevronRight className="h-4 w-4 shrink-0 text-neutral-400" />}
            </Link>
        </li>
    );
}

export function MobileMenu({ open, onOpenChange }: MobileMenuProps) {
    const { auth } = usePage<SharedData>().props;
    const close = () => onOpenChange(false);

    const accountLinks: MenuLinkProps[] = auth.user
        ? [
              { href: '/dashboard', label: 'Mi cuenta', icon: User },
              { href: '#', label: 'Historial de pedidos', icon: Package },
          ]
        : [
              { href: '/login', label: 'Iniciar sesión', icon: LogIn },
              { href: '/register', label: 'Crear cuenta', icon: UserPlus },
          ];

    const serviceLinks: MenuLinkProps[] = [
        { href: '#', label: 'Tiendas', icon: MapPin },
        { href: '#', label: 'Beauty Club', icon: Sparkles },
        { href: '#', label: 'Contacto y ayuda', icon: HelpCircle, showChevron: true },
    ];

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent
                side="left"
                className="flex h-full w-[85%] max-w-[20rem] flex-col gap-0 overflow-hidden border-r border-neutral-200 bg-white p-0 text-neutral-900 sm:max-w-xs [&>button]:hidden"
            >
                {/* Header: greeting + close */}
                <div className="flex shrink-0 items-center justify-between border-b border-neutral-200 px-4 py-4">
                    <p className="text-base font-semibold text-neutral-900">
                        {auth.user ? `¡Hola, ${auth.user.name}!` : '¡Hola!'}
                    </p>
                    <SheetClose asChild>
                        <button
                            type="button"
                            className="rounded-sm p-1 text-neutral-800 transition-opacity hover:opacity-70"
                            aria-label="Cerrar menú"
                        >
                            <X className="h-5 w-5" />
                        </button>
                    </SheetClose>
                </div>

                {/* Scrollable menu body */}
                <div className="min-h-0 flex-1 overflow-y-auto overscroll-contain">
                    <ul>
                        {[...accountLinks, ...serviceLinks].map((item) => (
                            <MenuLink key={item.label} {...item} onNavigate={close} />
                        ))}
                    </ul>

                    <div className="my-2 border-t border-neutral-200" />

                    <ul>
                        {navItems.map((item) => (
                            <li key={item.label}>
                                <a
                                    href={item.href}
                                    onClick={close}
                                    className="flex items-center gap-2 px-4 py-3.5 text-sm transition-colors hover:bg-neutral-50"
                                >
                                    <span
                                        className={`min-w-0 flex-1 tracking-wide uppercase ${
                                            item.highlight ? 'font-bold text-[#c41e3a]' : 'font-medium text-neutral-800'
                                        }`}
                                    >
                                        {item.label}
                                    </span>
                                    {item.highlight && (
                                        <span className="shrink-0 rounded-sm bg-[#c41e3a] px-1.5 py-0.5 text-[10px] font-bold tracking-wide text-white uppercase">
                                            Sale
                                        </span>
                                    )}
                                    <ChevronRight className="ml-auto h-4 w-4 shrink-0 text-neutral-400" />
                                </a>
                            </li>
                        ))}
                    </ul>
                </div>
            </SheetContent>
        </Sheet>
    );
}
