<?php namespace ScoroMaventa\Scoro;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use ScoroMaventa\API;
use stdClass;

class ScoroAPI
{
    private API $http;

    public function __construct()
    {
        $this->http = new API(SCORO_BASE_URL, function ($response) {
            if (empty($response->statusCode) || $response->statusCode > 299) {
                throw new Exception("Invalid response from ScoroAPI " . json_encode($response, JSON_PRETTY_PRINT));
            }
        });
        $this->http->authenticateByApiKey(["apiKey" => SCORO_API_KEY, "lang" => SCORO_LANG, "company_account_id" => SCORO_COMPANY_ACCOUNT_ID]);
    }

    /**
     * @param array|null $filter
     * @return array
     * @throws GuzzleException
     */
    public function getInvoiceList($filter = null): array
    {
        $data = [
            "request" => new stdClass(),
            "detailed_response" => true,
            "per_page" => 25, // Scoro max is 25 when detailed_response is true
            "page" => 1
        ];

        if (!empty($filter)) {
            $data['filter'] = $filter;
        }

        $invoices = [];

        while ($invoiceBatch = $this->http->post('invoices/list', ["json" => $data])) {
            $data['page']++;
            $invoices = array_merge($invoices, $invoiceBatch);
        }

        return $invoices;
    }

//    /**
//     * @param $userId
//     * @return mixed
//     * @throws Exception
//     */
//    public function getUserAuth($userId)
//    {
//
//        $response = $this->http->post('userAuth/modify/' . $userId, [
//            "json" => [
//                "request" => new stdClass(),
//                "username" => SCORO_USERNAME,
//                "password" => SCORO_PASSWORD,
//                "device_type" => "android",
//                "device_name" => "My phone",
//                "device_id" => 'fea9c344d0af14e8b51e8889c8a208ea'
//            ]
//        ]);
//
//        if (empty($response->data)) {
//            throw new Exception("Invalid response");
//        }
//
//        return $response->data;
//    }

    /**
     * @return object
     * @throws Exception|GuzzleException
     */
    public function getCompanyAccount()
    {

        $response = $this->http->post('companyAccount/list');

        return $response[0];
    }


    /**
     * @param $invoiceID
     * @return object
     * @throws Exception|GuzzleException
     */
    public function getInvoice($invoiceID)
    {
        $response = $this->http->post('invoices/view/' . $invoiceID, [
            "json" => [
                "request" => new stdClass()
            ]
        ]);

        return new ScoroInvoice($response);
    }

    /**
     * @param $productId
     * @return object
     * @throws Exception|GuzzleException
     */
    public function getProduct($productId)
    {

        $response = $this->http->post('products/view/' . $productId, [
            "json" => [
                "request" => new stdClass()
            ]
        ]);

        return $response->data;
    }

    /**
     * @param $orderId
     * @return object
     * @throws Exception|GuzzleException
     */
    public function getOrder($orderId)
    {

        $response = $this->http->post('orders/view/' . $orderId, [
            "json" => [
                "request" => new stdClass()
            ]
        ]);

        return empty($response->data) ? $response : $response->data;
    }

    /**
     * @param $contactId
     * @return ScoroContact
     * @throws GuzzleException
     */
    public function getContact($contactId): object
    {
        if (empty($contactId)) {
            throw new Exception("Invalid contact id");
        }
        return new ScoroContact($this->http->post('contacts/view/' . $contactId, [
            "json" => [
                "request" => new stdClass()
            ]
        ]));
    }

    /**
     * @param $contactId
     * @param $data
     * @return object
     * @throws Exception|GuzzleException
     */
    public function setContact($contactId, $data)
    {
        return $this->http->post('contacts/modify/' . $contactId, [
            "json" => [
                "request" => $data
            ]
        ]);
    }

    /**
     * @return object
     * @throws Exception|GuzzleException
     */
    public function getWebhooks()
    {

        return $this->http->post('webhooks', [
            "json" => [
                "request" => new stdClass()
            ]
        ]);
    }

    /**
     * @return object
     * @throws Exception|GuzzleException
     */
    public function getFinanceAccounts()
    {

        return $this->http->post('financeAccounts/list', [
            "json" => [
                "request" => new stdClass()
            ]
        ]);
    }

    /**
     * @return object
     * @throws Exception|GuzzleException
     */
    function registerNewWebhook()
    {
        $response = $this->http->post('webhooks/subscribe', [
            'json' => [
                "user_token" => SCORO_USER_TOKEN,
                'request' => [
                    'module' => 'invoices',
                    'action' => 'any',
                    'actors' => ['any'],
                    'owners' => ['any'],
                    'url' => SCORO_WEBHOOK_TARGET_URL,
                ]
            ]
        ]);

        var_dump($response);

        return $response;
    }
}