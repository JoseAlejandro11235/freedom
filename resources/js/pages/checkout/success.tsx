import { Head, Link } from '@inertiajs/react';

interface OrderResultProps {
    order: {
        number: string;
        statusLabel: string;
        total: number;
        customerEmail: string;
    };
}

export default function CheckoutSuccess({ order }: OrderResultProps) {
    return (
        <>
            <Head title="Pago exitoso" />
            <ResultShell
                title="¡Pago confirmado!"
                body={`Tu pedido ${order.number} fue pagado. Enviaremos la confirmación a ${order.customerEmail}.`}
                total={order.total}
                status={order.statusLabel}
                tone="success"
            />
        </>
    );
}

export function ResultShell({
    title,
    body,
    total,
    status,
    tone,
}: {
    title: string;
    body: string;
    total: number;
    status: string;
    tone: 'success' | 'pending' | 'failure';
}) {
    const accent =
        tone === 'success' ? 'text-emerald-700' : tone === 'pending' ? 'text-amber-700' : 'text-red-700';

    return (
        <div className="flex min-h-screen items-center justify-center bg-white px-4 py-10">
            <div className="w-full max-w-lg text-center">
                <p className={`text-xs font-semibold tracking-[0.25em] uppercase ${accent}`}>{status}</p>
                <h1 className="mt-3 font-serif text-3xl uppercase">{title}</h1>
                <p className="mt-3 text-sm text-neutral-600">{body}</p>
                <p className="mt-6 text-lg font-semibold">Total S/{total.toFixed(2)}</p>
                <Link
                    href="/"
                    className="mt-8 inline-block bg-black px-8 py-3 text-xs font-semibold tracking-wide text-white uppercase"
                >
                    Volver a la tienda
                </Link>
            </div>
        </div>
    );
}
