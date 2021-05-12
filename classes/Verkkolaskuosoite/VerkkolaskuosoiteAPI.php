<?php namespace ScoroMaventa\Verkkolaskuosoite;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use ScoroMaventa\API;

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
            throw new Exception("No companies with name or Business-ID matching: " . $organisationNameOrBusinessId. " in Verkkolaskuosoite database");
        }

        foreach ($response as $organisation) {
            if (strtolower($organisationNameOrBusinessId) == strtolower($organisation->name) ||
                $organisationNameOrBusinessId == $verkkolaskuosoite->getOrganisationBusinessID($organisation)) {
                return $verkkolaskuosoite->getOrganisation($organisation->id);
            }
        }

        throw new Exception("No companies with exact name or Business-ID matching: " . $organisationNameOrBusinessId. " in Verkkolaskuosoite database");
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

}