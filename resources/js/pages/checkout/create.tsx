import { SentuaFooter } from '@/components/sentua/footer';
import { SentuaHeader } from '@/components/sentua/header';
import type { CartSummary } from '@/types/sentua';
import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

interface CheckoutCreateProps {
    cart: CartSummary;
    paymentProvider: string;
}

export default function CheckoutCreate({ cart, paymentProvider }: CheckoutCreateProps) {
    const { data, setData, post, processing, errors } = useForm({
        customer_name: '',
        customer_email: '',
        customer_phone: '',
        shipping_address: '',
        shipping_city: '',
        shipping_district: '',
        shipping_notes: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post('/checkout');
    };

    return (
        <>
            <Head title="Checkout — Freedom" />

            <div className="min-h-screen bg-white text-neutral-900" style={{ fontFamily: "'DM Sans', sans-serif" }}>
                <SentuaHeader />

                <main className="mx-auto max-w-6xl px-4 py-10 lg:py-14">
                    <h1 className="font-serif text-3xl tracking-wide uppercase">Finalizar compra</h1>
                    <p className="mt-2 text-sm text-neutral-500">
                        Completa tus datos para continuar al pago
                        {paymentProvider === 'fake' ? ' (modo prueba local).' : ' con Mercado Pago.'}
                    </p>

                    <div className="mt-10 grid gap-10 lg:grid-cols-[1.2fr_0.8fr]">
                        <form onSubmit={submit} className="space-y-5">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <Field
                                    label="Nombre completo"
                                    error={errors.customer_name}
                                    value={data.customer_name}
                                    onChange={(value) => setData('customer_name', value)}
                                    required
                                />
                                <Field
                                    label="Correo"
                                    type="email"
                                    error={errors.customer_email}
                                    value={data.customer_email}
                                    onChange={(value) => setData('customer_email', value)}
                                    required
                                />
                            </div>
                            <Field
                                label="Teléfono"
                                error={errors.customer_phone}
                                value={data.customer_phone}
                                onChange={(value) => setData('customer_phone', value)}
                            />
                            <Field
                                label="Dirección"
                                error={errors.shipping_address}
                                value={data.shipping_address}
                                onChange={(value) => setData('shipping_address', value)}
                                required
                            />
                            <div className="grid gap-4 sm:grid-cols-2">
                                <Field
                                    label="Ciudad"
                                    error={errors.shipping_city}
                                    value={data.shipping_city}
                                    onChange={(value) => setData('shipping_city', value)}
                                    required
                                />
                                <Field
                                    label="Distrito"
                                    error={errors.shipping_district}
                                    value={data.shipping_district}
                                    onChange={(value) => setData('shipping_district', value)}
                                />
                            </div>
                            <div>
                                <label className="mb-1.5 block text-xs font-semibold tracking-wide text-neutral-600 uppercase">
                                    Notas de entrega
                                </label>
                                <textarea
                                    value={data.shipping_notes}
                                    onChange={(event) => setData('shipping_notes', event.target.value)}
                                    rows={3}
                                    className="w-full border border-neutral-300 px-3 py-2 text-sm outline-none focus:border-black"
                                />
                                {errors.shipping_notes && (
                                    <p className="mt-1 text-xs text-red-600">{errors.shipping_notes}</p>
                                )}
                            </div>

                            {errors.cart && <p className="text-sm text-red-600">{errors.cart}</p>}

                            <button
                                type="submit"
                                disabled={processing}
                                className="w-full bg-black px-6 py-3 text-xs font-semibold tracking-wide text-white uppercase disabled:bg-neutral-400 sm:w-auto"
                            >
                                {processing ? 'Procesando…' : 'Ir a pagar'}
                            </button>
                        </form>

                        <aside className="h-fit border border-neutral-200 p-5">
                            <h2 className="text-sm font-semibold tracking-wide uppercase">Resumen</h2>
                            <ul className="mt-4 space-y-3">
                                {cart.items.map((item) => (
                                    <li key={item.id} className="flex justify-between gap-3 text-sm">
                                        <span className="min-w-0">
                                            <span className="line-clamp-2 text-neutral-900">
                                                {item.name} × {item.quantity}
                                            </span>
                                            <span className="block text-xs text-neutral-500">{item.brand}</span>
                                        </span>
                                        <span className="shrink-0 font-medium">S/{item.lineTotal.toFixed(2)}</span>
                                    </li>
                                ))}
                            </ul>
                            <div className="mt-5 flex items-center justify-between border-t border-neutral-200 pt-4 text-sm">
                                <span className="text-neutral-600">Total</span>
                                <span className="text-base font-semibold">S/{cart.subtotal.toFixed(2)}</span>
                            </div>
                        </aside>
                    </div>
                </main>

                <SentuaFooter />
            </div>
        </>
    );
}

function Field({
    label,
    value,
    onChange,
    error,
    type = 'text',
    required = false,
}: {
    label: string;
    value: string;
    onChange: (value: string) => void;
    error?: string;
    type?: string;
    required?: boolean;
}) {
    return (
        <div>
            <label className="mb-1.5 block text-xs font-semibold tracking-wide text-neutral-600 uppercase">
                {label}
            </label>
            <input
                type={type}
                value={value}
                required={required}
                onChange={(event) => onChange(event.target.value)}
                className="w-full border border-neutral-300 px-3 py-2 text-sm outline-none focus:border-black"
            />
            {error && <p className="mt-1 text-xs text-red-600">{error}</p>}
        </div>
    );
}
