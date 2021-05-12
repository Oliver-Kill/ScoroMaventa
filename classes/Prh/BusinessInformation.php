<?php namespace ScoroMaventa\Prh;

use Exception;

/**
 * Class containing data received from PRH API about single business.
 * @link https://avoindata.prh.fi/ytj.html
 */
class BusinessInformation
{

    public string $businessId;
    public string $name;
    public string $registrationDate;
    public string $companyForm;
    public ?string $detailsUri;
    public array $liquidations;
    public array $names;
    public array $auxiliaryNames;
    public array $addresses;
    public array $companyForms;
    public array $businessLines;
    public array $languages;
    public array $registedOffices; // Typo in Finnish Patent and Registration Office API
    public array $contactDetails;
    public array $registeredEntries;
    public array $businessIdChanges;

    const ERROR_NO_MATCH = 1;


    /**
     * Constructor takes business data received from BusinessLookup::findByBusinessId().
     *
     * @param object $data Business data
     */
    public function __construct(object $data)
    {
        foreach ($data as $field_name => $value) {
            $this->$field_name = $value;
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getAddressInfo()
    {
        $result = [];
        foreach ($this->addresses as $address) {
            if ($address->type == Address::TYPE_POSTAL && $address->version === Address::VERSION_CURRENT) {
                $result[0] = $address;
            } elseif ($address->type == Address::TYPE_VISIT && $address->version === Address::VERSION_CURRENT) {
                $result[1] = $address;
            }
        }

        if (empty($result)) {
            throw new Exception("Unable to retrieve any address for $this->name from Finnish Business Information System");
        }

        return $result;
    }

}

