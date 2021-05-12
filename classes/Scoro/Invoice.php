<?php namespace ScoroMaventa\Scoro;

use DragonBe\Vies\Vies;
use Exception;
use ScoroMaventa\Finvoice\Finvoice;
use ScoroMaventa\Finvoice\FinvoiceSettings;
use ScoroMaventa\Maventa\MaventaAPI;
use ScoroMaventa\Prh\PrhAPI;
use ScoroMaventa\Verkkolaskuosoite\VerkkolaskuosoiteAPI;

class Invoice
{
    public static function send($scoroInvoice)
    {
        $country = $scoroInvoice->company_address->country;
        if (!empty($country) && $country != 'fin') {
            throw new Exception("Not sending e-invoice $scoroInvoice->no to $scoroInvoice->company_name because the company address country was not Finland");
        }
        debug("Country was valid");

        $scoroApi = new ScoroAPI();


        $buyerContactData = $scoroApi->getContact($scoroInvoice->company_id);

        if (empty($buyerContactData->id_code)) {
            $buyerOrganisation = PrhAPI::findByName($buyerContactData->name);
            $buyerContactData->id_code = $buyerOrganisation->businessId;
            $scoroApi->setContact($buyerContactData->contact_id, ['id_code' => $buyerContactData->id_code]);
        } else {
            VerkkolaskuosoiteApi::findOrganisation($buyerContactData->id_code);
        }

        debug("Updated Id Code");

        $vatNo = self::getVatIdFromBusinessId($buyerContactData->id_code);

        if (self::vatNumberIsValid($vatNo)) {
            $scoroApi->setContact($buyerContactData->contact_id, ['vatno' => $vatNo]);
            $buyerContactData->vatno = $vatNo;
        }


        debug("Updated Vat Code");

        $buyerIntermediator = INCLUDE_BUYER_INTERMEDIATOR ? VerkkolaskuosoiteAPI::findOrganisation($buyerContactData->id_code)->getToIntermediator() : null;

        $buyerPrhData = PrhAPI::findByBusinessId($buyerContactData->id_code);
        debug("Got buyer PRH data");
        $buyerPrhAddresses = $buyerPrhData->getAddressInfo();
        $buyerPrhPostalAddress = $buyerPrhAddresses[0];
        $sellerPrhData = PrhAPI::findByBusinessId(SELLER_BUSINESS_ID);
        debug("Got seller PRH data");
        $sellerPrhAddresses = $sellerPrhData->getAddressInfo();
        $sellerPrhPostalAddress = $sellerPrhAddresses[0];

        $sellerCompanyAccount = $scoroApi->getCompanyAccount();
        $sellerPhoneNumber = $sellerCompanyAccount->phone;

        $addresses = [];
        foreach ($buyerPrhAddresses as $buyerPrhAddress) {
            $addresses [] = (object)[
                'city' => $buyerPrhAddress->city,
                'street' => $buyerPrhAddress->street,
                'zipcode' => $buyerPrhAddress->postCode
            ];
        }
        $scoroApi->setContact($buyerContactData->contact_id, ['addresses' => $addresses]);

        debug("set Scoro contact address");


        self::setBuyerReference($scoroInvoice);
        self::moveSubheadingDataToLineComments($scoroInvoice);


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
                    'address' => $sellerPrhPostalAddress->street,
                    'postcode' => $sellerPrhPostalAddress->postCode,
                    'city' => $sellerPrhPostalAddress->city,
                    'email' => $scoroInvoice->owner_email,
                    'phone' => $sellerPhoneNumber
                ],
                // Buyer
                'to' => [
                    'identifier' => self::getOvtFromBusinessId($buyerContactData->id_code),
                    'identifier_scheme_id' => '0037',
                    'intermediator' => $buyerIntermediator,
                    'IBAN' => null,
                    'BIC' => null,
                    'name' => $buyerContactData->name,
                    'business_id' => $buyerContactData->id_code,
                    'address' => $buyerPrhPostalAddress->street,
                    'postcode' => $buyerPrhPostalAddress->postCode,
                    'city' => $buyerPrhPostalAddress->city,
                    'contact_name' => $scoroInvoice->person_name
                ],
                'invoice' => $scoroInvoice
            ]
        ));
        file_put_contents(".debug/last.xml", $finvoice->getXML());

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


}