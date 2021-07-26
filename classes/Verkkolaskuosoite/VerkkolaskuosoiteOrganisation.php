<?php namespace ScoroMaventa\Verkkolaskuosoite;


class VerkkolaskuosoiteOrganisation
{
    public $organizationEaddress;
    public $organization;

    public function __construct($attributes)
    {
        foreach ($attributes as $attribute => $value) {
            $this->$attribute = $value;
        }
    }

    public function getReceiveEAddress()
    {
        foreach ($this->organizationEaddress as $organizationEaddress) {
            if ($organizationEaddress->directionOfAddress === 'RECEIVE'
                && $organizationEaddress->ownerActive === true
                && $organizationEaddress->contextOfAddress === 'EINVOICE'
                //&& $organizationEaddress->serviceIdType === 'OVT' // Some r.y. had 'BIC' :/
            ) {
                debug('VLO: picked eAddress: '. print_r($organizationEaddress, true));
                return $organizationEaddress;
            }
        }
        return null;
    }

    public function getBusinessId()
    {
        if (!empty($this->organization->identifier[0])) {
            if (preg_match('/BUSINESSID:(\d+-\d)/', $this->organization->identifier[0], $matches)) {
                return $matches[1];
            }
        }
        return false;
    }
}