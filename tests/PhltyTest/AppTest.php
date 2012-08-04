<?php

namespace PhlytyTest;

use Phlyty\App;
use Phlyty\Exception;
use PHPUnit_Framework_TestCase as TestCase;
use Zend\Http\PhpEnvironment\Request;
use Zend\Http\PhpEnvironment\Response;

class AppTest extends TestCase
{
    public function setUp()
    {
        $this->app = new App();
    }

    public function testLazyLoadsRequest()
    {
        $request = $this->app->request();
        $this->assertInstanceOf('Zend\Http\PhpEnvironment\Request', $request);
    }

    public function testLazyLoadsResponse()
    {
        $response = $this->app->response();
        $this->assertInstanceOf('Zend\Http\PhpEnvironment\Response', $response);
    }

    public function testRequestIsInjectible()
    {
        $request = new Request();
        $this->app->setRequest($request);
        $this->assertSame($request, $this->app->request());
    }

    public function testResponseIsInjectible()
    {
        $response = new Response();
        $this->app->setResponse($response);
        $this->assertSame($response, $this->app->response());
    }

    public function testHaltShouldRaiseHaltException()
    {
        $this->setExpectedException('Phlyty\Exception\HaltException');
        $this->app->halt(403);
    }

    public function testResponseShouldContainStatusProvidedToHalt()
    {
        try {
            $this->app->halt(403);
            $this->fail('HaltException expected');
        } catch (Exception\HaltException $e) {
        }

        $this->assertEquals(403, $this->app->response()->getStatusCode());
    }

    public function testResponseShouldContainMessageProvidedToHalt()
    {
        try {
            $this->app->halt(500, 'error message');
            $this->fail('HaltException expected');
        } catch (Exception\HaltException $e) {
        }

        $this->assertContains('error message', $this->app->response()->getContent());
    }
}
