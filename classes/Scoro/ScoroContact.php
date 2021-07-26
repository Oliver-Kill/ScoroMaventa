<?php namespace ScoroMaventa\Scoro;

use Exception;
use GuzzleHttp\Exception\GuzzleException;

class ScoroContact
{

    public const BUSINESS_ID = 'id_code';
    public ?array $addresses;
    public ?string $bankaccount;
    public ?string $birthday;
    public ?int $cat_id;
    public ?string $cat_name;
    public ?int $client_profile_id;
    public ?string $comments;
    public ?int $contact_id;
    public ?string $contact_type;
    public ?array $contact_users;
    public ?string $created_date;
    public ?array $custom_fields;
    public ?string $deleted_date;
    public ?string $id_code;
    public ?int $is_client;
    public ?int $is_deleted;
    public ?int $is_supplier;
    public ?string $lastname;
    public ?string $manager_email;
    public ?int $manager_id;
    public ?object $means_of_contact;
    public ?string $modified_date;
    public ?string $name;
    public ?string $permissions;
    public ?string $position;
    public ?string $reference_no;
    public ?string $related_companies;
    public ?string $search_name;
    public ?string $sex;
    public ?array $tags;
    public ?string $timezone;
    public ?string $vatno;

    /**
     * ScoroInvoice constructor.
     * @param $response
     * @throws Exception
     */
    public function __construct($response)
    {
        if (empty($response->contact_type)) throw new Exception("Invalid contact response from Scoro");

        foreach ($response as $attribute => $value) {
            $this->$attribute = $value;
        }

    }

    /**
     * @param object $contact
     * @param $businessId
     * @throws GuzzleException
     */
    public static function setBusinessId(object $contact, $businessId)
    {
        self::set($contact, ScoroContact::BUSINESS_ID, $businessId);
    }

    /**
     * @param object $contact
     * @param $field
     * @return null|string
     */
    public static function get(object $contact, $field)
    {
        return empty($contact->$field) ? null : $contact->$field;
    }

    public static function hasBusinessId(object $contact): bool
    {
        return !!ScoroContact::get($contact, ScoroContact::BUSINESS_ID);
    }

    /**
     * @param $contactId
     * @param $value
     * @throws GuzzleException
     */
    public static function set($contactId, $field, $value): void
    {
        $scoroApi = new ScoroAPI;
        $scoroApi->setContact($contactId, [$field => $value]);
        debug("Updated $field in Scoro to $value");
    }

    /**
     * @param object $contact
     * @param array $buyer
     */
    public static function getAddress(object $contact)
    {
        if (!empty($contact->addresses[0])) {
            $address = $contact->addresses[0];
            $streetAddress = $address->full_address;
            self::removeZipCityAndCountryFromAddress($address->zipcode, $address->city, $streetAddress);
            self::removeNewLineFromAddress($streetAddress);
            return [
                'address' => $streetAddress,
                'city' => $contact->addresses[0]->city,
                'postcode' => $contact->addresses[0]->zipcode
            ];
        }
    }

    /**
     * Remove part from address
     * @param string $part
     * @param string $address
     */
    public static function removeZipCityAndCountryFromAddress(string $zip, string $city, string &$address): void
    {
        $address = preg_replace("/,? ?$zip,? ?$city,? ?(Finland|Suomi)?$/", '', $address);
    }

    /**
     * Remove residues from removing parts of the address
     * @param $address
     */
    public static function removeNewLineFromAddress(&$address)
    {
        $address = preg_replace("/(\r\n)+/", ' ', $address);
    }
}