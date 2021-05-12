<style>
    .right {
        text-align: right !important;
    }
    .center {
        text-align: center !important;
    }
    .loader {
        border: 4px solid #f3f3f3;
        border-radius: 50%;
        border-top: 4px solid #3498db;
        width: 24px;
        height: 24px;
        -webkit-animation: spin 2s linear infinite; /* Safari */
        animation: spin 2s linear infinite;
    }
    /* Safari */
    @-webkit-keyframes spin {
        0% { -webkit-transform: rotate(0deg); }
        100% { -webkit-transform: rotate(360deg); }
    }
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>
<div class="row">

    <h1>Invoices</h1>

    <div class="table-responsive">

        <table class="table table-striped table-bordered clickable-rows">

            <thead>
            <tr>
                <th></th>
                <th><?= __('Date modified') ?></th>
                <th class="center"><?= __('Number') ?></th>
                <th class="center"><?= __('Date') ?></th>
                <th><?= __('Company') ?></th>
                <th class="right"><?= __('Sum') ?></th>
                <th></th>
            </tr>

            </thead>

            <tbody>

            <?php $n = 1; foreach ($invoices as $invoice): ?>
                <tr data-href="invoices/<?= $invoice->id ?>" data-id="<?= $invoice->id ?>" data-number="<?= $invoice->no ?>">
                    <td><?=$n++?></td>
                    <td><?= strtr(substr($invoice->modified_date, 0, 16), 'T', ' ') ?></td>
                    <td class="center"><?= $invoice->no ?></td>
                    <td class="center"><?= substr($invoice->date, 0, 10) ?></td>
                    <td><?= $invoice->company_name ?></td>
                    <td class="right"><?= number_format($invoice->vat_sum + $invoice->sum, 2, ',', ' ')?></td></td>
                    <td><button type="button" class="btn btn-primary btn-send">Send</button></td>
                </tr>
            <?php endforeach; ?>

            </tbody>

        </table>

    </div>

</div>

<script>
    $('.btn-send').on('click', function () {

        let clickedBtn = $(this)

        // Create a spinner and insert it after the button
        let spinner = $('<div class="loader"></div>')
        spinner.insertAfter(clickedBtn);

        // Hide button
        clickedBtn.css('display', 'none');

        ajax('invoices/send', {
            invoice_id: clickedBtn.parents('tr').data('id')
        }, function (res) {

            // Remove spinned and make the button visible again
            spinner.remove();
            clickedBtn.css('display', 'inline-block');

            if (typeof res.data !== 'string') {
                show_error_modal('Server returned unexpected response: ' + JSON.stringify(res))
                return false;
            }

            if (res.data !== 'Successfully sent e-invoice') {
                show_error_modal('Server returned unexpected response: ' + res.data)
                return false;
            }

            alert('Sent ' + clickedBtn.parents('tr').data('number'))

        }, function (res) {

            // Remove spinned and make the button visible again
            spinner.remove();
            clickedBtn.css('display', 'inline-block');
            show_error_modal(res)

        })
        return false;
    })
</script>