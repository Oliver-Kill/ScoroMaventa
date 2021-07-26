<?php namespace ScoroMaventa\App;

use App\Application;
use DragonBe\Vies\Vies;
use Exception;
use ScoroMaventa\Finvoice\Finvoice;
use ScoroMaventa\Finvoice\FinvoiceSettings;
use ScoroMaventa\Maventa\MaventaAPI;
use ScoroMaventa\Prh\BusinessId;
use ScoroMaventa\Prh\BusinessInformation;
use ScoroMaventa\Prh\PrhAPI;
use ScoroMaventa\Prh\PrhBusinessNotFoundException;
use ScoroMaventa\Scoro\ScoroAPI;
use ScoroMaventa\Scoro\ScoroContact;
use ScoroMaventa\Scoro\ScoroInvoice;
use ScoroMaventa\Verkkolaskuosoite\VerkkolaskuosoiteAPI;

class Invoice
{
    private static $vlo = null;

    public static function send(ScoroInvoice $invoice)
    {
        $scoroApi = new ScoroAPI();
        $scoroCompany = $scoroApi->getContact($invoice->company_id);

        self::validateCountry($invoice);
        self::validateExistingBusinessId($scoroCompany);

        $buyer = self::initBuyer($scoroCompany);

        try {
            $buyerPrhData = self::getCustomerPrhData($scoroCompany);
            $scoroCompany->id_code = $buyerPrhData->businessId; // (Over)write id_code to $contact
            Invoice::setAddressFromPrh($buyerPrhData, $buyer);
            self::updateAddressInScoro($scoroCompany, $buyer, $scoroApi);

        } catch (Exception $e) {

            debug("WARNING: PRH CALL FAILED: ".$e->getMessage());

            // Since Prh is unavailable, use Scoro data for address
            self::setAddressFromScoro($buyer, ScoroContact::getAddress($scoroCompany));

            // If Scoro does not have business Id, try to get it from vlo
            if (empty($scoroCompany->id_code)) {
                try {
                    $vlo = self::getVerkkolaskuOsoiteData($scoroCompany);
                    $scoroCompany->id_code = $vlo->getBusinessId();
                } catch (Exception $e) {
                    debug("WARNING: VLO CALL FAILED: ".$e->getMessage());
                    debug("Since both PRH and VLO calls failed and there is no Y-tunnus in Scoro, we cannot continue.");
                    stop(500,"Since both PRH and VLO calls failed and there is no Y-tunnus in Scoro, we cannot continue.");
                }
            }

        }

        $buyer['identifier'] = BusinessId::toOvt($scoroCompany->id_code);
        $buyer['name'] = $scoroCompany->name;
        $buyer['business_id'] = $scoroCompany->id_code;
        $buyer['contact_name'] = $invoice->person_name;

        self::handleVatNo($scoroCompany, $scoroApi);
        self::setBuyerReference($invoice);
        self::moveSubheadingDataToLineComments($invoice);

        $finvoice = new Finvoice(new FinvoiceSettings([
                // Seller
                'from' => [
                    'identifier' => SELLER_ORGANISATION_OVT,
                    'identifier_scheme_id' => '0037',
                    'intermediator' => SELLER_ORGANISATION_OPERATOR_OVT,
                    'IBAN' => SELLER_ACCOUNT_ID,
                    'BIC' => SELLER_BIC,
                    'name' => SELLER_ORGANISATION_NAME,
                    'tax_code' => SELLER_TAX_CODE,
                    'business_id' => SELLER_BUSINESS_ID,
                    'address' => SELLER_STREET_NAME,
                    'postcode' => SELLER_POSTCODE_IDENTIFIER,
                    'city' => SELLER_TOWN_NAME,
                    'email' => $invoice->owner_email,
                    'phone' => SELLER_PHONE_NUMBER_IDENTIFIER
                ],
                // Buyer
                'to' => $buyer,
                'invoice' => $invoice
            ]
        ));

        self::writeXmlToDebugFile($finvoice);

        $maventa = new MaventaAPI();
        debug("Created Maventa API instance");
        $einvoice = $maventa->sendNewInvoice($finvoice->getXML());
        debug("Sent e-invoice $einvoice->number dated $einvoice->date for {$einvoice->recipient->name} to Maventa. Result: $einvoice->status");

        if (!empty($einvoice->status) && $einvoice->status === 'PENDING') {
            debug("Successfully sent e-invoice {$einvoice->number} to {$einvoice->recipient->name} using operator {$einvoice->recipient->operator}");
        } else {
            throw new Exception("Unexpected response from Maventa: " . print_r($einvoice, true));
        }


    }

    /**
     * @param $buyer
     * @param $address
     * @throws Exception
     */
    protected static function setAddressFromScoro(&$buyer, $address): void
    {
        debug("Getting address from Scoro");
        if (empty($address['address']) || empty($address['city']) || empty($address['postcode'])) {
            throw new Exception("Buyer address is incomplete in Scoro");
        }
        debug("Address from Scoro: " . print_r($address,1));
        $buyer = array_merge($buyer, $address);
    }

    /**
     * Returns true if input is a real registered VAT number
     * @param string $vatNumber
     * @return bool
     * @throws \DragonBe\Vies\ViesException
     * @throws \DragonBe\Vies\ViesServiceException
     */
    static function vatNumberIsValid(string $vatNumber): bool
    {
        if (!preg_match('/^(?=([A-Za-z]{2,4}))\1(?![\W_]+$)(?=.{2,12}$)[-_ 0-9]*(?:[a-zA-Z][-_ 0-9]*){0,2}$/', $vatNumber)) {
            return false;
        }

        $vies = new Vies();

        if (false === $vies->getHeartBeat()->isAlive()) {

            throw new Exception("Unable to validate VAT number because VIES service is not available at the moment");

        }

        return $vies->validateVat(substr($vatNumber, 0, 2), substr($vatNumber, 2))->isValid();

    }

    static function countOfCommentLines($lines)
    {
        return array_reduce($lines, function ($carry, $item) {
            $carry += $item->product_id == -1 ? 1 : 0;
            return $carry;
        });
    }

    /**
     * Clones comment from subheading lines to the following lines until the next subheading and deletes the subheading.
     * @param $invoice
     */
    static function moveSubheadingDataToLineComments(&$invoice)
    {

        foreach ($invoice->lines as $lineNumber => $line) {

            // Capture section comment for following rows
            if ($line->product_id === -1) {

                // Fill the comment of the next lines with this comment
                for ($n = $lineNumber + 1; $n <= count($invoice->lines) - 1; $n++) {

                    // Break the for loop when hitting the next subheading
                    if ($invoice->lines[$n]->product_id === -1) {
                        debug('Hit the next subheading. Breaking off the loop.');
                        break;
                    }

                    // Assign comment to line
                    $invoice->lines[$n]->comment = $line->comment;
                    debug("Assigned comment \"{$line->comment}\" to line");
                }

                // Delete the comment line
                unset($invoice->lines[$lineNumber]);

            }
        }

        // Renumber invoice lines after removing comment lines
        $invoice->lines = array_values($invoice->lines);
    }

    /**
     * Moves the comment from subheading to buyer reference if there is exactly one comment line and it's the first line
     * @param object $invoice
     * @return bool
     */
    static function setBuyerReference(object &$invoice): bool
    {
        if (self::countOfCommentLines($invoice->lines) === 1 && $invoice->lines[0]->product_id === -1) {

            foreach ($invoice->lines as $lineNumber => $line) {

                if ($line->product_id === -1) {

                    // Set buyer reference
                    $invoice->buyer_reference_no = $line->comment;

                    // Delete this line from the invoice
                    unset($invoice->lines[$lineNumber]);
                }
            }

            // Renumber lines
            $invoice->lines = array_values($invoice->lines);

            return true;

        }

        return false;
    }

    static function getOvtFromBusinessId($businessId): string
    {
        return '0037' . stripNonNumbers($businessId);
    }

    static function getVatIdFromBusinessId($businessId): string
    {
        return 'FI' . stripNonNumbers($businessId);
    }

    /**
     * @param ScoroInvoice $scoroInvoice
     * @throws Exception
     */
    public static function validateCountry(ScoroInvoice $scoroInvoice): void
    {
        $country = $scoroInvoice->company_address->country;

        if (!empty($country) && $country != 'fin') {
            throw new Exception("Not sending e-invoice $scoroInvoice->no to $scoroInvoice->company_name because the company address country was not Finland");
        }

        debug("Country was valid");
    }

    /**
     * @param object $contact
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function initBuyer(object $contact): array
    {
        $intermediator = null;

        if (INCLUDE_BUYER_INTERMEDIATOR) {
            try {
                $vlo = self::getVerkkolaskuOsoiteData($contact);
                $eAddress = $vlo->getReceiveEAddress();
                $intermediator = $eAddress->serviceId;
            } catch (Exception $e) {
                debug("ERROR: VLO: obtaining data failed: ". $e->getMessage());
                if (sentryDsnIsSet()) {
                    Application::sendExceptionToSentry($e, 'ScoroContact', $contact);
                }
                $intermediator = null;
            }
        }

        $buyer = [
            'identifier_scheme_id' => '0037',
            'intermediator' => $intermediator,
            'IBAN' => null,
            'BIC' => null,
        ];
        return $buyer;
    }

    /**
     * @param $contact
     * @param string $businessId
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public
    static function addBusinessIdToScoro($contact, string $businessId): void
    {
        ScoroContact::set($contact->contact_id, 'id_code', $businessId);
    }

    /**
     * @param \ScoroMaventa\Prh\BusinessInformation $prh
     * @param $buyer
     * @throws Exception
     */
    public
    static function setAddressFromPrh(\ScoroMaventa\Prh\BusinessInformation $prh, &$buyer)
    {
        $buyerPrhAddresses = $prh->getAddressInfo();
        $buyerPrhPostalAddress = $buyerPrhAddresses[0];
        $buyer['address'] = $buyerPrhPostalAddress->street;
        $buyer['postcode'] = $buyerPrhPostalAddress->postCode;
        $buyer['city'] = $buyerPrhPostalAddress->city;


    }

    /**
     * @param object $contact
     * @throws PrhBusinessNotFoundException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function getCustomerPrhData(object $contact): BusinessInformation
    {
        if (ScoroContact::hasBusinessId($contact)) {
            debug("Using Scoro supplied y-tunnus ({$contact->id_code})");
            $prh = PrhAPI::findByBusinessId($contact->id_code);
        } else {
            debug("Scoro did not provide y-tunnus");
            $prh = PrhAPI::findByName($contact->name);
            self::addBusinessIdToScoro($contact, $prh->businessId);
        }
        return $prh;
    }

    /**
     * @param object $contact
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function getVerkkolaskuOsoiteData(object &$contact)
    {
        if (!empty(self::$vlo)) {
            debug("Re-using vlo");
            return self::$vlo;
        }

        debug("Trying Verkkolaskuosoite...");

        if (ScoroContact::hasBusinessId($contact)) {

            $customerVerkkolaskuosoiteData = VerkkolaskuosoiteAPI::findOrganisation($contact->id_code);

        } else {

            $customerVerkkolaskuosoiteData = VerkkolaskuosoiteAPI::findOrganisation($contact->name);

            if (!empty($customerVerkkolaskuosoiteData->data->organization->identifier[0])) {
                if (preg_match('/BUSINESSID:(\d+-\d)/', $customerVerkkolaskuosoiteData->data->organization->identifier[0], $matches)) {
                    $contact->id_code = $matches[1];
                    ScoroContact::setBusinessId($contact->contact_id, $matches[1]);
                }
            }
        }

        self::$vlo = $customerVerkkolaskuosoiteData;
        return $customerVerkkolaskuosoiteData;
    }

    /**
     * @param object $contact
     * @param array $buyer
     * @param ScoroAPI $scoroApi
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public
    static function updateAddressInScoro(object $contact, array $buyer, ScoroAPI $scoroApi): void
    {
        if (empty($contact->addresses[0]) || $contact->addresses[0]->street != $buyer['address']) {
            $scoroApi->setContact($contact->contact_id, ['addresses' => [
                (object)[
                    'city' => $buyer['city'],
                    'street' => $buyer['street'],
                    'zipcode' => $buyer['postCode']
                ]
            ]]);
        }
    }

    /**
     * @param object $contact
     * @param ScoroAPI $scoroApi
     * @throws \DragonBe\Vies\ViesException
     * @throws \DragonBe\Vies\ViesServiceException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function handleVatNo(object $contact, ScoroAPI $scoroApi): void
    {
        if (empty($contact->vatno)) {
            $vatNo = self::getVatIdFromBusinessId($contact->id_code);
            if (self::vatNumberIsValid($vatNo)) {
                $scoroApi->setContact($contact->contact_id, ['vatno' => $vatNo]);
                debug("Updated Vat Code");
                $contact->vatno = $vatNo;
            }
        }
    }

    /**
     * Returns true if Scoro does not have y-tunnus set, or it is a valid Finnish business id.
     * @param ScoroContact $scoroCompany
     * @return bool
     */
    private static function validateExistingBusinessId(ScoroContact $scoroCompany): bool
    {
        return empty($scoroCompany->id_code) || BusinessId::isValid($scoroCompany->id_code);
    }

    /**
     * @param Finvoice $finvoice
     */
    protected static function writeXmlToDebugFile(Finvoice $finvoice): void
    {
        file_put_contents(".debug/last.xml", $finvoice->getXML());
    }


}