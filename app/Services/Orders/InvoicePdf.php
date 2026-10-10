<?php

namespace App\Services\Orders;

use App\Models\Order;
use Dompdf\Dompdf;
use Dompdf\Options;

/** Renders the invoice as a PDF (pure PHP, works on shared hosting). */
class InvoicePdf
{
    public function render(Order $order): string
    {
        $order->loadMissing(['customer', 'items']);

        $logo = public_path('assets/img/logo.png');
        $html = view('pdf.invoice', [
            'order' => $order,
            'logo' => is_file($logo) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logo)) : null,
        ])->render();

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isHtml5ParserEnabled', true);

        $pdf = new Dompdf($options);
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->setPaper('A4');
        $pdf->render();

        return $pdf->output();
    }

    public function filename(Order $order): string
    {
        return 'Invoice-' . $order->number . '.pdf';
    }
}
