import { FreedomLogo } from '@/components/freedom-logo';
import type { SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';

const footerLinks = {
    about: [
        { label: 'Freedom', href: '#' },
        { label: 'Tiendas físicas', href: '#' },
        { label: 'Citas en cabina', href: '#' },
    ],
    categories: [
        { label: 'Novedades', href: '#' },
        { label: 'Skincare', href: '#' },
        { label: 'Maquillaje', href: '#' },
        { label: 'Fragancias', href: '#' },
        { label: 'Cuidado capilar', href: '#' },
        { label: 'Accesorios', href: '#' },
        { label: 'Joyas', href: '#' },
    ],
    support: [
        { label: 'Preguntas frecuentes', href: '#' },
        { label: 'Contacto', href: '#' },
        { label: 'Ventas corporativas', href: '#' },
        { label: 'Libro de reclamaciones', href: '#' },
        { label: 'Rastreo', href: '#' },
    ],
    legal: [
        { label: 'Términos y condiciones', href: '#' },
        { label: 'Políticas protección de datos', href: '#' },
    ],
};

export function SentuaFooter() {
    const { logoUrl } = usePage<SharedData>().props;

    return (
        <footer className="bg-black text-white">
            <div className="border-b border-white/10">
                <div className="mx-auto max-w-7xl px-4 py-10">
                    <div className="grid gap-8 md:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <h3 className="mb-3 text-sm font-semibold tracking-wide uppercase">Novedades para ti</h3>
                            <p className="mb-4 text-sm text-neutral-400">
                                Recibe información actualizada, promociones y noticias especiales.
                            </p>
                            <form className="flex min-w-0 flex-col gap-2 sm:flex-row" onSubmit={(e) => e.preventDefault()}>
                                <input
                                    type="email"
                                    placeholder="Tu correo electrónico"
                                    className="flex-1 rounded-none border border-white/20 bg-transparent px-3 py-2 text-sm text-white placeholder:text-neutral-500 focus:border-white focus:outline-none"
                                />
                                <button
                                    type="submit"
                                    className="bg-white px-4 py-2 text-xs font-bold tracking-wide text-black uppercase"
                                >
                                    Suscribirme
                                </button>
                            </form>
                        </div>

                        <div>
                            <h3 className="mb-3 text-sm font-semibold tracking-wide uppercase">Sobre nosotros</h3>
                            <ul className="space-y-2">
                                {footerLinks.about.map((link) => (
                                    <li key={link.label}>
                                        <a href={link.href} className="text-sm text-neutral-400 hover:text-white">
                                            {link.label}
                                        </a>
                                    </li>
                                ))}
                            </ul>
                        </div>

                        <div>
                            <h3 className="mb-3 text-sm font-semibold tracking-wide uppercase">Categorías</h3>
                            <ul className="space-y-2">
                                {footerLinks.categories.map((link) => (
                                    <li key={link.label}>
                                        <a href={link.href} className="text-sm text-neutral-400 hover:text-white">
                                            {link.label}
                                        </a>
                                    </li>
                                ))}
                            </ul>
                        </div>

                        <div>
                            <h3 className="mb-3 text-sm font-semibold tracking-wide uppercase">Servicio al cliente</h3>
                            <ul className="space-y-2">
                                {footerLinks.support.map((link) => (
                                    <li key={link.label}>
                                        <a href={link.href} className="text-sm text-neutral-400 hover:text-white">
                                            {link.label}
                                        </a>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div className="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-6 text-sm text-neutral-400 md:flex-row md:items-center md:justify-between">
                <div>
                    {logoUrl ? (
                        <FreedomLogo url={logoUrl} variant="dark" imageClassName="h-8 w-auto" />
                    ) : (
                        <p className="font-serif text-lg tracking-[0.3em] text-white">FREEDOM</p>
                    )}
                    <p className="mt-4">Lima, Perú · +51 972 776 913</p>
                    <p>contacto@freedom.com.pe</p>
                </div>
                <div className="flex flex-wrap gap-4">
                    {footerLinks.legal.map((link) => (
                        <a key={link.label} href={link.href} className="hover:text-white">
                            {link.label}
                        </a>
                    ))}
                </div>
                <p className="text-xs text-neutral-500">© {new Date().getFullYear()} Freedom. Todos los derechos reservados.</p>
            </div>
        </footer>
    );
}
