import { Head, router } from '@inertiajs/react';

interface FakePayProps {
    order: {
        id: string;
        number: string;
        total: number;
        customerName: string;
        customerEmail: string;
        items: Array<{
            id: string;
            name: string;
            quantity: number;
            lineTotal: number;
        }>;
    };
}

export default function FakePay({ order }: FakePayProps) {
    return (
        <>
            <Head title={`Pagar ${order.number}`} />

            <div className="flex min-h-screen items-center justify-center bg-neutral-100 px-4 py-10">
                <div className="w-full max-w-md border border-neutral-200 bg-white p-6 shadow-sm">
                    <p className="text-xs font-semibold tracking-[0.2em] text-amber-700 uppercase">Pago de prueba</p>
                    <h1 className="mt-2 font-serif text-2xl uppercase">Simular pago</h1>
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

                    <div className="mt-6 grid gap-3">
                        <button
                            type="button"
                            className="bg-black px-4 py-3 text-xs font-semibold tracking-wide text-white uppercase"
                            onClick={() =>
                                router.post(`/checkout/orders/${order.id}/pay`, { approved: true })
                            }
                        >
                            Aprobar pago
                        </button>
                        <button
                            type="button"
                            className="border border-neutral-300 px-4 py-3 text-xs font-semibold tracking-wide text-neutral-700 uppercase"
                            onClick={() =>
                                router.post(`/checkout/orders/${order.id}/pay`, { approved: false })
                            }
                        >
                            Rechazar pago
                        </button>
                    </div>
                </div>
            </div>
        </>
    );
}
