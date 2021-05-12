<?php namespace ScoroMaventa\Maventa;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7;
use ScoroMaventa\API;

class MaventaAPI
{

    private $token;
    private API $http;

    public function __construct()
    {
        $this->http = new API(MAVENTA_BASE_URL);

        $token = $this->getToken(MAVENTA_CLIENT_ID, MAVENTA_CLIENT_SECRET, MAVENTA_SCOPE, MAVENTA_VENDOR_API_KEY);
        $this->token = $token;
        $this->http->authenticateByHeader(['Authorization' => 'Bearer ' . $this->token]);
    }

    /**
     * @param $client_id
     * @param $client_secret
     * @param $scope
     * @param $vendor_api_key
     * @return mixed
     * @throws Exception|GuzzleException
     */
    private function getToken($client_id, $client_secret, $scope, $vendor_api_key)
    {

        $response = $this->http->post('oauth2/token', ['form_params' => [
            'grant_type' => 'client_credentials',
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'scope' => $scope,
            'vendor_api_key' => $vendor_api_key]]);

        if (empty($response->access_token)) {
            throw new Exception('No access_token returned from the Maventa API');
        }

        return $response->access_token;
    }

    /**
     * @param $invoiceXml
     * @return mixed
     * @throws Exception|GuzzleException
     */
    public function sendNewInvoice($invoiceXml)
    {
        if (!$this->token) {
            throw new Exception('Unauthenticated!');
        }

        return $this->http->post('v1/invoices', [
            'multipart' => [
                [
                    'name' => 'format',
                    'contents' => 'FINVOICE30'
                ],
                [
                    'name' => 'recipient_email',
                    'contents' => PHPMAILER_TO_EMAIL
                ],
                [
                    'name' => 'file',
                    'contents' => Psr7\Utils::streamFor($invoiceXml),
                    'filename' => 'generatedFinvoice.xml'
                ],
            ],
        ]);

    }

}