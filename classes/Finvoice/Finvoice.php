<?php namespace ScoroMaventa\Finvoice;

use DOMDocument;
use SimpleXMLElement;

class Finvoice
{

    private string $id;
    private FinvoiceSettings $settings;
    private SimpleXMLElement $xml;
    private string $timestamp;
    private array $namespaces = [
        'xsi' => 'http://www.w3.org/2001/XMLSchema-instance'
    ];

    public function __construct(FinvoiceSettings $settings)
    {
        $this->id = md5(rand() * time());
        $this->settings = $settings;
        $this->xml = new SimpleXMLElement('<root/>');
        $this->timestamp = date('c');

        if (!empty($this->settings->invoice)) {
            $this->xml = $this->append($this->xml, $this->getFinvoice());
            debug("Got XML from getFinvoice()");
        }
    }

    /**
     * @param $element
     * @param $field
     * @param $value
     */
    public static function addChild(SimpleXMLElement $element, $field, $value)
    {
        if (!empty($value)) {
            return $element->addChild($field, $value);
        }
        return false;
    }

    /**
     * @return string|string[]|null
     */
    public function getXML()
    {
        $xml = $this->xml;
        $dom = new DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());
        $xml = $dom->saveXML();
        $xml = str_replace(['<Envelope', '</Envelope', '<?xml version="1.0"?>'], ['<SOAP-ENV:Envelope', '</SOAP-ENV:Envelope', ''], $xml);
        $xml = preg_replace('~</?root>~', '', $xml);
        $xml = preg_replace('~^ {2}~m', '', $xml);
        $xml = preg_replace("~\n+~", "\n", $xml);
        $xml = trim($xml);
        $xml = preg_replace('~<Finvoice~', "<?xml version=\"1.0\" encoding=\"ISO-8859-15\"?>\n<?xml-stylesheet href=\"/lib/Finvoice/Finvoice.xsl\" type=\"text/xsl\"?>\n<Finvoice", $xml, 1);
        return $xml;
    }

    /**
     * @param $a
     * @param $b
     * @return mixed
     */
    private function append($a, $b)
    {
        $dom = dom_import_simplexml($a);
        $child = dom_import_simplexml($b);
        $child = $dom->ownerDocument->importNode($child, true);
        $dom->appendChild($child);
        return $a;
    }

    /**
     * @return SimpleXMLElement
     */
    public function getFinvoice()
    {
        $invoice = $this->settings->invoice;

        $finvoice = new SimpleXMLElement('<Finvoice/>');
        $finvoice->addAttribute('Version', '3.0');
        $finvoice->addAttribute('xsi:noNamespaceSchemaLocation', 'https://file.finanssiala.fi/finvoice/Finvoice3.0.xsd', $this->namespaces['xsi']);

        $messageTransmissionDetails = $finvoice->addChild('MessageTransmissionDetails');

        $messageSenderDetails = $messageTransmissionDetails->addChild('MessageSenderDetails');
        $messageSenderDetails->addChild('FromIdentifier', $this->settings->from->identifier)->addAttribute('SchemeID', $this->settings->from->identifier_scheme_id);
        $messageSenderDetails->addChild('FromIntermediator', $this->settings->from->intermediator);

        $messageReceiverDetails = $messageTransmissionDetails->addChild('MessageReceiverDetails');
        $messageReceiverDetails->addChild('ToIdentifier', $this->settings->to->identifier)->addAttribute('SchemeID', $this->settings->to->identifier_scheme_id);
        if (!empty($this->settings->to->intermediator)) {
            $messageReceiverDetails->addChild('ToIntermediator', $this->settings->to->intermediator);
        }

        $messageDetails = $messageTransmissionDetails->addChild('MessageDetails');
        $messageDetails->addChild('MessageIdentifier', $this->id);
        $messageDetails->addChild('MessageTimeStamp', $this->timestamp);

        $sellerPartyDetails = $finvoice->addChild('SellerPartyDetails');
        $sellerPartyDetails->addChild('SellerPartyIdentifier', $this->settings->from->business_id);
        $sellerPartyDetails->addChild('SellerOrganisationName', $this->settings->from->name);
        $sellerPartyDetails->addChild('SellerOrganisationTaxCode', $this->settings->from->tax_code);
        $sellerPostalAddressDetails = $sellerPartyDetails->addChild('SellerPostalAddressDetails');

        $sellerPostalAddressDetails->addChild('SellerStreetName', $this->settings->from->address);
        $sellerPostalAddressDetails->addChild('SellerTownName', $this->settings->from->city);
        $sellerPostalAddressDetails->addChild('SellerPostCodeIdentifier', $this->settings->from->postcode);
        $sellerPostalAddressDetails->addChild('CountryCode', 'FI');
        $sellerPostalAddressDetails->addChild('CountryName', 'Suomi');

        #$finvoice->addChild('SellerOrganisationUnitNumber', '0037' . str_replace('-', '', $this->settings->from->business_id));
        #$finvoice->addChild('SellerContactPersonName', isset($this->settings->from->contact) ? $this->settings->from->contact : null);

        $sellerCommunicationDetails = $finvoice->addChild('SellerCommunicationDetails');
        $sellerCommunicationDetails->addChild('SellerPhoneNumberIdentifier', isset($this->settings->from->phone) ? $this->settings->from->phone : null);
        $sellerCommunicationDetails->addChild('SellerEmailaddressIdentifier', isset($this->settings->from->email) ? $this->settings->from->email : null);

        $sellerInformationDetails = $finvoice->addChild('SellerInformationDetails');
        $sellerAccountDetails = $sellerInformationDetails->addChild('SellerAccountDetails');
        $sellerAccountDetails->addChild('SellerAccountID', $this->settings->from->IBAN)->addAttribute('IdentificationSchemeName', 'IBAN');
        $sellerAccountDetails->addChild('SellerBic', $this->settings->from->BIC)->addAttribute('IdentificationSchemeName', 'BIC');

        $buyerPartyDetails = $finvoice->addChild('BuyerPartyDetails');
        $buyerPartyDetails->addChild('BuyerPartyIdentifier', $this->settings->to->business_id);
        $buyerPartyDetails->addChild('BuyerOrganisationName', $this->settings->to->name);
        $buyerPartyDetails->addChild('BuyerOrganisationTaxCode', 'FI' . str_replace('-', '', $this->settings->to->business_id));

        $buyerPostalAddressDetails = $buyerPartyDetails->addChild('BuyerPostalAddressDetails');
        $buyerPostalAddressDetails->addChild('BuyerStreetName', $this->settings->to->address);
        $buyerPostalAddressDetails->addChild('BuyerTownName', $this->settings->to->city);
        $buyerPostalAddressDetails->addChild('BuyerPostCodeIdentifier', $this->settings->to->postcode);
        $buyerPostalAddressDetails->addChild('CountryCode', 'FI');
        $buyerPostalAddressDetails->addChild('CountryName', 'Suomi');

        if (!empty($this->settings->delivery)) {
            $deliveryPartyDetails = $finvoice->addChild('DeliveryPartyDetails');
            $deliveryPartyDetails->addChild('DeliveryPartyIdentifier', isset($this->settings->delivery->business_id) ? $this->settings->delivery->business_id : null);
            $deliveryPartyDetails->addChild('DeliveryOrganisationName', $this->settings->delivery->name);
            $deliveryPartyDetails->addChild('DeliveryOrganisationTaxCode', isset($this->settings->delivery->business_id) ? 'FI' . str_replace('-', '', $this->settings->delivery->business_id) : null);

            $deliveryPostalAddressDetails = $deliveryPartyDetails->addChild('DeliveryPostalAddressDetails');
            $deliveryPostalAddressDetails->addChild('DeliveryStreetName', $this->settings->delivery->address);
            $deliveryPostalAddressDetails->addChild('DeliveryTownName', $this->settings->delivery->city);
            $deliveryPostalAddressDetails->addChild('DeliveryPostCodeIdentifier', $this->settings->delivery->postcode);
            $deliveryPostalAddressDetails->addChild('CountryCode', 'FI');
            $deliveryPostalAddressDetails->addChild('CountryName', 'Suomi');
        }

        #$finvoice->addChild('BuyerContactPersonName', isset($this->settings->to->contact_name) ? $this->settings->to->contact_name : null);

        $invoiceDetails = $finvoice->addChild('InvoiceDetails');
        $invoiceDetails->addChild('InvoiceTypeCode', 'INV01')->addAttribute('CodeListAgencyIdentifier', 'SPY');
        $invoiceDetails->addChild('InvoiceTypeText', 'INVOICE');
        $invoiceDetails->addChild('OriginCode', 'Original');
        $invoiceDetails->addChild('InvoiceNumber', $invoice->no);
        $invoiceDetails->addChild('InvoiceDate', date('Ymd', strtotime($invoice->date)))->addAttribute('Format', 'CCYYMMDD');
        $invoiceDetails->addChild('SellerReferenceIdentifier', isset($invoice->reference_no) ? $invoice->reference_no : null);
        $invoiceDetails->addChild('BuyerReferenceIdentifier', $invoice->buyer_reference_no ?? null);

        if (isset($invoice->order_no)) {
            $invoiceDetails->addChild('OrderIdentifier', $invoice->order_no);
        }

        $invoiceDetails->addChild('InvoiceTotalVatExcludedAmount', $this->format($invoice->sum))->addAttribute('AmountCurrencyIdentifier', $invoice->currency);
        $invoiceDetails->addChild('InvoiceTotalVatAmount', $this->format($invoice->vat_sum))->addAttribute('AmountCurrencyIdentifier', $invoice->currency);
        $invoiceDetails->addChild('InvoiceTotalVatIncludedAmount', $this->format($invoice->vat_sum + $invoice->sum))->addAttribute('AmountCurrencyIdentifier', $invoice->currency);

        $invoiceDetails->addChild('InvoiceFreeText', $invoice->project_name);

        $paymentTermsDetails = $invoiceDetails->addChild('PaymentTermsDetails');
        $paymentTermsDetails->addChild('InvoiceDueDate', date('Ymd', strtotime($invoice->deadline)))->addAttribute('Format', 'CCYYMMDD');
        $paymentOverDueFineDetails = $paymentTermsDetails->addChild('PaymentOverDueFineDetails');
        $paymentOverDueFineDetails->addChild('PaymentOverDueFinePercent', $this->format($invoice->fine, 3));

        $sectionComment = '';

        foreach ($invoice->lines as $lineNumber => $line) {

            $invoiceRow = $finvoice->addChild('InvoiceRow');

            $invoiceRow->addChild('ArticleName', $line->product_name);
            if (isset($line->ordered)) {
                $invoiceRow->addChild('OrderedQuantity', $this->format($line->ordered))->addAttribute('QuantityUnitCode', $line->unit);
            }
            $invoiceRow->addChild('InvoicedQuantity', $this->format($line->amount))->addAttribute('QuantityUnitCode', $line->unit);
            $unitPriceAmount = $invoiceRow->addChild('UnitPriceAmount', $this->format($line->price));
            $unitPriceAmount->addAttribute('AmountCurrencyIdentifier', $invoice->currency);
            $unitPriceAmount->addAttribute('UnitPriceUnitCode', 'e/' . $line->unit);
            $invoiceRow->addChild('UnitPriceVatIncludedAmount', $this->format($line->price * (100 + $invoice->vat) / 100))->addAttribute('AmountCurrencyIdentifier', $invoice->currency);

            //$invoiceRow->addChild('RowDiscountAmount', $line->sum * $invoice->discount / 100)->addAttribute('AmountCurrencyIdentifier', $invoice->currency);
            self::addChild($invoiceRow, 'RowFreeText', $sectionComment . "\r\n" . $line->comment);
            self::addChild($invoiceRow, 'RowDiscountAmount', $this->format($line->sum * $invoice->discount / 100))->addAttribute('AmountCurrencyIdentifier', $invoice->currency);

            $invoiceRow->addChild('RowVatRatePercent', $this->format($invoice->vat));
            $invoiceRow->addChild('RowVatAmount', $this->format($line->amount * round($line->price * ($invoice->vat / 100), 2)))->addAttribute('AmountCurrencyIdentifier', $invoice->currency);
            $invoiceRow->addChild('RowVatExcludedAmount', $this->format($line->amount * $line->price))->addAttribute('AmountCurrencyIdentifier', $invoice->currency);
            $invoiceRow->addChild('RowAmount', $this->format($line->amount * round($line->price * (100 + $invoice->vat) / 100, 2)))->addAttribute('AmountCurrencyIdentifier', $invoice->currency);
        }

        #$specificationDetails = $finvoice->addChild('SpecificationDetails');
        #$specificationDetails->addChild('BuyerReferenceIdentifier')

        $epiDetails = $finvoice->addChild('EpiDetails');
        $epiIdentificationDetails = $epiDetails->addChild('EpiIdentificationDetails');
        $epiIdentificationDetails->addChild('EpiDate', date('Ymd', strtotime($invoice->date)))->addAttribute('Format', 'CCYYMMDD');
        $epiIdentificationDetails->addChild('EpiReference', isset($invoice->epi_reference) ? $invoice->epi_reference : null);

        $epiPartyDetails = $epiDetails->addChild('EpiPartyDetails');
        $epiBfiPartyDetails = $epiPartyDetails->addChild('EpiBfiPartyDetails');
        $epiBfiPartyDetails->addChild('EpiBfiIdentifier', $this->settings->from->BIC)->addAttribute('IdentificationSchemeName', 'BIC');

        $epiBeneficiaryPartyDetails = $epiPartyDetails->addChild('EpiBeneficiaryPartyDetails');
        $epiBeneficiaryPartyDetails->addChild('EpiNameAddressDetails', $this->settings->from->name);
        $epiBeneficiaryPartyDetails->addChild('EpiAccountID', $this->settings->from->IBAN)->addAttribute('IdentificationSchemeName', 'IBAN');

        $epiPaymentInstructionDetails = $epiDetails->addChild('EpiPaymentInstructionDetails');
        #$epiPaymentInstructionDetails->addChild('EpiRemittanceInfoIdentifier', null)->addAttribute('IdentificationSchemeName', $invoice->epi_remittance_info_identifier_identification_scheme_name);
        $epiPaymentInstructionDetails->addChild('EpiInstructedAmount', $this->format($invoice->vat_sum + $invoice->sum))->addAttribute('AmountCurrencyIdentifier', $invoice->currency);
        $epiPaymentInstructionDetails->addChild('EpiCharge')->addAttribute('ChargeOption', 'SHA');
        $epiPaymentInstructionDetails->addChild('EpiDateOptionDate', date('Ymd', strtotime($invoice->deadline)))->addAttribute('Format', 'CCYYMMDD');

        return $finvoice;
    }


    /**
     * @param $number
     * @param int $decimalPlaces
     * @return string
     */
    private function format($number, $decimalPlaces = 2): string
    {
        return number_format($number, $decimalPlaces, ',', '');
    }

}
