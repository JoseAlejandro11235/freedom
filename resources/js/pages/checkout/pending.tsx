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

export default function CheckoutPending({ order }: Props) {
    return (
        <>
            <Head title="Pago pendiente" />
            <ResultShell
                title="Pago pendiente"
                body={`Tu pedido ${order.number} está pendiente de confirmación. Te avisaremos a ${order.customerEmail}.`}
                total={order.total}
                status={order.statusLabel}
                tone="pending"
            />
        </>
    );
}
