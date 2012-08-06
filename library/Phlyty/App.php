<?php
/**
 * @link      http://github.com/weierophinney/Phlty for the canonical sournce
 * @copyright Copyright (c) 2012 Matthew Weier O'Phinney
 * @license   BSD 3-Clause
 * @package   Phlyty
 */

namespace Phlyty;

use Zend\EventManager\EventManager;
use Zend\EventManager\EventManagerInterface;
use Zend\Http\PhpEnvironment\Request;
use Zend\Http\PhpEnvironment\Response;
use Zend\Log\Logger;
use Zend\Mvc\Router;
use Zend\Uri\UriInterface;

/**
 * Application container
 *
 * @category   Phlyty
 * @package    Phlyty
 */
class App
{
    /**
     * Event manager instance
     *
     * @var EventManagerInterface
     */
    protected $events;

    /**
     * Logger
     *
     * By default, has no writers attached
     * 
     * @var Logger
     */
    protected $log;

    /**
     * Named routes - used to generate URLs
     *
     * @var array
     */
    protected $namedRoutes = [];

    /**
     * Parameters returned as the result of a route match
     *
     * @var null|Router\RouteMatch
     */
    protected $params;

    /**
     * Request environment
     *
     * @var Request
     */
    protected $request;

    /**
     * Response environment
     *
     * @var Response
     */
    protected $response;

    /**
     * Index of route that matched
     *
     * @var null|int
     */
    protected $routeIndex = -1;

    /**
     * Whether or not we've already registered the route listener
     *
     * @var bool
     */
    protected $routeListenerRegistered = false;

    /**
     * Routes
     *
     * @var Route[]
     */
    protected $routes = [];

    /**
     * Routes by method
     *
     * Array of method => Route[] pairs
     *
     * @var array
     */
    protected $routesByMethod = [];

    /**
     * Retrieve event manager instance
     *
     * If not present, lazy-loads and registers one.
     *
     * @return EventManagerInterface
     */
    public function events()
    {
        if (!$this->events instanceof EventManagerInterface) {
            $this->setEventManager(new EventManager());
        }
        return $this->events;
    }

    /**
     * Create and return an application event instance
     *
     * Sets the target to the App object instance, and, if a route has been
     * matched, adds it to the instance.
     *
     * @return AppEvent
     */
    public function event()
    {
        $event = new AppEvent();
        $event->setTarget($this);
        if (-1 < $this->routeIndex) {
            $route = $this->routes[$this->routeIndex];
            $event->setRoute($route);
        }
        return $event;
    }

    /**
     * Trigger a named event
     *
     * Allows optionally passing more params if desired.
     *
     * @param  string $name
     * @param  array $params
     * @return \Zend\EventManager\ResponseCollection
     */
    public function trigger($name, array $params = [])
    {
        $event = $this->event();
        $event->setParams($params);
        return $this->events()->trigger($name, $event);
    }

    /**
     * Set the event manager instance
     *
     * @param  EventManagerInterface $events
     * @return App
     */
    public function setEventManager(EventManagerInterface $events)
    {
        $this->events = $events;
        return $this;
    }

    /**
     * Retrieve logger
     *
     * Lazy instantiates one if none present.
     * 
     * @return Logger
     */
    public function getLog()
    {
        if (!$this->log instanceof Logger) {
            $this->setLog(new Logger());
        }
        return $this->log;
    }

    /**
     * Set logger
     * 
     * @param  Logger $log 
     * @return App
     */
    public function setLog(Logger $log)
    {
        $this->log = $log;
        return $this;
    }

    /**
     * Retrieve the request environment
     *
     * @return Request
     */
    public function request()
    {
        if (!$this->request instanceof Request) {
            $this->setRequest(new Request);
        }
        return $this->request;
    }

    /**
     * Set the request object instance
     *
     * @param  Request $request
     * @return App
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * Retrieve the response environment
     *
     * @return Response
     */
    public function response()
    {
        if (!$this->response instanceof Response) {
            $this->setResponse(new Response);
        }
        return $this->response;
    }

    /**
     * Set the response object instance
     *
     * @param  Response $response
     * @return App
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;
        return $this;
    }

    /**
     * Halt execution
     *
     * Halts execution, and sets the response status code to $status, as well
     * as sets the response body to the provided message (if any). Any previous
     * content in the response body will be overwritten.
     *
     * @param  int    $status  HTTP response status
     * @param  string $message HTTP response body
     * @return void
     * @throws Exception\HaltException
     */
    public function halt($status, $message = '')
    {
        $response = $this->response();
        $response->setStatusCode($status);
        $response->setContent($message);
        throw new Exception\HaltException();
    }

    /**
     * Stop execution
     *
     * Stops execution immediately, returning the response as it currently
     * stands.
     *
     * @return void
     * @throws Exception\HaltException
     */
    public function stop()
    {
        throw new Exception\HaltException();
    }

    /**
     * Redirect
     *
     * Stop execution, and redirect to the provided location.
     *
     * @param  string|UriInterface $uri
     * @param  int $status
     * @return void
     * @throws Exception\HaltException
     */
    public function redirect($uri, $status = 302)
    {
        if ($uri instanceof UriInterface) {
            $uri = $uri->toString();
        }
        $response = $this->response();
        $response->getHeaders()->addHeaderLine('Location', $uri);
        $response->setStatusCode($status);

        throw new Exception\HaltException();
    }

    /**
     * Pass execution on to next matching route
     *
     * @throws Exception\PassException
     */
    public function pass()
    {
        throw new Exception\PassException();
    }

    /**
     * Map a route to a callback
     *
     * @param  string|Router\RouteInterface $route
     * @param  callable $controller
     * @return Route
     * @throws Exception\InvalidRouteException
     */
    public function map($route, $controller)
    {
        if (is_string($route)) {
            $route = Router\Http\Segment::factory(array(
                'route' => $route,
            ));
        }
        if (!$route instanceof Router\RouteInterface) {
            throw new Exception\InvalidRouteException(
                'Routes are expected to be either strings or instances of Zend\Mvc\Router\RouteInterface'
            );
        }

        $route = new Route($route, $controller);
        $this->routes[] = $route;
        return $route;
    }

    /**
     * Add a route for a DELETE request
     *
     * @param  string|Router\RouteInterface $route
     * @param  callable $controller
     * @return Route
     */
    public function delete($route, $controller)
    {
        $map = $this->map($route, $controller);
        $map->via('delete');
        return $map;
    }

    /**
     * Add a route for a GET request
     *
     * @param  string|Router\RouteInterface $route
     * @param  callable $controller
     * @return Route
     */
    public function get($route, $controller)
    {
        $map = $this->map($route, $controller);
        $map->via('get');
        return $map;
    }

    /**
     * Add a route for a OPTIONS request
     *
     * @param  string|Router\RouteInterface $route
     * @param  callable $controller
     * @return Route
     */
    public function options($route, $controller)
    {
        $map = $this->map($route, $controller);
        $map->via('options');
        return $map;
    }

    /**
     * Add a route for a PATCH request
     *
     * @param  string|Router\RouteInterface $route
     * @param  callable $controller
     * @return Route
     */
    public function patch($route, $controller)
    {
        $map = $this->map($route, $controller);
        $map->via('patch');
        return $map;
    }

    /**
     * Add a route for a POST request
     *
     * @param  string|Router\RouteInterface $route
     * @param  callable $controller
     * @return Route
     */
    public function post($route, $controller)
    {
        $map = $this->map($route, $controller);
        $map->via('post');
        return $map;
    }

    /**
     * Add a route for a PUT request
     *
     * @param  string|Router\RouteInterface $route
     * @param  callable $controller
     * @return Route
     */
    public function put($route, $controller)
    {
        $map = $this->map($route, $controller);
        $map->via('put');
        return $map;
    }

    /**
     * Return the route match parameters
     *
     * If none has been set yet, lazy instantiates an empty
     * Router\RouteMatch container.
     *
     * @return Router\RouteMatch
     */
    public function params()
    {
        if (null === $this->params) {
            $this->params = new Router\RouteMatch([]);
        }
        return $this->params;
    }

    /**
     * Generates a URL based on a named route
     *
     * @param  string $route Named Route instance
     * @param  array $params Parameters to use in url generation, if any
     * @param  array $options Router\RouteInterface-specific options to use in url generation, if any
     * @return string
     */
    public function urlFor($route = null, array $params = [], array $options = [])
    {
        if (null === $route) {
            if (-1 === $this->routeIndex) {
                throw new Exception\InvalidRouteException(
                    'Cannot call urlFor() with empty arguments; no route matched in this request'
                );
            }
            $route  = $this->routes[$this->routeIndex];
            $params = array_merge($this->params()->getParams(), $params);
        }

        if (is_string($route)) {
            if (!isset($this->namedRoutes[$route])) {
                throw new Exception\InvalidRouteException(sprintf(
                    'Route by name "%s" not found; cannot generate URL',
                    $name
                ));
            }
            $route = $this->namedRoutes[$route];
        }

        if (!$route instanceof Route) {
            throw new Exception\InvalidRouteException(sprintf(
                'Invalid route type provided to %s; expects a string',
                __METHOD__
            ));
        }

        return $route->route()->assemble($params, $options);
    }

    /**
     * Run the application
     *
     * @triggers begin
     * @triggers route
     * @triggers halt
     * @triggers 404
     * @triggers 501
     * @triggers 500
     * @triggers finish
     */
    public function run()
    {
        $events  = $this->events();

        try {
            $this->trigger('begin');

            route:

            $request = $this->request();
            $method  = $request->getMethod();
            $route   = $this->route($request, $method);

            $controller = $route->controller();
            $result     = call_user_func($controller, $this);
        } catch (Exception\HaltException $e) {
            // Handle a halt condition
            $this->trigger('halt');
        } catch (Exception\PassException $e) {
            // Pass handling on to next route that matches
            goto route;
        } catch (Exception\PageNotFoundException $e) {
            // Handle a 404 condition
            $this->trigger('404');
        } catch (Exception\InvalidControllerException $e) {
            // Handle situation where controller is invalid
            $this->trigger('501');
        } catch (\Exception $e) {
            // Handle all other exceptions
            $this->trigger('500', ['exception' => $e]);
        }

        $this->trigger('finish');
        $this->response()->send();
    }

    /**
     * Prepare routes
     *
     * Ensure no duplicate routes, determine what named routes are available,
     * and determine which routes respond to which methods.
     */
    protected function prepareRoutes()
    {
        foreach ($this->routes as $index => $route) {
            $this->registerNamedRoute($route);
            $this->registerRouteMethods($route, $index);
        }
    }

    /**
     * Register a named route
     *
     * @param  Route $route
     * @throws Exception\DuplicateRouteException if route with the same name already registered
     */
    protected function registerNamedRoute(Route $route)
    {
        $name = $route->name();

        if (!$name) {
            return;
        }

        if (isset($this->namedRoutes[$name])) {
            if ($route === $this->namedRoutes[$name]) {
                return;
            }

            throw new Exception\DuplicateRouteException(sprintf(
                'Duplicate attempt to register route by name "%s" detected',
                $name
            ));
        }

        $this->namedRoutes[$name] = $route;
    }

    /**
     * Determine what methods a route responds to
     *
     * @param  Route $route
     * @param  int   $index
     */
    protected function registerRouteMethods(Route $route, $index)
    {
        foreach ($route->respondsTo() as $method) {
            if (!isset($this->routesByMethod[$method])) {
                $this->routesByMethod[$method] = [];
            }
            $this->routesByMethod[$method][$index] = $route;
        }
    }

    /**
     * Register the route listener with the route event
     */
    protected function registerRouteListener()
    {
        if ($this->routeListenerRegistered) {
            return;
        }

        $closure = function ($e) {
            $method = $e->getParam('method');
            if (!isset($this->routesByMethod[$method])) {
                throw new Exception\PageNotFoundException();
            }

            $request = $e->getParam('request');
            $routes  = $this->routesByMethod[$method];
            foreach ($routes as $index => $route) {
                if ($index <= $this->routeIndex) {
                    // Skip over routes we've already looked at
                    continue;
                }
                $result = $route->route()->match($request);
                if ($result) {
                    $this->routeIndex = $index;
                    $this->params     = $result;
                    return $route;
                }
            }

            throw new Exception\PageNotFoundException();
        };
        $closure = $closure->bindTo($this);

        $this->events()->attach('route', $closure);;
        $this->routeListenerRegistered = true;
    }

    /**
     * Route the request
     *
     * Attempts to match a route. If matched, sets $params and
     * $routeIndex. Otherwise, throws an
     * Exception\PageNotFoundException.
     *
     * @param  Request $request
     * @param  string  $method
     * @throws Exception\PageNotFoundException
     */
    protected function route(Request $request, $method)
    {
        $this->prepareRoutes();
        $this->registerRouteListener();

        $results = $this->trigger('route', [
            'request' => $request,
            'method'  => $method,
        ]);
        return $results->last();
    }
}
