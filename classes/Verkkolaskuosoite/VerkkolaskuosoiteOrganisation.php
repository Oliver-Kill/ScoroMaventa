<?php namespace ScoroMaventa\Verkkolaskuosoite;


class VerkkolaskuosoiteOrganisation
{
    public object $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function getToIntermediator() {
        foreach ($this->data->organizationEaddress as $organizationEaddress) {
            if ($organizationEaddress->directionOfAddress === 'RECEIVE'
                && $organizationEaddress->ownerActive === true
                && $organizationEaddress->contextOfAddress === 'EINVOICE'
                && $organizationEaddress->serviceIdType === 'OVT') {
                return $organizationEaddress->serviceId;
            }
        }
    }
}