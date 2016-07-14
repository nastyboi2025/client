<?php namespace Heg;

use FR3D\SwaggerAssertions\SchemaManager;
use FR3D\SwaggerAssertions\PhpUnit\AssertsTrait;
use Rhumsaa\Uuid\Uuid;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use GuzzleHttp\Subscriber\Retry\RetrySubscriber;

/**
 * BFClient - Communicate to the Business Fabric
 *
 * Author: Luke Harwood
 * Date: 16/05/16
 */
class BFClient
{
    use AssertsTrait;

    private $config, $schemaManager, $definition, $guzzleClient, $version, $log;

    function __construct(array $options = array(), StreamHandler $streamHandler = null)
    {
        if (file_exists("/etc/brand-integration-client.ini")) {
            $this->config = parse_ini_file("/etc/brand-integration-client.ini", true);
        } else {
            $this->config = parse_ini_file(__DIR__ . "/../../conf/brand-integration-client.ini", true);
        }

        $this->version = $this->config['api_gateway']['version'];

        $this->log = new Logger('bfclient');
        $this->log->pushHandler($streamHandler ? $streamHandler : new StreamHandler('php://stdout',
            $this->config['bfclient']['debug'] ? Logger::DEBUG : Logger::ERROR));

        $this->getSchema();
        $this->guzzleClient = $this->getGuzzleClient();
    }

    private function getGuzzleClient()
    {
        $retry = new RetrySubscriber([
            'filter' => RetrySubscriber::createStatusFilter([503]), # 503 = 'Service Unavailable'
            'max' => $this->config['bfclient']['max_retries'],
            'delay' => function ($number, $event) {
                return $this->config['bfclient']['delay_milliseconds'];
            }
        ]);

        $client = new \GuzzleHttp\Client(['base_url' => [$this->getApiGatewayUrl() . '/{version}', [
            'version' => $this->version]
        ]]);

        $client->getEmitter()->attach($retry);

        return $client;
    }

    private function getSchema()
    {
        $this->schemaManager = new SchemaManager($this->getApiSwaggerDefinitionUri());

        $reflectedProperty = new \ReflectionObject($this->schemaManager);
        $p = $reflectedProperty->getProperty('definition');
        $p->setAccessible(true);
        $this->definition = $p->getValue($this->schemaManager);
    }

    private function getApiGatewayUrl()
    {
        return 'http://' . $this->config['api_gateway']['hostname'] . ':' . $this->config['api_gateway']['port'];
    }

    private function getApiSwaggerDefinitionUri()
    {
        return $this->getApiGatewayUrl() . '/v' . $this->version . $this->config['api_gateway']['swagger_path'];
    }

    function getSchemaManager()
    {
        return $this->schemaManager;
    }

    /**
     * Checks if a JSON request is valid to a path defined in the Swagger document
     *
     * @param $path
     * @param $httpMethod
     * @param $requestJson
     * @return bool
     * @throws \Exception
     */
    function isRequestValid($path, $httpMethod, $requestJson)
    {
        $this->log->debug("Method: isRequestValid");

        $request = json_decode($requestJson);

        try {
            self::assertRequestBodyMatch($request, $this->schemaManager, "/v$this->version$path", $httpMethod);
            return true;

        } catch (\Exception $e) {

            if ($e instanceof \PHPUnit_Framework_ExpectationFailedException) {

                throw new \Exception($e->getMessage());
            }

            throw $e;
        }
    }

    /**
     * Sends the request through to Business Farbic returning a Guzzle Response on success
     *
     * @param $path
     * @param $httpMethod
     * @param $requestJson
     * @return \GuzzleHttp\Message\FutureResponse|\GuzzleHttp\Message\ResponseInterface|\GuzzleHttp\Ring\Future\FutureInterface|null
     * @throws \Exception
     */
    function sendRequest($path, $httpMethod, $requestJson)
    {
        $this->log->debug("Method: sendRequest");
        $uuidTracking = Uuid::uuid4();
        $this->log->debug("Tracking UUID $uuidTracking");

        $this->isRequestValid($path, $httpMethod, $requestJson);

        $request = $this->guzzleClient->createRequest($httpMethod, $this->definition->basePath . $path,
            ['debug' => $this->log->getHandlers() && $this->log->getHandlers()[0]->getLevel() == 100 ? $this->log->getHandlers()[0] : false,
                'body' => $requestJson,
                'headers' => ['Authorization' => 'Bearer ' . 'TOKEN', 'X-API-Key' =>
                    $this->config['api_gateway']['apikey'],
                    'HEG-BG' => $uuidTracking,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json']
            ]);

        $response = $this->guzzleClient->send($request);

        return $response;
    }

    /**
     * Gets the schema for a individual path and method
     *
     * @param $path
     * @param $method
     * @param $asJson
     * @param $jsonTidy
     * @return mixed
     */
    function getRequestSchema($path, $method, $asJson, $jsonTidy)
    {
        $this->log->debug("Method: getRequestSchema");

        $schema = $this->schemaManager->getRequestSchema($path, $method);

        if (!$asJson) {
            return $schema;
        }

        if ($jsonTidy) {
            return json_encode($schema, JSON_PRETTY_PRINT);
        } else {
            return json_encode($schema);
        }
    }
}