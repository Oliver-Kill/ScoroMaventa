<?php namespace ScoroMaventa\Verkkolaskuosoite;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use ScoroMaventa\API;
use ScoroMaventa\Prh\PrhBusinessNotFoundException;

class VerkkolaskuosoiteAPI extends API
{

    /**
     * @param $organisationNameOrBusinessId
     * @return VerkkolaskuosoiteOrganisation
     * @throws Exception|GuzzleException
     */
    static function findOrganisation($organisationNameOrBusinessId)
    {

        $verkkolaskuosoite = new VerkkolaskuosoiteAPI('https://verkkolaskuosoite.fi/');

        $response = $verkkolaskuosoite->get('server/Public/organizations?countryCode=FI&searchText=' . urlencode($organisationNameOrBusinessId));

        if (count($response) <= 0) {
            self::throw404BusinessIdNotFound($organisationNameOrBusinessId);
        }

        foreach ($response as $organisation) {

            if (self::caseInsensitiveMatch(
                self::removePunctuationAndSpaces($organisationNameOrBusinessId),
                self::removePunctuationAndSpaces($organisation->name))) {

                debug("VLO: found organization by name: $organisation->name");
                return $verkkolaskuosoite->getOrganisation($organisation->id);
            }

            if ( $organisationNameOrBusinessId == $verkkolaskuosoite->getOrganisationBusinessID($organisation)) {

                debug("VLO: found organization by business ID: $organisation->name");
                return $verkkolaskuosoite->getOrganisation($organisation->id);
            }
        }

        self::throw404BusinessNameNotFound($organisationNameOrBusinessId);
    }

    /**
     * @param $organisationId
     * @return \ScoroMaventa\Verkkolaskuosoite\VerkkolaskuosoiteOrganisation
     * @throws Exception|GuzzleException
     */
    public function getOrganisation($organisationId)
    {

        $response = $this->get('server/Public/organizations/' . $organisationId);

        return new VerkkolaskuosoiteOrganisation($response);
    }

    /**
     * @param $organisation
     * @return false|string
     */
    public function getOrganisationBusinessID($organisation)
    {
        return substr($organisation->identifier[0], 11);
    }

    /**
     * @param string $businessId
     * @throws PrhBusinessNotFoundException
     */
    private static function throw404BusinessIdNotFound(string $businessId): void
    {
        throw new VerkkolaskuosoiteBusinessNotFoundException("Verkkolaskuosoite.fi does not have information about a company with a Y-tunnus of $businessId.", 404);
    }

    /**
     * @param $businessName
     * @throws Exception
     */
    private static function throw404BusinessNameNotFound($businessName): void
    {
        throw new VerkkolaskuosoiteBusinessNotFoundException("Verkkolaskuosoite.fi does not have information about a company named " . $businessName . ".", 404);
    }

    /**
     * Returns string without punctuation and white space.
     * @param string $string
     * @return string
     */
    protected static function removePunctuationAndSpaces(string $string): string
    {
        return preg_replace('/[^[:alnum:]]/u', '', $string);
    }

    /**
     * Returns true if strings match regardless of their case
     * @param string $string1
     * @param string $string2
     * @return bool
     */
    private static function caseInsensitiveMatch(string $string1, string $string2): bool
    {
        return mb_strtolower($string1) == mb_strtolower($string2);
    }

}