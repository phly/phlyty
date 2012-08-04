<?php

namespace PhlytyTest;

use Phlyty\App;
use Phlyty\Exception;
use Phlyty\Route;
use PHPUnit_Framework_TestCase as TestCase;
use Zend\Http\PhpEnvironment\Request;
use Zend\Http\PhpEnvironment\Response;
use Zend\Mvc\Router\Http as Routes;

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

    public function testStopShouldRaiseHaltException()
    {
        $this->setExpectedException('Phlyty\Exception\HaltException');
        $this->app->stop();
    }

    public function testResponseShouldRemainUnalteredAfterStop()
    {
        $this->app->response()->setStatusCode(200)
                              ->setContent('foo bar');
        try {
            $this->app->stop();
            $this->fail('HaltException expected');
        } catch (Exception\HaltException $e) {
        }

        $this->assertEquals(200, $this->app->response()->getStatusCode());
        $this->assertContains('foo bar', $this->app->response()->getContent());
    }

    public function testRedirectShouldRaiseHaltException()
    {
        $this->setExpectedException('Phlyty\Exception\HaltException');
        $this->app->redirect('http://github.com');
    }

    public function testRedirectShouldSet302ResponseStatusByDefault()
    {
        try {
            $this->app->redirect('http://github.com');
            $this->fail('HaltException expected');
        } catch (Exception\HaltException $e) {
        }

        $this->assertEquals(302, $this->app->response()->getStatusCode());
    }

    public function testRedirectShouldSetResponseStatusBasedOnProvidedStatusCode()
    {
        try {
            $this->app->redirect('http://github.com', 301);
            $this->fail('HaltException expected');
        } catch (Exception\HaltException $e) {
        }

        $this->assertEquals(301, $this->app->response()->getStatusCode());
    }

    public function testRedirectShouldSetLocationHeader()
    {
        try {
            $this->app->redirect('http://github.com');
            $this->fail('HaltException expected');
        } catch (Exception\HaltException $e) {
        }

        $response = $this->app->response();
        $headers  = $response->getHeaders();
        $this->assertTrue($headers->has('Location'));

        $location = $headers->get('Location');
        $uri      = $location->getUri();
        $this->assertEquals('http://github.com', $uri);
    }

    public function testMapCreatesASegmentRouteWhenProvidedWithAStringRoute()
    {
        $map   = $this->app->map('/:controller', function ($params, $app) { });
        $route = $map->route();
        $this->assertInstanceOf('Zend\Mvc\Router\Http\Segment', $route);
    }

    public function testMapCanReceiveARouteObject()
    {
        $route = Routes\Segment::factory(array(
            'route'    => '/:controller',
        ));
        $map = $this->app->map($route, function ($params, $app) { });
        $this->assertSame($route, $map->route());
    }

    public function testPassingInvalidRouteRaisesException()
    {
        $this->setExpectedException('Phlyty\Exception\InvalidRouteException');
        $this->app->map($this, function () {});
    }

    public function testMapCanReceiveACallable()
    {
        $map   = $this->app->map('/:controller', function ($params, $app) { });
        $this->assertInstanceOf('Closure', $map->controller());
    }

    public function testPassingInvalidControllerToRouteDoesNotImmediatelyRaiseException()
    {
        $map   = $this->app->map('/:controller', 'bogus-callback');
        $this->assertInstanceOf('Phlyty\Route', $map);
    }

    public function testAccessingInvalidControllerRaisesException()
    {
        $map   = $this->app->map('/:controller', 'bogus-callback');
        $this->setExpectedException('Phlyty\Exception\InvalidControllerException');
        $map->controller();
    }

    public function testPassingInvalidMethodToRouteViaMethodRaisesException()
    {
        $map   = $this->app->map('/:controller', 'bogus-callback');
        $this->setExpectedException('Phlyty\Exception\InvalidMethodException');
        $map->via('FooBar');
    }

    public function testCanSetMethodsRouteRespondsToSingly()
    {
        $map   = $this->app->map('/:controller', 'bogus-callback');
        $map->via('get');
        $this->assertTrue($map->respondsTo('get'));
        $this->assertFalse($map->respondsTo('post'));
        $map->via('post');
        $this->assertTrue($map->respondsTo('get'));
        $this->assertTrue($map->respondsTo('post'));
    }

    public function testCanSetMethodsRouteRespondsToAsArray()
    {
        $map   = $this->app->map('/:controller', 'bogus-callback');
        $map->via(['get', 'post']);
        $this->assertTrue($map->respondsTo('get'));
        $this->assertTrue($map->respondsTo('post'));
        $this->assertFalse($map->respondsTo('put'));
    }

    public function testCanSetMethodsRouteRespondsToAsMultipleArguments()
    {
        $map   = $this->app->map('/:controller', 'bogus-callback');
        $map->via('get', 'post');
        $this->assertTrue($map->respondsTo('get'));
        $this->assertTrue($map->respondsTo('post'));
        $this->assertFalse($map->respondsTo('put'));
    }

    public function testCanSpecifyAdditionalMethodTypesToRespondTo()
    {
        Route::allowMethod(__FUNCTION__);
        $map   = $this->app->map('/:controller', 'bogus-callback');
        $map->via(__FUNCTION__);
        $this->assertTrue($map->respondsTo(__FUNCTION__));
    }

    public function testCanSpecifyRouteName()
    {
        $map   = $this->app->map('/:controller', 'bogus-callback');
        $map->name('controller');
        $this->assertEquals('controller', $map->name());
    }

    public function methods()
    {
        return [
            ['delete'],
            ['get'],
            ['options'],
            ['patch'],
            ['post'],
            ['put'],
        ];
    }

    /**
     * @dataProvider methods
     */
    public function testAddingRouteUsingMethodTypeCreatesRouteThatRespondsToThatMethodType($method)
    {
        $methods = ['delete', 'get', 'options', 'patch', 'post', 'put'];
        $map = $this->app->$method('/:controller', 'bogus-callback');
        $this->assertTrue($map->respondsTo($method));

        foreach ($methods as $test) {
            if ($test === $method) {
                continue;
            }
            $this->assertFalse($map->respondsTo($test));
        }
    }
}
