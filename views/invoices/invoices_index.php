<style>
    .right {
        text-align: right !important;
    }
    .center {
        text-align: center !important;
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
                <tr data-href="invoices/<?= $invoice->id ?>" data-id="<?= $invoice->id ?>">
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
    $('.btn-send').on('click', function(){
        ajax('invoices/send', {invoice_id: $(this).parents('tr').data('id')})
        return false;
    })
</script>