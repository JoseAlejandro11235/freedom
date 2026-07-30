import { Head, router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

interface CulqiPayProps {
    order: {
        id: string;
        number: string;
        total: number;
        currency: string;
        customerName: string;
        customerEmail: string;
        items: Array<{
            id: string;
            name: string;
            quantity: number;
            lineTotal: number;
        }>;
    };
    culqiPublicKey: string;
}

declare global {
    interface Window {
        Culqi?: {
            publicKey: string;
            token?: { id: string } | null;
            order?: unknown;
            error?: { user_message?: string; merchant_message?: string } | null;
            settings: (options: {
                title: string;
                currency: string;
                amount: number;
            }) => void;
            options?: (options: Record<string, unknown>) => void;
            open: () => void;
            close: () => void;
        };
        culqi?: () => void;
    }
}

const CULQI_SCRIPT_SRC = 'https://checkout.culqi.com/js/v4';

function loadCulqiScript(): Promise<void> {
    if (window.Culqi) {
        return Promise.resolve();
    }

    const existing = document.querySelector<HTMLScriptElement>(`script[src="${CULQI_SCRIPT_SRC}"]`);

    if (existing) {
        return new Promise((resolve, reject) => {
            existing.addEventListener('load', () => resolve(), { once: true });
            existing.addEventListener('error', () => reject(new Error('No se pudo cargar Culqi Checkout.')), {
                once: true,
            });
        });
    }

    return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = CULQI_SCRIPT_SRC;
        script.async = true;
        script.onload = () => resolve();
        script.onerror = () => reject(new Error('No se pudo cargar Culqi Checkout.'));
        document.head.appendChild(script);
    });
}

export default function CulqiPay({ order, culqiPublicKey }: CulqiPayProps) {
    const [ready, setReady] = useState(false);
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const submittingRef = useRef(false);

    const amountCents = Math.round(order.total * 100);

    useEffect(() => {
        let cancelled = false;

        window.culqi = () => {
            const Culqi = window.Culqi;

            if (!Culqi) {
                return;
            }

            if (Culqi.token?.id) {
                const token = Culqi.token.id;
                Culqi.close();

                if (submittingRef.current) {
                    return;
                }

                submittingRef.current = true;
                setProcessing(true);
                setError(null);

                router.post(
                    `/checkout/orders/${order.id}/culqi/charge`,
                    { token },
                    {
                        onError: () => {
                            submittingRef.current = false;
                            setProcessing(false);
                            setError('No se pudo confirmar el pago. Inténtalo de nuevo.');
                        },
                        onFinish: () => {
                            // Keep processing true on success (navigation away).
                        },
                    },
                );

                return;
            }

            if (Culqi.error) {
                Culqi.close();
                setProcessing(false);
                setError(
                    Culqi.error.user_message ||
                        Culqi.error.merchant_message ||
                        'No se pudo generar el token de pago.',
                );
            }
        };

        loadCulqiScript()
            .then(() => {
                if (cancelled) {
                    return;
                }

                if (!window.Culqi) {
                    setError('Culqi Checkout no está disponible.');
                    return;
                }

                window.Culqi.publicKey = culqiPublicKey;
                window.Culqi.settings({
                    title: 'Freedom',
                    currency: order.currency || 'PEN',
                    amount: amountCents,
                });
                setReady(true);
            })
            .catch((err: Error) => {
                if (!cancelled) {
                    setError(err.message);
                }
            });

        return () => {
            cancelled = true;
            delete window.culqi;
        };
    }, [amountCents, culqiPublicKey, order.currency, order.id]);

    const openCheckout = () => {
        if (!window.Culqi || !ready || processing) {
            return;
        }

        setError(null);
        window.Culqi.publicKey = culqiPublicKey;
        window.Culqi.settings({
            title: 'Freedom',
            currency: order.currency || 'PEN',
            amount: amountCents,
        });
        window.Culqi.open();
    };

    return (
        <>
            <Head title={`Pagar ${order.number}`} />

            <div className="flex min-h-screen items-center justify-center bg-neutral-100 px-4 py-10">
                <div className="w-full max-w-md border border-neutral-200 bg-white p-6 shadow-sm">
                    <p className="text-xs font-semibold tracking-[0.2em] text-neutral-500 uppercase">Pago seguro</p>
                    <h1 className="mt-2 font-serif text-2xl uppercase">Pagar con Culqi</h1>
                    <p className="mt-2 text-sm text-neutral-600">
                        Pedido <strong>{order.number}</strong> · {order.customerName}
                    </p>
                    <p className="mt-1 text-sm text-neutral-500">{order.customerEmail}</p>

                    <ul className="mt-6 space-y-2 border-y border-neutral-100 py-4 text-sm">
                        {order.items.map((item) => (
                            <li key={item.id} className="flex justify-between gap-3">
                                <span>
                                    {item.name} × {item.quantity}
                                </span>
                                <span>S/{item.lineTotal.toFixed(2)}</span>
                            </li>
                        ))}
                    </ul>

                    <div className="mt-4 flex items-center justify-between text-sm">
                        <span>Total</span>
                        <span className="text-lg font-semibold">S/{order.total.toFixed(2)}</span>
                    </div>

                    {error && <p className="mt-4 text-sm text-red-600">{error}</p>}

                    <div className="mt-6 grid gap-3">
                        <button
                            type="button"
                            disabled={!ready || processing}
                            className="bg-black px-4 py-3 text-xs font-semibold tracking-wide text-white uppercase disabled:cursor-not-allowed disabled:bg-neutral-400"
                            onClick={openCheckout}
                        >
                            {processing ? 'Procesando…' : ready ? 'Pagar ahora' : 'Cargando Culqi…'}
                        </button>
                        <a
                            href="/checkout"
                            className="border border-neutral-300 px-4 py-3 text-center text-xs font-semibold tracking-wide text-neutral-700 uppercase"
                        >
                            Volver al checkout
                        </a>
                    </div>

                    <p className="mt-4 text-center text-xs text-neutral-400">
                        Tarjetas y Yape · Procesado por Culqi
                    </p>
                </div>
            </div>
        </>
    );
}
