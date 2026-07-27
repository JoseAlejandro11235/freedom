export interface SentuaProduct {
    id: string;
    brand: string;
    name: string;
    size?: string;
    price: number;
    originalPrice?: number;
    discount?: number;
    badge?: string;
    exclusiveWeb?: boolean;
    image: string;
    imageFit?: 'cover' | 'contain';
    href: string;
    inStock?: boolean;
    stockQuantity?: number | null;
}

export interface CartItem extends SentuaProduct {
    quantity: number;
    maxQuantity: number | null;
    lineTotal: number;
}

export interface CartSummary {
    count: number;
    subtotal: number;
    items: CartItem[];
}

export interface SentuaCategory {
    name: string;
    href: string;
    image: string;
}

export interface SentuaPromo {
    title: string;
    subtitle: string;
    cta: string;
    href: string;
    image: string;
}

export interface StorefrontNavItem {
    label: string;
    href: string;
    highlight?: boolean;
}
