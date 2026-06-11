import { Link } from '@inertiajs/react';

interface FreedomLogoProps {
    url: string;
    className?: string;
    imageClassName?: string;
    /** Light page background: show black letterforms (inverts white-on-dark MinIO assets). */
    variant?: 'light' | 'dark';
}

export function FreedomLogo({
    url,
    className = '',
    imageClassName = 'h-9 max-w-[8.5rem] w-auto object-contain sm:h-10 sm:max-w-[10rem] lg:h-12 lg:max-w-[12rem]',
    variant = 'light',
}: FreedomLogoProps) {
    const filter = variant === 'light' ? 'invert' : '';

    return (
        <Link href="/" className={`inline-flex min-w-0 shrink items-center ${className}`}>
            <img src={url} alt="Freedom" className={[imageClassName, filter].filter(Boolean).join(' ')} />
        </Link>
    );
}
