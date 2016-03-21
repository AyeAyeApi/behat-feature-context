<?php

namespace AyeAye\Behat;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
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
        $this->client = new Client([
            'base_uri' => $baseUrl
        ]);
    }

    /**
     * @Given I create a :method request
     * @Given I create a request
     * @param $method
     */
    public function iCreateARequest($method = 'GET')
    {
        $this->response = null;
        $this->request = new Request($method, '');
    }

    /**
     * Get the request if one is available
     * @throws \RuntimeException
     * @return Request
     */
    protected function getRequest()
    {
        if(!$this->request) {
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
        if(!$this->response) {
            throw new \RuntimeException("No response has been received, did you send a request");
        }
        return $this->response;
    }

    /**
     * @When I set header :header to :value
     * @param $header
     * @param $value
     */
    public function iSetHeaderTo($header, $value)
    {
        $this->getRequest()->withHeader($header, $value);
    }

    /**
     * @When I send the request to :location
     * @When I send the request
     * @param $location
     */
    public function iSendTheRequestTo($location = '')
    {
        $this->getRequest()->withUri(
            new Uri($location)
        );
        $this->response = $this->client->send($this->getRequest());
    }

    /**
     * @Then I expect the status code to be :code
     * @param $code
     */
    public function iExpectTheStatusCodeToBe($code)
    {
        if($this->getResponse()->getStatusCode() != $code) {
            throw new FailedStepException(
                "Expected status code '{$code}', actually got '{$this->getResponse()->getStatusCode()}'"
            );
        }
    }

    /**
     * @Then I expect the body to contain :text
     * @param $text
     */
    public function iExpectTheBodyToContain($text)
    {
        $contents = $this->getResponse()->getBody()->getContents();
        if(strpos($contents , trim($text)) === false) {
            throw new FailedStepException(
                "Expected body to contain '{$text}', but it did not:\n$contents"
            );
        }
    }
}
