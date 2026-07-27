import { Head } from '@inertiajs/react';
import { ResultShell } from './success';

interface Props {
    order: {
        number: string;
        statusLabel: string;
        total: number;
        customerEmail: string;
    };
}

export default function CheckoutFailure({ order }: Props) {
    return (
        <>
            <Head title="Pago no completado" />
            <ResultShell
                title="No se completó el pago"
                body={`El pago del pedido ${order.number} no se completó. Puedes volver al carrito e intentarlo de nuevo.`}
                total={order.total}
                status={order.statusLabel}
                tone="failure"
            />
        </>
    );
}
