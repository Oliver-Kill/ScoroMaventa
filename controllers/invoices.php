<?php namespace App;

use ScoroMaventa\App\Invoice;
use ScoroMaventa\Scoro\ScoroAPI;

class invoices extends Controller
{

    function index()
    {

        $scoroApi = new ScoroAPI();

        $this->invoices = $scoroApi->getInvoiceList([
            "created_date" => [
                // 2021-05-10 means 2021-05-10 00:00:00!
                "from_date" => date('Y-m-d', strtotime('today - 14 days')),
                "to_date" => date('Y-m-d', strtotime('today + 1 days'))
            ]
        ]);
        usort($this->invoices, function ($a, $b) {
            return $b->created_date <=> $a->created_date;
        });

    }

    function view()
    {
        $scoroApi = new ScoroAPI();

        $this->invoice = $scoroApi->getInvoice($this->params[0]);

    }

    function AJAX_send()
    {
        if (empty($_POST['invoice_id'])) {
            stop(400, 'Invalid invoice_id');
        }
        $scoroApi = new ScoroAPI();
        //file_put_contents('.debug/testInvoice.json', json_encode($invoiceData));
        Invoice::send($scoroApi->getInvoice($_POST['invoice_id']));
        stop(200, 'Successfully sent e-invoice');

    }

}