<?php namespace ScoroMaventa\Scoro;

use Exception;

class ScoroInvoice
{

    public ?string $account_id;
    public ?object $company_address;
    public ?int $company_address_id;
    public ?int $company_id;
    public ?string $company_name;
    public ?string $created_date;
    public ?array $credited_invoices;
    public ?string $currency;
    public ?int $currency_rate;
    public ?array $custom_fields;
    public ?string $date;
    public ?string $deadline;
    public ?string $deleted_date;
    public ?string $description;
    public ?float $discount;
    public ?float $discount2;
    public ?float $discount3;
    public ?float $fine;
    public ?string $generation_type;
    public ?int $id;
    public ?int $is_deleted;
    public ?int $is_sent;
    public ?array $lines;
    public ?string $modified_date;
    public ?string $no;
    public ?array $order_id;
    public ?string $owner_email;
    public ?int $owner_id;
    public ?int $paid_sum;
    public ?string $payment_type;
    public ?int $person_id;
    public ?string $person_name;
    public ?int $prepayment_id;
    public ?float $prepayment_sum;
    public ?int $project_id;
    public ?string $project_name;
    public ?array $quote_id;
    public ?int $real_estate_id;
    public ?float $receivable_sum;
    public ?string $reference_no;
    public ?int $scheduled_invoice_id;
    public ?string $status;
    public ?float $sum;
    public ?float $vat;
    public ?string $vat_code;
    public ?int $vat_code_id;
    public ?float $vat_sum;

    /**
     * ScoroInvoice constructor.
     * @throws Exception
     */
    public function __construct($response)
    {
        if (empty($response->account_id) || empty($response->generation_type)) throw new Exception("Invalid invoice response from Scoro");

        foreach ($response as $attribute => $value) {
            $this->$attribute = $value;
        }

    }
}