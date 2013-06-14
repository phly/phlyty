<?php

namespace PhlytyTest;

use Phlyty\App;
use Phlyty\Exception;
use Phlyty\Route;
use Phlyty\View;
use PHPUnit_Framework_TestCase as TestCase;
use ReflectionObject;
use Zend\Http\PhpEnvironment\Request;
use Zend\Http\PhpEnvironment\Response;
use Zend\Log\Logger;
use Zend\Mvc\Router\Http as Routes;

class AppTest extends TestCase
{
    public function setUp()
    {
        $this->app = new App();
        $this->app->setResponse(new TestAsset\Response());
    }

    public function testLazyLoadsRequest()
    {
        $request = $this->app->request();
        $this->assertInstanceOf('Zend\Http\PhpEnvironment\Request', $request);
    }

    public function testLazyLoadsResponse()
    {
        $app      = new App();
        $response = $app->response();
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

    public function setupRoutes()
    {
        $this->app->get('/foo', function () {});
        $this->app->get('/bar', function () {});
        $this->app->post('/bar', function () {});
        $this->app->delete('/bar', function () {});
    }

    public function testRunningWithNoMatchingRoutesRaisesPageNotFoundException()
    {
        $this->setupRoutes();
        $r = new ReflectionObject($this->app);
        $routeMethod = $r->getMethod('route');
        $routeMethod->setAccessible(true);
        $this->setExpectedException('Phlyty\Exception\PageNotFoundException');
        $routeMethod->invoke($this->app, $this->app->request(), 'GET');
    }

    public function testRoutingSetsListOfNamedRoutes()
    {
        $foo = $this->app->get('/foo', function () {})->name('foo');
        $this->app->get('/bar', function () {});
        $barPost = $this->app->post('/bar', function () {})->name('bar-post');
        $this->app->delete('/bar', function () {});

        $r = new ReflectionObject($this->app);
        $routeMethod = $r->getMethod('route');
        $routeMethod->setAccessible(true);
        try {
            $routeMethod->invoke($this->app, $this->app->request(), 'GET');
            $this->fail('Successful routing not expected');
        } catch (\Exception $e) {
        }

        $this->assertAttributeEquals(['foo' => $foo, 'bar-post' => $barPost], 'namedRoutes', $this->app);
    }

    public function testRoutingSetsListsOfRoutesByMethod()
    {
        $foo       = $this->app->get('/foo', function () {})->name('foo');
        $bar       = $this->app->get('/bar', function () {});
        $barPost   = $this->app->post('/bar', function () {})->name('bar-post');
        $barDelete = $this->app->delete('/bar', function () {});

        $r = new ReflectionObject($this->app);
        $routeMethod = $r->getMethod('route');
        $routeMethod->setAccessible(true);
        try {
            $routeMethod->invoke($this->app, $this->app->request(), 'GET');
            $this->fail('Successful routing not expected');
        } catch (\Exception $e) {
        }

        $routesByMethod = $r->getProperty('routesByMethod');
        $routesByMethod->setAccessible(true);
        $routesByMethod = $routesByMethod->getValue($this->app);

        $this->assertTrue(isset($routesByMethod['GET']));
        $this->assertEquals([$foo, $bar], array_values($routesByMethod['GET']));
        $this->assertTrue(isset($routesByMethod['POST']));
        $this->assertEquals([$barPost], array_values($routesByMethod['POST']));
        $this->assertTrue(isset($routesByMethod['DELETE']));
        $this->assertEquals([$barDelete], array_values($routesByMethod['DELETE']));
    }

    public function testSuccessfulRoutingDispatchesController()
    {
        $foo = $this->app->get('/foo', function ($app) {
            $app->response()->setContent('Foo bar!');
        });
        $request = $this->app->request();
        $request->setMethod('GET')
                ->setUri('/foo');
        $this->app->run();
        $response = $this->app->response();
        $this->assertEquals('Foo bar!', $response->sentContent);
    }

    public function testUnsuccessfulRoutingTriggers404Event()
    {
        $test = (object) ['status' => false];
        $this->app->events()->attach('404', function ($app) use ($test) {
            $test->status = true;
        });
        $this->app->run();
        $this->assertTrue($test->status);
    }

    public function testCallingHaltTriggersHaltEvent()
    {
        $foo = $this->app->get('/foo', function ($app) {
            $app->halt(418, "Calmez vous");
        });

        $test = (object) ['status' => false];
        $this->app->events()->attach('halt', function ($app) use ($test) {
            $test->status = true;
        });

        $request = $this->app->request();
        $request->setMethod('GET')
                ->setUri('/foo');
        $this->app->run();

        $this->assertTrue($test->status);
    }

    public function testInvalidControllerTriggers501Event()
    {
        $foo = $this->app->get('/foo', 'bogus-controller');

        $test = (object) ['status' => false];
        $this->app->events()->attach('501', function ($app) use ($test) {
            $test->status = true;
        });

        $request = $this->app->request();
        $request->setMethod('GET')
                ->setUri('/foo');
        $this->app->run();

        $this->assertTrue($test->status);
    }

    public function testExceptionRaisedInControllerTriggers500Event()
    {
        $exception = new \DomainException();
        $foo = $this->app->get('/foo', function ($app) use ($exception) {
            throw $exception;
        });

        $test = (object) ['status' => false];
        $this->app->events()->attach('500', function ($event) use ($test) {
            $test->status = true;
            $test->exception = $event->getParam('exception');
        });

        $request = $this->app->request();
        $request->setMethod('GET')
                ->setUri('/foo');
        $this->app->run();

        $this->assertTrue($test->status);
        $this->assertSame($exception, $test->exception);
    }

    public function testCanPassToNextMatchingRoute()
    {
        $foo = $this->app->get('/foo', function ($app) {
            $app->response()->setContent('Foo bar!');
            $app->pass();
        });
        $bar = $this->app->get('/foo', function ($app) {
            $app->response()->setContent('FOO BAR!');
        });
        $request = $this->app->request();
        $request->setMethod('GET')
                ->setUri('/foo');
        $this->app->run();
        $response = $this->app->response();
        $this->assertEquals('FOO BAR!', $response->sentContent);
    }

    public function testUrlForHelperAssemblesUrlBasedOnNameProvided()
    {
        $foo = $this->app->get('/foo', function ($app) {
            $app->response()->setContent($app->urlFor('foo'));
        })->name('foo');
        $request = $this->app->request();
        $request->setMethod('GET')
                ->setUri('/foo');
        $this->app->run();
        $response = $this->app->response();
        $this->assertEquals('/foo', $response->sentContent);
    }

    public function testUrlForHelperAssemblesUrlBasedOnNameAndParamsProvided()
    {
        $foo = $this->app->get('/foo/:id', function ($app) {
            $app->response()->setContent($app->urlFor('foo', ['id' => 3]));
        })->name('foo');
        $request = $this->app->request();
        $request->setMethod('GET')
                ->setUri('/foo/1');
        $this->app->run();
        $response = $this->app->response();
        $this->assertEquals('/foo/3', $response->sentContent);
    }

    public function testUrlForHelperAssemblesUrlBasedOnCurrentRouteMatchWhenNoNameProvided()
    {
        $foo = $this->app->get('/foo/:id', function ($app) {
            $app->response()->setContent($app->urlFor());
        })->name('foo');
        $request = $this->app->request();
        $request->setMethod('GET')
                ->setUri('/foo/1');
        $this->app->run();
        $response = $this->app->response();
        $this->assertEquals('/foo/1', $response->sentContent);
    }

    public function testComposesLoggerInstanceByDefault()
    {
        $logger = $this->app->getLog();
        $this->assertInstanceOf('Zend\Log\Logger', $logger);
    }

    public function testCanInjectSpecificLoggerInstance()
    {
        $logger = new Logger();
        $this->app->setLog($logger);
        $this->assertSame($logger, $this->app->getLog());
    }

    public function testMustacheViewIsUsedByDefault()
    {
        $view = $this->app->view();
        $this->assertInstanceOf('Phlyty\View\MustacheView', $view);
    }

    public function testCanInjectAlternateViewInstance()
    {
        $view = new View\MustacheView();
        $this->app->setView($view);
        $this->assertSame($view, $this->app->view());
    }

    public function testRenderRendersATemplateToTheResponse()
    {
        $view = $this->app->view();
        $view->setTemplatePath(__DIR__ . '/TestAsset');
        $this->app->render('test');
        $test = file_get_contents(__DIR__ . '/TestAsset/test.mustache');
        $this->assertContains($test, $this->app->response()->getContent());
    }

    public function testViewModelReturnsMustacheViewModelByDefault()
    {
        $model = $this->app->viewModel();
        $this->assertInstanceOf('Phlyty\View\MustacheViewModel', $model);
    }

    public function testSubsequentCallsToViewModelReturnSeparateInstances()
    {
        $model1 = $this->app->viewModel();
        $this->assertInstanceOf('Phlyty\View\MustacheViewModel', $model1);
        $model2 = $this->app->viewModel();
        $this->assertInstanceOf('Phlyty\View\MustacheViewModel', $model2);
        $this->assertNotSame($model1, $model2);
    }

    public function testCanProvideViewModelPrototype()
    {
        $model = (object) [];
        $this->app->setViewModelPrototype($model);
        $test  = $this->app->viewModel();
        $this->assertInstanceOf('stdClass', $test);
        $this->assertNotInstanceOf('Phlyty\View\MustacheViewModel', $test);
        $this->assertNotSame($model, $test);
    }

    public function testRouteMatchWithBaseUrl()
    {
        $foo = $this->app->get('/foo', function ($app) {
            $app->response()->setContent('Foo bar!');
        });

        $this->app->request()->setBaseUrl('/bar/baz');

        $request = $this->app->request();
        $request->setMethod('GET')
            ->setUri('/bar/baz/foo');
        $this->app->run();
        $response = $this->app->response();
        $this->assertEquals('Foo bar!', $response->sentContent);
    }
}
