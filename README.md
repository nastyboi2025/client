HEG Brand Integration Client
============================

This repository contains everything a brand should need to get started using Business Fabric. Within this project is a reusable PHP client that can be consumed within a PHP code base by using Composer.

The client currently supports the following:

- All data that is sent to Business Fabric will be valid and match the applicable schema
- Automatic retries with a delay will occur if a 503 "Service Unavailable" is thrown
- Each request will contain a unique ID for tracking purposes in Business Fabric
- Version support can be changed with ease
- Command line client available to help aid testing

## Usage
```
use Heg\BFClient;

$client = new BFClient();
$json = "{...}";

if ($client->isRequestValid('/pet', 'POST', $json)) {
    $response = $client->sendRequest('/pet', 'POST', $json);

    echo "status code   : " . $response->getStatusCode() . "\n";
    echo "response body : " . json_encode($response->json()) . "\n";
}
```

## Installing brand-bf-client

This project has been setup to work with Composer and the easiest way to get started is adding the below to your composer.json file:

```
...
"repositories": [
    {
        "type": "git",
        "url": "ssh://git@stash.heg.com:7999/pd/brand-bf-client.git"
    }
],
"require": {"heg/brand-integration-client" : "*"}
...
```

## Configuring brand-bf-client

An initial step required is moving configuration into the file /etc/brand-integration-client.ini or 
conf/brand-integration-client.ini in this project structure. 
Depending on the 
type of operations you need, move either import.ini or readwrite.ini from the conf/ directory. You can change the version of the Business Fabric API from here along with setting the client in debug mode.

If your system makes use of Monolog for logging, you could pass a StreamHandler instance to the client, so that all output goes your own logs rather than to STDOUT:

```
$streamHandler = new StreamHandler('/var/log/bfclient.log', Logger::DEBUG) # Change log level here too
$client = new BFClient(array(), $streamHandler);
```

## Command line client

As an addition, a command line client has been provided to help integration. To make use of it and see its usage, just run it:

```
bin/bfclient
```

An example use case though, but be to send a HTTP POST command, with a JSON request stored in a file:

```
bin/bfclient -t -sr '/pet' post < post_pets.txt
```