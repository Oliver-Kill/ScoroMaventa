<?php namespace ScoroMaventa\Finvoice;

class FinvoiceSettings
{
    public object $from;
    public object $to;
    public object $invoice;

    public function __construct(array $options)
    {
        foreach ($options as $option => $value) {
            $this->$option = (object)$value;
        }
    }
}
