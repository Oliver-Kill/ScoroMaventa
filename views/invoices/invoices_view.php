<div class="row">

    <h1>Invoice <?= $invoice->no ?></h1>

    <table class="table table-striped table-bordered">
        <tr>
            <th>company_name</th>
            <td><?= $invoice->company_name ?></td>
        </tr>
        <tr>
            <th>date</th>
            <td><?= $invoice->date ?></td>
        </tr>
        <tr>
            <th>payment_type</th>
            <td><?= $invoice->payment_type ?></tr>
        <tr>
            <th>fine</th>
            <td><?= $invoice->fine ?></td>
        </tr>
        <tr>
            <th>quote_id</th>
            <td><?= implode(',', $invoice->quote_id) ?></td>
        </tr>
        <tr>
            <th>order_id</th>
            <td><?= implode(',', $invoice->order_id) ?></td>
        </tr>
        <tr>
            <th>prepayment_id</th>
            <td><?= $invoice->prepayment_id ?></td>
        </tr>
        <tr>
            <th>credited_invoices</th>
            <td><?= implode(',', $invoice->credited_invoices) ?></td>
        </tr>
        <tr>
            <th>prepayment_sum</th>
            <td><?= $invoice->prepayment_sum ?></td>
        </tr>
        <tr>
            <th>real_estate_id</th>
            <td><?= $invoice->real_estate_id ?></td>
        </tr>
        <tr>
            <th>reference_no</th>
            <td><?= $invoice->reference_no ?></td>
        </tr>

        <tr>
            <th>person_name</th>
            <td><?= $invoice->person_name ?></td>
        </tr>
        <tr>
            <th>project_name</th>
            <td><?= $invoice->project_name ?></td>
        </tr>
        <tr>
            <th>owner_email</th>
            <td><?= $invoice->owner_email ?></td>
        </tr>
        <tr>
            <th>vat_code</th>
            <td><?= $invoice->vat_code ?></td>
        </tr>
        <tr>
            <th>currency_rate</th>
            <td><?= $invoice->currency_rate ?></td>
        </tr>
        <tr>
            <th>paid_sum</th>
            <td><?= $invoice->paid_sum ?></td>
        </tr>
        <tr>
            <th>receivable_sum</th>
            <td><?= $invoice->receivable_sum ?></td>
        </tr>
        <tr>
            <th>generation_type</th>
            <td><?= $invoice->generation_type ?></td>
        </tr>
        <tr>
            <th>scheduled_invoice_id</th>
            <td><?= $invoice->scheduled_invoice_id ?></td>
        </tr>
        <tr>
            <th>id</th>
            <td><?= $invoice->id ?></td>
        </tr>
        <tr>
            <th>discount</th>
            <td><?= $invoice->discount ?></td>
        </tr>
        <tr>
            <th>discount2</th>
            <td><?= $invoice->discount2 ?></td>
        </tr>
        <tr>
            <th>discount3</th>
            <td><?= $invoice->discount3 ?></td>
        </tr>
        <tr>
            <th>sum</th>
            <td><?= $invoice->sum ?></td>
        </tr>
        <tr>
            <th>vat_sum</th>
            <td><?= $invoice->vat_sum ?></td>
        </tr>
        <tr>
            <th>vat</th>
            <td><?= $invoice->vat ?></td>
        </tr>
        <tr>
            <th>vat_code_id</th>
            <td><?= $invoice->vat_code_id ?></td>
        </tr>
        <tr>
            <th>company_id</th>
            <td><?= $invoice->company_id ?></td>
        </tr>
        <tr>
            <th>person_id</th>
            <td><?= $invoice->person_id ?></td>
        </tr>
        <tr>
            <th>company_address_id</th>
            <td><?= $invoice->company_address_id ?></td>
        </tr>
        <tr>
            <th>project_id</th>
            <td><?= $invoice->project_id ?></td>
        </tr>
        <tr>
            <th>currency</th>
            <td><?= $invoice->currency ?></td>
        </tr>
        <tr>
            <th>owner_id</th>
            <td><?= $invoice->owner_id ?></td>
        </tr>
        <tr>
            <th>date</th>
            <td><?= $invoice->date ?></td>
        </tr>
        <tr>
            <th>deadline</th>
            <td><?= $invoice->deadline ?></td>
        </tr>
        <tr>
            <th>status</th>
            <td><?= $invoice->status ?></td>
        </tr>
        <tr>
            <th>description</th>
            <td>
                <pre><?= $invoice->description ?></pre>
            </td>
        </tr>
        <tr>
            <th>account_id</th>
            <td><?= $invoice->account_id ?></td>
        </tr>
        <tr>
            <th>is_sent</th>
            <td><?= $invoice->is_sent ?></td>
        </tr>
        <tr>
            <th>lines</th>
            <td>
                <table>
                    <?php foreach ($invoice->lines as $key => $line): ?>
                        <tr>
                            <td>
                                <table>

                                    <tr>
                                        <th>product_name</th>
                                        <td><?= $line->product_name ?></td>
                                    </tr>

                                    <tr>
                                        <th>supplier_name</th>
                                        <td><?= $line->supplier_name ?></td>
                                    </tr>
                                    <tr>
                                        <th>project_name</th>
                                        <td><?= $line->project_name ?></td>
                                    </tr>
                                    <tr>
                                        <th>id</th>
                                        <td><?= $line->id ?></td>
                                    </tr>
                                    <tr>
                                        <th>product_id</th>
                                        <td><?= $line->product_id ?></td>
                                    </tr>
                                    <tr>
                                        <th>comment</th>
                                        <td><pre><?= $line->comment ?></pre></td>
                                    </tr>
                                    <tr>
                                        <th>price</th>
                                        <td><?= $line->price ?></td>
                                    </tr>
                                    <tr>
                                        <th>amount</th>
                                        <td><?= $line->amount ?></td>
                                    </tr>
                                    <tr>
                                        <th>sum</th>
                                        <td><?= $line->sum ?></td>
                                    </tr>
                                    <tr>
                                        <th>unit</th>
                                        <td><?= $line->unit ?></td>
                                    </tr>
                                    <tr>
                                        <th>cost</th>
                                        <td><?= $line->cost ?></td>
                                    </tr>
                                    <tr>
                                        <th>supplier_id</th>
                                        <td><?= $line->supplier_id ?></td>
                                    </tr>
                                    <tr>
                                        <th>doer_id</th>
                                        <td><?= $line->doer_id ?></td>
                                    </tr>
                                    <tr>
                                        <th>is_internal</th>
                                        <td><?= $line->is_internal ?></td>
                                    </tr>
                                    <tr>
                                        <th>project_id</th>
                                        <td><?= $line->project_id ?></td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </table>
            </td>
        </tr>
    </table>

</div>


