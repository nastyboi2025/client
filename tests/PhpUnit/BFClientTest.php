<?php

namespace HEG\PhpUnit;

require_once __DIR__ . '/../../vendor/autoload.php';

//require_once __DIR__ . '/../../src/start.php';

use PHPUnit_Framework_TestCase as TestCase;
use Heg\BFClient;

/**
 * Author: Luke Harwood
 * Date: 16/05/16
 */
class BFClientTest extends TestCase
{

    public function testBFClientInstance()
    {
        $client = new BFClient();

        self::assertNotNull($client);
    }

    public function testGetSchema()
    {
        $client = new BFClient();
        self::assertNotNull($client->getSchemaManager());
    }

    public function testGetRequestSchema() {
        $client = new BFClient();

        self::assertEquals('{"type":"object","required":["name","photoUrls"],"properties":{"id":{"type":"integer","format":"int64"},"category":{"type":"object","properties":{"id":{"type":"integer","format":"int64"},"name":{"type":"string"}},"xml":{"name":"Category"}},"name":{"type":"string","example":"doggie"},"photoUrls":{"type":"array","xml":{"name":"photoUrl","wrapped":true},"items":{"type":"string"}},"tags":{"type":"array","xml":{"name":"tag","wrapped":true},"items":{"type":"object","properties":{"id":{"type":"integer","format":"int64"},"name":{"type":"string"}},"xml":{"name":"Tag"}}},"status":{"type":"string","description":"pet status in the store","enum":["available","pending","sold"]}},"xml":{"name":"Pet"}}',
            $client->getRequestSchema('/pet', 'post', true, false) );
    }

    public function testIsRequestValid()
    {
        $client = new BFClient();

        $request = <<<JSON
{
  "id": 12,
  "category": {
    "id": 0,
    "name": "string"
  },
  "name": "doggie",
  "photoUrls": [
    "string"
  ],
  "tags": [
    {
      "id": 0,
      "name": "string"
    }
  ],
  "status": "available"
}
JSON;

        self::assertTrue($client->isRequestValid('/pet', 'post', $request), "Valid request appears valid");
    }

    public function testIsRequestInvalid()
    {
        $client = new BFClient();

        $request = <<<JSON
{
  "id": "12",
  "category": {
    "id": 0,
    "name": "string"
  },
  "name": "doggie",
  "photoUrls": [
    "string"
  ],
  "tags": [
    {
      "id": 0,
      "name": "string"
    }
  ],
  "status": "available"
}
JSON;

        try {
            $client->isRequestValid('/pet', 'post', $request);
            self::assertTrue(false, "Exception not thrown");
        } catch(\Exception $e) {
            self::assertTrue(true, "Exception thrown");
            self::assertEquals($e->getMessage(), "Failed asserting that {\"id\":\"12\",\"category\":{\"id\":0,\"name\":\"string\"},\"name\":\"doggie\",\"photoUrls\":[\"string\"],\"tags\":[{\"id\":0,\"name\":\"string\"}],\"status\":\"available\"} is a valid request body.\n[id] String value found, but an integer is required\n");
        }
    }

    public function testSendRequest() {

        $client = new BFClient();

        $request = <<<JSON
{
  "id": 12,
  "category": {
    "id": 0,
    "name": "string"
  },
  "name": "doggie",
  "photoUrls": [
    "string"
  ],
  "tags": [
    {
      "id": 0,
      "name": "string"
    }
  ],
  "status": "available"
}
JSON;

        $response = $client->sendRequest('/pet', 'post', $request);

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('{"id":12,"category":{"id":0,"name":"string"},"name":"doggie","photoUrls":["string"],"tags":[{"id":0,"name":"string"}],"status":"available"}', json_encode($response->json()));

    }

    public function testSendRequestInvalid() {

        $client = new BFClient();

        $request = <<<JSON
{
  "id": 12,
  "category": {
    "id": 0,
    "name": "string"
  },
  "name": 123,
  "photoUrls": [
    "string"
  ],
  "tags": [
    {
      "id": 0,
      "name": "string"
    }
  ],
  "status": "available"
}
JSON;

        try {
            $client->sendRequest('/pet', 'post', $request);
            self::assertTrue(false, "Exception not thrown");
        } catch(\Exception $e) {
            self::assertTrue(true, "Exception thrown");
            self::assertEquals($e->getMessage(), "Failed asserting that {\"id\":12,\"category\":{\"id\":0,\"name\":\"string\"},\"name\":123,\"photoUrls\":[\"string\"],\"tags\":[{\"id\":0,\"name\":\"string\"}],\"status\":\"available\"} is a valid request body.\n[name] Integer value found, but a string is required\n");
        }
    }
}