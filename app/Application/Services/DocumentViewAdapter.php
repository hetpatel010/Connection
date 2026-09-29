<?php

namespace App\Application\Services;

use App\Models\PurchaseBill;
use App\Models\SalesInvoice;

class DocumentViewAdapter
{
    public static function purchaseBillAsInvoice(PurchaseBill $bill): SalesInvoice
    {
        $invoice = new SalesInvoice([
            'invoice_number' => $bill->bill_number,
            'invoice_date' => $bill->bill_date,
            'due_date' => $bill->due_date,
            'settlement_mode' => $bill->settlement_mode,
            'subtotal_amount' => $bill->subtotal_amount,
            'tax_amount' => $bill->tax_amount,
            'total_amount' => $bill->total_amount,
            'notes' => $bill->notes,
            'reference' => $bill->reference,
            'billing_type' => $bill->billing_type,
            'document_template' => $bill->document_template,
        ]);

        $invoice->setRelation('party', $bill->party);
        $invoice->setRelation('lines', $bill->lines);

        return $invoice;
    }
}
