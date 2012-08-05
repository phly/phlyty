<?php
/**
 * @link      http://github.com/weierophinney/Phlty for the canonical sournce
 * @copyright Copyright (c) 2012 Matthew Weier O'Phinney
 * @license   BSD 3-Clause
 * @package   Phlyty
 */

namespace Phlyty;

use Zend\Http\PhpEnvironment\Request;
use Zend\Http\PhpEnvironment\Response;
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
     * Named routes - used to generate URLs
     *
     * @var array
     */
    protected $namedRoutes = array();

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
    protected $routeIndex;

    /**
     * Routes
     *
     * @var Route[]
     */
    protected $routes;

    /**
     * Routes by method
     *
     * Array of method => Route[] pairs
     *
     * @var array
     */
    protected $routesByMethod = array();

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
     * Run the application
     *
     * @todo exception handling when preparing routes (?)
     * @todo 404 exception handling when routing
     * @todo exception handling when dispatching (including handling HaltException, PageNotFoundException, InvalidControllerException)
     */
    public function run()
    {
        $request = $this->request();
        $method  = strtoupper($request->getMethod());
        $route   = $this->route($request, $method);

        $controller = $route->controller();
        $result     = call_user_func($controller, $this);
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
     * Route the request
     *
     * Attempts to match a route. If matched, sets $params and
     * $routeIndex. Otherwise, throws an
     * Exception\PageNotFoundException.
     *
     * @param  Request $request
     * @param  string  $method
     */
    protected function route(Request $request, $method)
    {
        $this->prepareRoutes();

        if (!isset($this->routesByMethod[$method])) {
            throw new Exception\PageNotFoundException();
        }

        foreach ($this->routesByMethod[$method] as $index => $route) {
            $result = $route->route()->match($request);
            if ($result) {
                $this->routeIndex = $index;
                $this->params     = $result;
                return $route;
            }
        }

        throw new Exception\PageNotFoundException();
    }
}
