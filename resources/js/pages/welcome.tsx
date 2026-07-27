import { SentuaFooter } from '@/components/sentua/footer';
import { SentuaHeader } from '@/components/sentua/header';
import { ProductGrid } from '@/components/sentua/product-grid';
import { ScrambleText } from '@/components/sentua/scramble-text';
import type { SentuaCategory, SentuaProduct, SentuaPromo } from '@/types/sentua';
import type { SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { Gift, Leaf, ShieldCheck, Truck } from 'lucide-react';

const benefits = [
    { icon: Gift, text: 'Obtén regalos por compras en marcas seleccionadas' },
    { icon: Truck, text: 'Recibe tus productos sin moverte de casa' },
    { icon: ShieldCheck, text: 'Paga en cuotas sin intereses con tu banco favorito' },
    { icon: Leaf, text: 'Cuidamos el medioambiente. Caja 100% reciclable' },
];

const heroPhrases = [
    {
        text: 'El hombre nace libre, pero en todas partes está encadenado.',
        author: 'Rousseau',
        className: 'left-[4%] top-[6%] text-left text-[clamp(1.1rem,3vw,2.5rem)]',
    },
    {
        text: '¿Quieres saber qué es la libertad? No ser esclavo de nada.',
        author: 'Séneca',
        className: 'right-[4%] top-[14%] text-right text-[clamp(1.05rem,2.8vw,2.3rem)]',
    },
    {
        text: 'La libertad es la obediencia a la ley que uno se ha prescrito.',
        author: 'Rousseau',
        className: 'left-[5%] top-[34%] text-left text-[clamp(1.05rem,2.8vw,2.35rem)]',
    },
    {
        text: 'La libertad no es hacer lo que se quiere, sino poder hacer lo que se debe.',
        author: 'Kant',
        className: 'right-[5%] top-[42%] text-right text-[clamp(1rem,2.65vw,2.2rem)]',
    },
    {
        text: 'Nadie es libre si no es dueño de sí mismo.',
        author: 'Epicteto',
        className: 'left-[6%] bottom-[20%] text-left text-[clamp(1.1rem,3vw,2.5rem)]',
    },
    {
        text: 'La libertad es el derecho de hacer todo lo que las leyes permiten.',
        author: 'Montesquieu',
        className: 'right-[6%] bottom-[10%] text-right text-[clamp(1.05rem,2.75vw,2.3rem)]',
    },
];

interface WelcomeProps {
    meta: {
        title: string;
        description: string;
    };
    featuredProducts: SentuaProduct[];
    newArrivalsProducts: SentuaProduct[];
    categories: SentuaCategory[];
    promos: SentuaPromo[];
    brands: string[];
}

export default function Welcome() {
    const { featuredProducts, newArrivalsProducts, categories, promos, brands } =
        usePage<SharedData & WelcomeProps>().props;

    return (
        <>
            <Head title="Freedom — Perfumes, Maquillaje y Skincare">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link
                    href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|playfair-display:400,500,600,700,400i,600i"
                    rel="stylesheet"
                />
            </Head>

            <div
                className="min-h-screen w-full max-w-full overflow-x-hidden bg-white text-neutral-900"
                style={{ fontFamily: "'DM Sans', sans-serif" }}
            >
                <SentuaHeader />

                {/* Hero — layered typography */}
                <section className="relative isolate min-h-[min(92vh,56rem)] overflow-hidden bg-[#efe8ea] text-neutral-950">
                    <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_20%_20%,#c41e3a22,transparent_45%),radial-gradient(ellipse_at_85%_70%,#1a1a1a12,transparent_40%)]" />

                    {/* Accent shapes */}
                    <div className="pointer-events-none absolute top-[12%] left-[6%] h-16 w-16 rounded-full bg-[#c41e3a] opacity-90 sm:h-20 sm:w-20 lg:h-24 lg:w-24 animate-[hero-float_8s_ease-in-out_infinite]" />
                    <div className="pointer-events-none absolute top-[28%] right-[18%] h-10 w-10 rotate-45 border-2 border-[#c41e3a]/80 sm:h-12 sm:w-12 animate-[hero-float_10s_ease-in-out_infinite_reverse]" />
                    <div className="pointer-events-none absolute bottom-[18%] left-[22%] h-14 w-14 rounded-full bg-[#1a1a1a] opacity-80 sm:h-16 sm:w-16 animate-[hero-float_9s_ease-in-out_infinite]" />
                    <div
                        className="pointer-events-none absolute top-[55%] right-[8%] h-20 w-20 opacity-90 sm:h-24 sm:w-24"
                        style={{
                            background:
                                'conic-gradient(from 0deg, #c41e3a 0deg 18deg, transparent 18deg 36deg)',
                            WebkitMask: 'radial-gradient(farthest-side, transparent calc(100% - 10px), #000 calc(100% - 9px))',
                            mask: 'radial-gradient(farthest-side, transparent calc(100% - 10px), #000 calc(100% - 9px))',
                            animation: 'hero-spin 18s linear infinite',
                        }}
                    />

                    {/* Background phrases — kept fully inside the hero */}
                    <div className="pointer-events-none absolute inset-0 select-none overflow-hidden p-4 sm:p-6 lg:p-8" aria-hidden>
                        {heroPhrases.map((phrase, index) => (
                            <p
                                key={phrase.text}
                                className={`absolute max-w-[42vw] text-balance font-serif italic leading-[0.95] tracking-tight text-neutral-900/30 sm:max-w-[38vw] ${phrase.className}`}
                                style={{
                                    animation: `hero-phrase-in 1.1s ease-out ${0.08 * index}s both`,
                                }}
                            >
                                {phrase.text}{' '}
                                <span className="whitespace-nowrap text-[0.38em] not-italic tracking-[0.12em]">
                                    — {phrase.author}
                                </span>
                            </p>
                        ))}
                    </div>

                    {/* Brand + CTA */}
                    <div className="relative z-10 mx-auto flex min-h-[min(92vh,56rem)] max-w-7xl flex-col items-center justify-center px-4 pb-16 pt-10 text-center">
                        <p
                            className="text-xs font-bold tracking-[0.35em] text-[#c41e3a] uppercase"
                            style={{ animation: 'hero-phrase-in 0.8s ease-out 0.15s both' }}
                        >
                            Perfumes · Maquillaje · Skincare
                        </p>
                        <h1
                            className="mt-4 whitespace-nowrap font-serif text-[clamp(2.75rem,11vw,8.5rem)] leading-none font-medium tracking-[0.12em] text-neutral-950 uppercase"
                            style={{
                                fontFamily: "'Playfair Display', serif",
                                animation: 'hero-brand-in 1s cubic-bezier(0.16,1,0.3,1) 0.15s both',
                            }}
                        >
                            <ScrambleText text="FREEDOM" />
                            <span className="text-[#c41e3a]">*</span>
                        </h1>
                        <p
                            className="mt-6 max-w-md text-base text-neutral-700 sm:text-lg"
                            style={{ animation: 'hero-phrase-in 0.9s ease-out 0.45s both' }}
                        >
                            Belleza con carácter. Elige lo que te hace sentir libre.
                        </p>
                        <a
                            href="#destacados"
                            className="mt-8 inline-block bg-neutral-950 px-8 py-3 text-xs font-bold tracking-widest text-white uppercase transition-colors hover:bg-[#c41e3a]"
                            style={{ animation: 'hero-phrase-in 0.9s ease-out 0.55s both' }}
                        >
                            Ver destacados
                        </a>
                    </div>

                    <style>{`
                        @keyframes hero-phrase-in {
                            from { opacity: 0; transform: translateY(18px); }
                            to { opacity: 1; transform: translateY(0); }
                        }
                        @keyframes hero-brand-in {
                            from { opacity: 0; transform: scale(0.92) translateY(24px); }
                            to { opacity: 1; transform: scale(1) translateY(0); }
                        }
                        @keyframes hero-float {
                            0%, 100% { transform: translateY(0); }
                            50% { transform: translateY(-12px); }
                        }
                        @keyframes hero-spin {
                            from { transform: rotate(0deg); }
                            to { transform: rotate(360deg); }
                        }
                    `}</style>
                </section>

                {/* Coupon banner */}
                <div className="border-y border-neutral-200 bg-[#faf8f6] px-4 py-4 text-center">
                    <p className="text-sm text-balance text-neutral-700">
                        <span className="font-semibold text-black">¡Obtén 10% adicional!</span> en tu primera compra con el cupón{' '}
                        <code className="mx-1 inline-block max-w-full break-all bg-black px-2 py-0.5 font-bold text-white">SOYFREEDOM</code>
                        <span className="text-neutral-500"> · *Aplican términos y condiciones</span>
                    </p>
                </div>

                <div id="destacados">
                    <ProductGrid
                        title="Destacados"
                        subtitle="Selección editorial de Freedom"
                        products={featuredProducts}
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

                <div id="novedades">
                    <ProductGrid
                        title="Novedades"
                        subtitle="Lo último en llegar a Freedom"
                        products={newArrivalsProducts}
                        cta={{ label: 'Ver todo', href: '/catalog' }}
                        layout="carousel"
                    />
                </div>

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
                {promos.length > 0 && (
                    <section className="py-10">
                        <div className="mx-auto grid max-w-7xl items-stretch gap-4 px-4 md:grid-cols-3">
                            {promos.map((promo) => (
                                <a
                                    key={promo.href}
                                    href={promo.href}
                                    className="group relative flex h-full min-h-[280px] flex-col justify-end overflow-hidden bg-neutral-900 p-6 text-white"
                                >
                                    {promo.image ? (
                                        <img
                                            src={promo.image}
                                            alt=""
                                            className="absolute inset-0 h-full w-full object-cover opacity-60 transition-opacity group-hover:opacity-50"
                                        />
                                    ) : null}
                                    <div className="relative">
                                        <p className="min-h-[4.5rem] text-lg leading-snug font-medium">{promo.title}</p>
                                        <span className="mt-3 inline-block border-b border-white pb-0.5 text-xs font-bold tracking-wide uppercase">
                                            {promo.cta}
                                        </span>
                                        {promo.subtitle ? (
                                            <p className="mt-2 text-[10px] text-neutral-300">{promo.subtitle}</p>
                                        ) : null}
                                    </div>
                                </a>
                            ))}
                        </div>
                    </section>
                )}

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
                {brands.length > 0 && (
                    <section className="border-t border-neutral-100 py-10">
                        <p className="mb-6 text-center text-xs font-semibold tracking-[0.25em] text-neutral-500 uppercase">
                            Descubre las mejores marcas
                        </p>
                        <div className="mx-auto flex max-w-5xl flex-wrap items-center justify-center gap-8 px-4 opacity-60 grayscale">
                            {brands.map((brand) => (
                                <span key={brand} className="font-serif text-lg tracking-widest">
                                    {brand}
                                </span>
                            ))}
                        </div>
                    </section>
                )}

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
