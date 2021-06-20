<?php namespace ScoroMaventa\Prh;

use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use ScoroMaventa\API;

class PrhAPI extends API
{

    const baseUrl = 'http://avoindata.prh.fi/bis/';


    /**
     * Validates if given business id format fits Finnish business id.
     * Method does not validate checksum.
     *
     * @param string $businessId
     * @return boolean
     */
    public static function isValidBusinessId(string $businessId)
    {
        return (bool)preg_match('/^[FI]{0,2}([0-9]{7})-?([0-9])/i', trim($businessId));
    }

    /**
     * Check that business id is formated correctly and checksum matches.
     *
     * @param string $businessId
     * @return boolean
     */
    public static function isValidFinnishBusinessId(string $businessId)
    {

        if (self::isValidBusinessId($businessId) == false) {
            return false;
        }

        // Remove all except numbers
        $businessId = preg_replace('/[^0-9]/', '', $businessId);

        // Some old business ids may have just 6 numbers + checksum
        $businessId = str_pad($businessId, 8, "0", STR_PAD_LEFT);

        // Calculate checksum
        $multipliers = [7, 9, 10, 5, 8, 4, 2];
        $checksum = 0;
        foreach (str_split($businessId) as $k => $v) {
            if (isset($multipliers[$k]) == false) break;

            $checksum += ((int)$v * $multipliers[$k]);
        }
        $checksum = $checksum % 11;

        if ($checksum == 1) return false;
        if ($checksum > 1) {
            $checksum = 11 - $checksum;
        }

        return (substr($businessId, -1) == $checksum);
    }

    /**
     * Lookup business information from Finnish Patent and Registration Office using Finnish business id.
     *
     * Method accepts Finnish business id in following formats:
     * FI12345678
     * 12345678
     * 1234567-8
     *
     * @param string $businessId Finnish business id
     * @param array $options Array of additional options to pass to GuzzleHttp\Client
     * @return BusinessInformation BusinessInformation object or false if not found
     * @throws Exception|GuzzleException
     */
    public static function findByBusinessId(string $businessId, array $options = [])
    {
        if (self::isValidFinnishBusinessId($businessId) == false) {
            throw new InvalidArgumentException("$businessId is not a valid Finnish business id.");
        }

        try {
            $response = API::getInstance(self::baseUrl)->get("v1/" . urlencode($businessId), $options);
        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 404) {
                throw new Exception("A business with a business id $businessId was not found from ". self::baseUrl .". Make sure business id is correct.");
            }
        }

        if (!isset($response->results[0])) {
            throw new Exception("Invalid response from Finnish Patent and Registration Office API");
        }
        return new BusinessInformation($response->results[0]);

    }

    /**
     * @param $businessName
     * @param array $options
     * @return BusinessInformation|bool
     * @throws Exception
     * @throws GuzzleException
     */
    public static function findByName($businessName, array $options = [])
    {

        $api = new API('https://avoindata.prh.fi/bis/');

        try {
            $response = $api->get("v1?totalResults=true&maxResults=999&resultsFrom=0&name=" . urlencode($businessName), $options);

            foreach ($response->results as $organisation) {
                if (mb_strtolower($businessName, 'UTF-8') == mb_strtolower($organisation->name, 'UTF-8')) {
                    return new BusinessInformation($organisation);
                }
            }
        } catch (ClientException $e) {
            if ($e->getCode() === 404) {
                throw new Exception("Unable to send e-invoice because the Finnish Business Information System does not have information about a company named " . $businessName);
            }
        }

        return false;
    }

}