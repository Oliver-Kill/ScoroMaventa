<?php namespace App;

use ScoroMaventa\Scoro\Invoice;
use ScoroMaventa\Scoro\ScoroAPI;

class invoices extends Controller
{

    function index()
    {

        $scoroApi = new ScoroAPI();

        $this->invoices = $scoroApi->getInvoiceList([
            "modified_date" => [
                // 2021-05-10 means 2021-05-10 00:00:00!
                "from_date" => date('Y-m-d', strtotime('today - 1 days')),
                "to_date" => date('Y-m-d', strtotime('today + 1 days'))
            ]
        ]);
        usort($this->invoices, function ($a, $b) {
            return $b->modified_date <=> $a->modified_date;
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
        $invoiceData = $scoroApi->getInvoice($_POST['invoice_id']);
        //file_put_contents('.debug/testInvoice.json', json_encode($invoiceData));
        //$invoiceData = json_decode(file_get_contents('.debug/testInvoice.json'));
        Invoice::send($invoiceData);

    }

}