<?php namespace ScoroMaventa;

use App\Application;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

class API
{

    const debugFileLocation = '.debug/lastRequest.http';
    protected Client $client;
    private string $baseUrl;
    private array $authenticationHeader;
    private array $authenticationParameters;
    private $validationFunction;

    public function __construct($baseUrl, $validationFunction = null)
    {

        $this->baseUrl = $baseUrl;
        $this->validationFunction = $validationFunction;

        $this->client = new Client([
            // Base URI is used with relative requests
            'base_uri' => $baseUrl,
            // You can set any number of default request options.
            'timeout' => 10.0,
        ]);
    }

    static function getInstance($baseUrl)
    {
        return new API($baseUrl);
    }

    /**
     * @param $endpoint
     * @param array $extraArguments
     * @return mixed
     * @throws Exception|GuzzleException
     */
    function post($endpoint, $extraArguments = [])
    {
        return $this->send($endpoint, 'POST', $extraArguments);
    }

    /**
     * @param $endpoint
     * @param $method
     * @param $options
     * @return mixed
     * @throws Exception|GuzzleException
     */
    function send($endpoint, $method, $options)
    {

        $options['debug'] = fopen(self::debugFileLocation, 'w');

        if (!empty($this->authenticationHeader)) {
            $options['headers'][key($this->authenticationHeader)] = current($this->authenticationHeader);
        }

        if (!empty($this->authenticationParameters)) {
            foreach ($this->authenticationParameters as $key => $value) {
                $options['json'][$key] = $value;
            }
        }

        $response = $this->client->request($method, $endpoint, $options);
        $statusCode = $response->getStatusCode();
        $body = (string)$response->getBody();
        if ($statusCode > 299) {
            throw new Exception("$method {$this->baseUrl}$endpoint request was not successful ($statusCode). $body");
        }
        if (self::bodyIsNotJson($response)) {
            throw new Exception("$method {$this->baseUrl}$endpoint response was not JSON ($body)");
        }
        if (!empty($this->validationFunction)) {
            $validationFunction = $this->validationFunction;
            $validationFunction(json_decode($body));
        }
        $body = json_decode($body);

        return isset($body->data) ? $body->data : $body;


    }

    /**
     * @param ResponseInterface $response
     * @return bool
     */
    private static function bodyIsNotJson(ResponseInterface $response)
    {
        return !str_contains($response->getHeaderLine('Content-Type'), 'application/json');
    }

    /**
     * @param $endpoint
     * @param array $extraArguments
     * @return mixed
     * @throws Exception|GuzzleException
     */
    function put($endpoint, $extraArguments = [])
    {
        return $this->send($endpoint, 'PUT', $extraArguments);
    }

    /**
     * @param $endpoint
     * @param array $extraArguments
     * @return mixed
     * @throws Exception
     * @throws GuzzleException
     */
    function get($endpoint, $extraArguments = [])
    {
        return $this->send($endpoint, 'GET', $extraArguments);
    }

    /**
     * @param array $authenticationHeader
     */
    function authenticateByHeader(array $authenticationHeader)
    {
        $this->authenticationHeader = $authenticationHeader;
    }

    /**
     * @param array $authenticationParameters
     */
    function authenticateByApiKey(array $authenticationParameters)
    {
        $this->authenticationParameters = $authenticationParameters;
    }

    /**
     * @return false|int
     */
    public static function truncateLastRequestDebugFile()
    {
        return file_put_contents(API::debugFileLocation, '');
    }

}