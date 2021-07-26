<?php namespace ScoroMaventa\Prh;

use App\Application;
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
        if (BusinessId::isValid($businessId) == false) {
            throw new InvalidArgumentException("$businessId is not a valid Finnish business id.");
        }

        try {
            $response = API::getInstance(self::baseUrl)->get("v1/" . urlencode($businessId), $options);
        } catch (Exception $e) {
            if ($e instanceof \GuzzleHttp\Exception\ClientException && $e->getResponse()->getStatusCode() === 404) {
                self::throw404BusinessIdNotFound($businessId);
            } else {
                // There was unknown error with the Prh service. Fail silently so that sending can continue but report failure to Sentry
                if (sentryDsnIsSet()) {
                    Application::sendExceptionToSentry($e, 'businessId', $businessId);
                }
            }
        }

        if (!isset($response->results[0])) {
            throw new Exception("Invalid response from Finnish Patent and Registration Office API");
        }
        debug("PRH: found organization by business id: " . $response->results[0]->name);
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

        $api = new API(self::baseUrl);

        try {
            $response = $api->get("v1?totalResults=true&maxResults=999&resultsFrom=0&name=" . urlencode($businessName), $options);

            foreach ($response->results as $organisation) {
                if (mb_strtolower($businessName, 'UTF-8') == mb_strtolower($organisation->name, 'UTF-8')
                    || mb_strtolower($businessName, 'UTF-8') . ' oy' == mb_strtolower($organisation->name, 'UTF-8')) {
                    debug("PRH: found by name: " . $organisation);
                    return new BusinessInformation($organisation);
                }
            }
        } catch (ClientException $e) {
            if ($e->getCode() === 404) {
                self::throw404BusinessNameNotFound($businessName);
            }
        }

        return false;
    }

    /**
     * @param string $businessId
     * @throws PrhBusinessNotFoundException
     */
    private static function throw404BusinessIdNotFound(string $businessId): void
    {
        throw new PrhBusinessNotFoundException("The Finnish Business Information System does not have information about a company with a Y-tunnus of $businessId.", 404);
    }

    /**
     * @param $businessName
     * @throws Exception
     */
    private static function throw404BusinessNameNotFound($businessName): void
    {
        throw new PrhBusinessNotFoundException("The Finnish Business Information System does not have information about a company named " . $businessName . ".", 404);
    }

}