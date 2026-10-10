<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Orders\InvoicePdf;
use Illuminate\Http\Response;

/** Invoice as a PDF for admin and customer. Requests that have no offer yet have no invoice. */
class InvoiceController extends Controller
{
    public function show(Order $order, InvoicePdf $pdf): Response
    {
        $this->authorize('view', $order);
        abort_unless($order->items()->exists() && ! $order->status->isLead(), 404, 'There is no invoice for this order yet.');

        return response($pdf->render($order), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $pdf->filename($order) . '"',
            'Cache-Control' => 'private, max-age=0, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
