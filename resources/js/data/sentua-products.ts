import type { SentuaCategory, SentuaProduct, SentuaPromo } from '@/types/sentua';

const img = (id: string) => `https://images.unsplash.com/${id}?w=400&h=500&fit=crop`;

const productMedia = (mediaBaseUrl: string, file: string) => `${mediaBaseUrl}/products/${file}`;

const sectionMedia = (mediaBaseUrl: string, file: string) => `${mediaBaseUrl}/sections/${file}`;

export function buildFlashSaleProducts(mediaBaseUrl: string): SentuaProduct[] {
    return [
        {
            id: '1',
            brand: 'Ralph Lauren',
            name: 'Polo Red Parfum 125 ml',
            size: '125 ml',
            price: 309,
            originalPrice: 609,
            discount: 49,
            badge: 'Oferta 24 hrs',
            exclusiveWeb: true,
            image: productMedia(mediaBaseUrl, 'perfume.webp'),
            imageFit: 'contain',
            href: '#',
        },
        {
            id: '2',
            brand: 'Hugo Boss',
            name: 'BOSS Bottled Beyond Eau de Parfum 150 ml',
            size: '150 ml',
            price: 389,
            originalPrice: 619,
            discount: 37,
            badge: 'Oferta 24 hrs',
            exclusiveWeb: true,
            image: productMedia(mediaBaseUrl, 'perfume2.webp'),
            imageFit: 'contain',
            href: '#',
        },
        {
            id: '3',
            brand: 'Viktor&Rolf',
            name: 'Flowerbomb Eau de Parfum',
            size: '2 Tamaños',
            price: 299,
            discount: 50,
            badge: 'Oferta 24 hrs',
            image: productMedia(mediaBaseUrl, 'perfume3.webp'),
            imageFit: 'contain',
            href: '#',
        },
        {
            id: '4',
            brand: 'Mercedes Benz',
            name: 'Club Black Eau de Toilette',
            size: '1 Tamaño',
            price: 222,
            discount: 50,
            badge: 'Oferta 24 hrs',
            exclusiveWeb: true,
            image: productMedia(mediaBaseUrl, 'perfume2.webp'),
            imageFit: 'contain',
            href: '#',
        },
    ];
}

/** @deprecated Use buildFlashSaleProducts(mediaBaseUrl) on the homepage */
export const flashSaleProducts: SentuaProduct[] = buildFlashSaleProducts('http://localhost:9000/freedom');

export function buildForHimProducts(mediaBaseUrl: string): SentuaProduct[] {
    return [
        {
            id: 'h1',
            brand: 'Givenchy',
            name: 'Gentlemen Only Eau Toilette 100 ml',
            size: '100 ml',
            price: 299,
            originalPrice: 509,
            discount: 41,
            badge: 'Oferta 24 hrs',
            exclusiveWeb: true,
            image: productMedia(mediaBaseUrl, 'perfumemen1.webp'),
            imageFit: 'contain',
            href: '#',
        },
        {
            id: 'h2',
            brand: 'Giorgio Armani',
            name: 'Acqua Di Gio Parfum 100 ml',
            size: '100 ml',
            price: 599,
            image: productMedia(mediaBaseUrl, 'perfumemen2.webp'),
            imageFit: 'contain',
            href: '#',
        },
        {
            id: 'h3',
            brand: 'Tom Ford',
            name: 'Costa Azzurra Eau de Parfum Unisex',
            size: '2 Tamaños',
            price: 699,
            image: productMedia(mediaBaseUrl, 'perfumemen3.webp'),
            imageFit: 'contain',
            href: '#',
        },
        {
            id: 'h4',
            brand: 'Kenzo',
            name: 'Kenzo Homme Eau de Toilette Intense Duo Pack',
            size: '2 x 110 ml',
            price: 399.5,
            originalPrice: 799,
            discount: 50,
            badge: 'Oferta 24 hrs',
            exclusiveWeb: true,
            image: productMedia(mediaBaseUrl, 'perfumemen4.webp'),
            imageFit: 'contain',
            href: '#',
        },
    ];
}

export function buildCategories(mediaBaseUrl: string): SentuaCategory[] {
    return [
        { name: 'Skincare', href: '#', image: sectionMedia(mediaBaseUrl, 'skincareprod.jpg') },
        { name: 'Maquillaje', href: '#', image: sectionMedia(mediaBaseUrl, 'maquillajeprod.jpg') },
        { name: 'Fragancias', href: '#', image: sectionMedia(mediaBaseUrl, 'fraganciasprod.webp') },
        { name: 'Capilar', href: '#', image: sectionMedia(mediaBaseUrl, 'cuidadocapilarprod.jpg') },
        { name: 'Accesorios', href: '#', image: img('photo-1553062407-98eeb64c6a62') },
        { name: 'Joyería', href: '#', image: sectionMedia(mediaBaseUrl, 'joyeriaprod.webp') },
    ];
}

export function buildPromos(mediaBaseUrl: string): SentuaPromo[] {
    return [
        {
            title: 'Encuentra los mejores productos para skincare',
            subtitle: '*Válido hasta agotar existencias',
            cta: 'Ver productos',
            href: '#',
            image: sectionMedia(mediaBaseUrl, 'skincare.jpg'),
        },
        {
            title: 'Encuentra las mejores fragancias para Ellos',
            subtitle: '*Válido hasta agotar existencias',
            cta: 'Ver productos',
            href: '#',
            image: sectionMedia(mediaBaseUrl, 'menfragance.jpg'),
        },
        {
            title: 'Tus favoritos e infaltables productos de cuidado capilar',
            subtitle: '*Válido hasta agotar existencias',
            cta: 'Ver productos',
            href: '#',
            image: sectionMedia(mediaBaseUrl, 'cuidadocapilarprod.jpg'),
        },
    ];
}

export const navItems: { label: string; href: string; highlight?: boolean }[] = [
    { label: 'Novedades', href: '#' },
    { label: 'Skincare', href: '#' },
    { label: 'Maquillaje', href: '#' },
    { label: 'Fragancias', href: '#' },
    { label: 'Cuidado capilar', href: '#' },
    { label: 'Accesorios', href: '#' },
    { label: 'Joyas', href: '#' },
    { label: 'Ideas de regalo', href: '#' },
    { label: 'OFERTAS 24 HRS', href: '#', highlight: true },
    { label: 'Marcas', href: '#' },
];
