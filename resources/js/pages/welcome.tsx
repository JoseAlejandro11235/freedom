import { SentuaFooter } from '@/components/sentua/footer';
import { SentuaHeader } from '@/components/sentua/header';
import { ProductGrid } from '@/components/sentua/product-grid';
import { buildPromos } from '@/data/sentua-products';
import type { SentuaCategory, SentuaProduct } from '@/types/sentua';
import type { SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { Gift, Leaf, ShieldCheck, Truck } from 'lucide-react';

const benefits = [
    { icon: Gift, text: 'Obtén regalos por compras en marcas seleccionadas' },
    { icon: Truck, text: 'Recibe tus productos sin moverte de casa' },
    { icon: ShieldCheck, text: 'Paga en cuotas sin intereses con tu banco favorito' },
    { icon: Leaf, text: 'Cuidamos el medioambiente. Caja 100% reciclable' },
];

interface WelcomeProps {
    meta: {
        title: string;
        description: string;
    };
    flashSaleProducts: SentuaProduct[];
    forHimProducts: SentuaProduct[];
    categories: SentuaCategory[];
}

export default function Welcome() {
    const { mediaBaseUrl, flashSaleProducts, forHimProducts, categories } = usePage<SharedData & WelcomeProps>().props;
    const mediaUrl = mediaBaseUrl ?? 'http://localhost:9000/freedom';
    const promos = buildPromos(mediaUrl);

    return (
        <>
            <Head title="Freedom — Perfumes, Maquillaje y Skincare">
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
                <SentuaHeader />

                {/* Hero flash sale */}
                <section className="relative overflow-hidden bg-neutral-900 text-white">
                    <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,_#c41e3a33,_transparent_50%)]" />
                    <div className="relative mx-auto max-w-7xl px-4 py-16 lg:py-24">
                        <div className="max-w-xl">
                            <p className="text-xs font-bold tracking-[0.3em] text-[#f5a3b0] uppercase">Solo hoy</p>
                            <h1 className="mt-2 font-serif text-4xl leading-tight tracking-wide uppercase lg:text-6xl">
                                Por 24 horas
                            </h1>
                            <p className="mt-4 text-lg text-neutral-300">
                                Las mejores ofertas en fragancias, skincare y maquillaje de lujo.
                            </p>
                            <div className="mt-8 flex flex-wrap gap-4 text-center">
                                {[
                                    { label: 'Horas', value: '08' },
                                    { label: 'Min', value: '42' },
                                    { label: 'Seg', value: '15' },
                                ].map((unit) => (
                                    <div key={unit.label} className="min-w-[4rem] border border-white/20 px-3 py-2">
                                        <span className="block font-serif text-3xl">{unit.value}</span>
                                        <span className="text-[10px] tracking-widest text-neutral-400 uppercase">{unit.label}</span>
                                    </div>
                                ))}
                            </div>
                            <a
                                href="#ofertas"
                                className="mt-8 inline-block bg-white px-8 py-3 text-xs font-bold tracking-widest text-black uppercase hover:bg-neutral-100"
                            >
                                Ver ofertas
                            </a>
                        </div>
                    </div>
                </section>

                {/* Coupon banner */}
                <div className="border-y border-neutral-200 bg-[#faf8f6] px-4 py-4 text-center">
                    <p className="text-sm text-balance text-neutral-700">
                        <span className="font-semibold text-black">¡Obtén 10% adicional!</span> en tu primera compra con el cupón{' '}
                        <code className="mx-1 inline-block max-w-full break-all bg-black px-2 py-0.5 font-bold text-white">SOYFREEDOM</code>
                        <span className="text-neutral-500"> · *Aplican términos y condiciones</span>
                    </p>
                </div>

                <div id="ofertas">
                    <ProductGrid
                        title="Solo por 24 horas"
                        subtitle="Ofertas exclusivas web — termina pronto"
                        products={flashSaleProducts}
                    />
                </div>

                {/* Gift section */}
                <section className="bg-[#1a1a1a] py-14 text-white">
                    <div className="mx-auto max-w-7xl px-4 text-center">
                        <h2 className="font-serif text-3xl tracking-wide uppercase">¡Listo para regalar!</h2>
                        <p className="mt-2 text-neutral-400">Encuentra los mejores sets de regalo</p>
                        <a
                            href="#"
                            className="mt-6 inline-block border border-white px-8 py-3 text-xs font-bold tracking-widest uppercase hover:bg-white hover:text-black"
                        >
                            Ver ideas de regalo
                        </a>
                    </div>
                </section>

                <ProductGrid
                    title="Lo mejor para ellos"
                    products={forHimProducts}
                    cta={{ label: 'Ver todo', href: '#' }}
                    layout="carousel"
                />

                {/* Explore categories */}
                <section className="bg-neutral-50 py-14">
                    <div className="mx-auto max-w-7xl px-4">
                        <h2 className="text-center font-serif text-2xl tracking-wide text-neutral-900 uppercase lg:text-3xl">
                            Explora lo nuevo
                        </h2>
                        <p className="mt-2 text-center text-sm text-neutral-500">
                            Descubre las nuevas categorías que tenemos para ti.
                        </p>
                        <div className="mt-10 grid w-full min-w-0 grid-cols-2 items-stretch gap-3 md:grid-cols-3 lg:grid-cols-6 lg:gap-4 [&>*]:min-w-0">
                            {categories.map((cat) => (
                                <a
                                    key={cat.name}
                                    href={cat.href}
                                    className="group relative aspect-[3/4] min-w-0 overflow-hidden bg-neutral-200"
                                >
                                    {cat.image ? (
                                        <img
                                            src={cat.image}
                                            alt={cat.name}
                                            className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110"
                                        />
                                    ) : (
                                        <div className="h-full w-full bg-neutral-300" />
                                    )}
                                    <div className="absolute inset-0 bg-black/30 transition-colors group-hover:bg-black/40" />
                                    <span className="absolute bottom-4 left-0 w-full text-center text-sm font-semibold tracking-widest text-white uppercase">
                                        {cat.name}
                                    </span>
                                </a>
                            ))}
                        </div>
                    </div>
                </section>

                {/* Promo banners */}
                <section className="py-10">
                    <div className="mx-auto grid max-w-7xl items-stretch gap-4 px-4 md:grid-cols-3">
                        {promos.map((promo) => (
                            <a
                                key={promo.title}
                                href={promo.href}
                                className="group relative flex h-full min-h-[280px] flex-col justify-end overflow-hidden bg-neutral-900 p-6 text-white"
                            >
                                <img
                                    src={promo.image}
                                    alt=""
                                    className="absolute inset-0 h-full w-full object-cover opacity-60 transition-opacity group-hover:opacity-50"
                                />
                                <div className="relative">
                                    <p className="min-h-[4.5rem] text-lg leading-snug font-medium">{promo.title}</p>
                                    <span className="mt-3 inline-block border-b border-white pb-0.5 text-xs font-bold tracking-wide uppercase">
                                        {promo.cta}
                                    </span>
                                    <p className="mt-2 text-[10px] text-neutral-300">{promo.subtitle}</p>
                                </div>
                            </a>
                        ))}
                    </div>
                </section>

                {/* Kaos section */}
                <section className="border-y border-neutral-200 px-4 py-16 text-center">
                    <h2 className="font-serif text-3xl tracking-[0.12em] text-balance text-neutral-900 uppercase sm:text-4xl sm:tracking-[0.2em] lg:text-5xl">
                        El kaos perfecto
                    </h2>
                </section>

                {/* Benefits */}
                <section className="border-t border-neutral-100 bg-white py-10">
                    <div className="mx-auto grid max-w-7xl gap-6 px-4 sm:grid-cols-2 lg:grid-cols-4">
                        {benefits.map(({ icon: Icon, text }) => (
                            <div
                                key={text}
                                className="flex items-center justify-center gap-3 sm:justify-start"
                            >
                                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-neutral-100">
                                    <Icon className="h-5 w-5 text-neutral-700" />
                                </div>
                                <p className="text-center text-sm leading-snug text-neutral-600 sm:text-left">{text}</p>
                            </div>
                        ))}
                        </div>
                </section>

                {/* Brands */}
                <section className="border-t border-neutral-100 py-10">
                    <p className="mb-6 text-center text-xs font-semibold tracking-[0.25em] text-neutral-500 uppercase">
                        Descubre las mejores marcas
                    </p>
                    <div className="mx-auto flex max-w-5xl flex-wrap items-center justify-center gap-8 px-4 opacity-60 grayscale">
                        {['DIOR', 'CHANEL', 'GUCCI', 'LANCÔME', 'TOM FORD', 'ARMANI'].map((brand) => (
                            <span key={brand} className="font-serif text-lg tracking-widest">
                                {brand}
                            </span>
                        ))}
                </div>
                </section>

                <SentuaFooter />

                {/* WhatsApp float — inside page wrapper so it cannot widen the document */}
                <a
                    href="#"
                    className="fixed right-3 bottom-4 z-50 flex h-12 w-12 items-center justify-center rounded-full bg-[#25D366] text-white shadow-lg"
                    aria-label="WhatsApp"
                >
                    <svg viewBox="0 0 24 24" className="h-6 w-6 fill-current" aria-hidden>
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                    </svg>
                </a>
            </div>
        </>
    );
}
