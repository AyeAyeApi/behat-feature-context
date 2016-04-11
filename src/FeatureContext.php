<?php
/**
 * FeatureContext.php
 * @author    Daniel Mason <daniel@ayeayeapi.com>
 * @copyright (c) 2016 Daniel Mason <daniel@ayeayeapi.com>
 * @license   MIT
 * @see       https://github.com/AyeAyeApi/behat-feature-context
 */


namespace AyeAye\Behat;

use AyeAye\Formatter\Reader\Json;
use AyeAye\Formatter\Reader\Xml;
use AyeAye\Formatter\ReaderFactory;
use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Psr7\Uri;

/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context, SnippetAcceptingContext
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var array
     */
    protected $responseData;

    /**
     * @var SimpleAyeAyeServer
     */
    protected $server;

    /**
     * @var ReaderFactory
     */
    protected $readerFactory;

    protected $parameters = [];

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     *
     * @param string $baseUrl
     */
    public function __construct($baseUrl = '')
    {
        $this->readerFactory = new ReaderFactory([
            new Json(),
            new Xml()
        ]);
        $this->client = new Client([
            'base_uri' => $baseUrl
        ]);
    }

    /**
     * Parse a string for replacements
     * Anything wrapped in [square.brackets] will be replaced using a lookup in the response object,
     * first for 'square', then for 'brackets';
     * @param $string
     */
    protected function parseString($string)
    {
        // Identify references to the response object
        $string = preg_replace_callback(
            '/\[[^\]]+\]/',
            function ($matches) {
                $match = reset($matches); // Get the first result
                $match = trim($match, '[] \t\r\n');
                $result = $this->getDataFromResponse($match);
                if (!$result) {
                    throw new FailedStepException('Key did not exist in data');
                }
                if (!is_string($result)) {
                    throw new FailedStepException('Key found in data, but was not a string');
                }
                return $result;
            },
            $string
        );

        return $string;
    }

    /**
     * Takes a multi dimensional array and looks up a particular key
     * @param string $key A dot separated chain for multidimensional array navigation
     * @param array|null $data The data to be searched
     * @return mixed
     */
    protected function getDataFromResponse($key, array $data = null)
    {
        if ($data === null) {
            $data = $this->getResponseData();
        }
        $keyParts = explode('.', $key);

        if (!is_array($data)) {
            if (is_string($data)) {
                throw new \RuntimeException('Response body was not or could not be unserialised: '.$data);
            }
            throw new \RuntimeException('Response body was not recognised');
        }

        if ($keyParts) {
            $nextKeyPart = array_shift($keyParts);
            if (array_key_exists($nextKeyPart, $data)) {
                $nextData = $data[$nextKeyPart];
                if (!$keyParts) {
                    return $nextData;
                }
                if (is_array($nextData) || is_object($nextData)) {
                    return $this->getDataFromResponse(implode('.', $keyParts), (array)$nextData);
                }
            }
        }
        throw new \OutOfRangeException('Key did not exist in data');
    }

    /**
     * @Given the server at :docRoot is started
     * @param $docRoot
     */
    public function startServer($docRoot)
    {
        if (!$this->server) {
            $this->server = new SimpleAyeAyeServer(realpath($docRoot));
        }
    }

    /**
     * For some reason the server isn't cleaned up properly before Scenario Outlines.
     * @AfterScenario
     */
    public function stopServer()
    {
        $this->server = null;
    }

    /**
     * @Given I create a :method request
     * @Given I create a request
     * @param $method
     */
    public function iCreateARequest($method = 'GET')
    {
        $this->parameters = [];
        $this->response = null;
        $this->responseData = null;
        $this->request = new Request($method, '');
    }

    /**
     * @When I set parameter :parameter to :value
     * @param $parameter
     * @param $value
     */
    public function iSetParameterTo($parameter, $value)
    {
        $this->parameters[$parameter] = $value;
    }

    /**
     * @param $string
     * @return Stream
     */
    protected function stringToStream($string)
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $string);
        rewind($stream);
        return new Stream($stream);
    }

    /**
     * @When I set the body to:
     * @param $string
     */
    public function iSetTheBodyTo($string)
    {
        $string = trim($string);
        $stream = $this->stringToStream($string);
        $this->request = $this->getRequest()->withBody($stream);
    }

    /**
     * Get the request if one is available
     * @throws \RuntimeException
     * @return Request
     */
    protected function getRequest()
    {
        if (!$this->request) {
            throw new \RuntimeException("No request has been created");
        }
        return $this->request;
    }

    /**
     * Get the response if one is available
     * @throws \RuntimeException
     * @return Response
     */
    protected function getResponse()
    {
        if (!$this->response) {
            throw new \RuntimeException("No response has been received, did you send a request");
        }
        return $this->response;
    }

    /**
     * Get the response data as an array if it's available
     * @throws \RuntimeException
     * @return Response
     */
    protected function getResponseData()
    {
        if (!$this->responseData === null) {
            throw new \RuntimeException("No response has been received, did you send a request");
        }
        return $this->responseData;
    }

    /**
     * @When I set header :header to :value
     * @param $header
     * @param $value
     */
    public function iSetHeaderTo($header, $value)
    {
        $this->request = $this->getRequest()->withHeader($header, $value);
    }

    /**
     * @When I send the request to :location
     * @When I send the request
     * @param $location
     */
    public function iSendTheRequestTo($location = '')
    {
        // Add parameters
        $location .= (strpos($location, '?') === false) ? '?' : '&';
        $location .= http_build_query($this->parameters);

        $this->request = $this->getRequest()->withUri(
            new Uri($location)
        );
        try {
            $this->response = $this->client->send($this->getRequest());
        } catch (ClientException $e) {
            $this->response = $e->getResponse();
        }

        // Try to format it into an object, if possible
        $body = $this->getResponse()->getBody()->getContents();
        $this->responseData = $this->readerFactory->read($body);
        if (!$this->responseData) {
            $this->responseData = $body;
        }
    }

    /**
     * @When I set the request body to:
     * @param string $text
     */
    public function iSetRequestBodyTo($text = '')
    {
        $stream = \GuzzleHttp\Psr7\stream_for($text);
        $this->request = $this->getRequest()->withBody($stream);
    }

    /**
     * @Then I expect the status code to be :code
     * @param $code
     */
    public function iExpectTheStatusCodeToBe($code)
    {
        if ($this->getResponse()->getStatusCode() != $code) {
            throw new FailedStepException(
                "Expected status code '{$code}', actually got '{$this->getResponse()->getStatusCode()}'" . PHP_EOL .
                $this->getResponse()->getBody()
            );
        }
    }

    /**
     * @Then I expect the header :header to be :value
     * @param $header
     * @param $value
     */
    public function iExpectTheHeader($header, $value)
    {
        $actualValues = (array)$this->getResponse()->getHeader($header);
        foreach ($actualValues as $actualValue) {
            if ($actualValue == $value) {
                return;
            }
        }
        throw new FailedStepException(
            "Expected {$header} to be {$value}, but it was: " . implode(", ", $actualValues) . PHP_EOL .
            $this->getResponse()->getBody()
        );
    }

    /**
     * @Then I expect the body to contain :text
     * @Then I expect the body to contain:
     * @param $text
     */
    public function iExpectTheBodyToContain($text)
    {
        if (strpos($this->getResponse()->getBody(), trim($text)) === false) {
            throw new FailedStepException(
                "Expected body to contain '{$text}', but it did not:" . PHP_EOL .
                $this->getResponse()->getBody()
            );
        }
    }

    /**
     * Compare two strings.
     * Substitutions may be made.
     * @Then I expect :string1 to be :string2
     * @param $string1
     * @param $string2
     */
    public function iExpectStringToBeString($string1, $string2)
    {
        $parsedString1 = $this->parseString($string1);
        $parsedString2 = $this->parseString($string2);

        if ($parsedString1 != $parsedString2) {
            throw new FailedStepException(
                "Expected '{$string1}' to be '{$string2}', " .
                "however '{$parsedString1}' was not '{$parsedString2}'" . PHP_EOL .
                $this->getResponse()->getBody()
            );
        }
    }
}
