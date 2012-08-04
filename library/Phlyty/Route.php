<?php
/**
 * @link      http://github.com/weierophinney/Phlty for the canonical sournce
 * @copyright Copyright (c) 2012 Matthew Weier O'Phinney
 * @license   BSD 3-Clause
 * @package   Phlyty
 */

namespace Phlyty;

use Closure;
use Zend\Mvc\Router;

/**
 * Route
 *
 * Encapsulates the route, the callback to execute, and the methods it will
 * respond to.
 *
 * @category   Phlyty
 * @package    Phlyty
 */
class Route
{
    /**
     * Allowed methods
     *
     * @var array
     */
    protected static $allowedMethods = array(
        'DELETE',
        'GET',
        'OPTIONS',
        'PATCH',
        'POST',
        'PUT',
    );

    /**
     * Callable assigned to this route
     *
     * @var callable
     */
    protected $controller;

    /**
     * Methods this route responds to
     *
     * @var array
     */
    protected $methods = array();

    /**
     * Name of this route (if any)
     *
     * @var null|string
     */
    protected $name;

    /**
     * Route object
     *
     * @var Router\RouteInterface
     */
    protected $route;

    /**
     * Add an HTTP method you will allow
     *
     * @param  string $method
     * @return void
     */
    public static function allowMethod($method)
    {
        $method = strtoupper($method);
        if (in_array($method, static::$allowedMethods)) {
            return;
        }
        static::$allowedMethods[] = $method;
    }

    /**
     * Constructor
     *
     * Accepts the router and controller.
     *
     * @param  Router\RouteInterface $route
     * @param  callable $controller
     */
    public function __construct(Router\RouteInterface $route, $controller)
    {
        $this->route      = $route;
        $this->controller = $controller;
    }

    /**
     * Get the actual route interface
     *
     * @return Router\RouteInterface
     */
    public function route()
    {
        return $this->route;
    }

    /**
     * Retrieve controller assigned to this route
     *
     * @return callable
     */
    public function controller()
    {
        if (is_string($this->controller)
            && !is_callable($this->controller)
            && !class_exists($this->controller)
        ) {
            throw new Exception\InvalidControllerException(sprintf(
                'Invalid controller specified: "%s"',
                $this->controller
            ));
        }

        if (is_string($this->controller)
            && !is_callable($this->controller)
            && class_exists($this->controller)
        ) {
            $controller = $this->controller;
            $this->controller = new $controller;
        }

        if (!is_callable($this->controller)) {
            $controller = $this->controller;
            if (is_array($controller)) {
                $method     = array_pop($controller);
                $controller = array_pop($controller);

                if (is_object($controller)) {
                    $controller = get_class($controller);
                }

                $controller .= '::' . $method;
            }
            if (is_object($controller)) {
                $controller = get_class($controller);
            }
            throw new Exception\InvalidControllerException(sprintf(
                'Controller "%s" is not callable',
                $controller
            ));
        }

        return $this->controller;
    }

    /**
     * Assign one or more methods this route will respond to
     *
     * Additional arguments will be used as additional methods.
     *
     * @param  string|array $method
     * @return Route
     */
    public function via($method)
    {
        if (is_string($method) && 1 < func_num_args()) {
            $method = func_get_args();
        }

        if (is_string($method)) {
            $method = (array) $method;
        }

        $method = array_map('strtoupper', $method);

        foreach ($method as $test) {
            if (!in_array($test, self::$allowedMethods)) {
                throw new Exception\InvalidMethodException(sprintf(
                    'Invalid method "%s" specified; must be one of %s;'
                    . ' if the method is valid, add it using Phlyty\Route::allowMethod()',
                    $test,
                    implode(', ', static::$allowedMethods)
                ));
            }
            if (!isset($this->methods[$test])) {
                $this->methods[$test] = true;
            }
        }

        return $this;
    }

    /**
     * Does this route respond to the given method?
     *
     * @param  string $method
     * @return bool
     */
    public function respondsTo($method)
    {
        $method = strtoupper($method);
        return isset($this->methods[$method]);
    }

    /**
     * Retrieve and/or set the route name
     *
     * Sets the route name if a non-empty string is provided, and then returns
     * the Route instance to allow a fluent interface.
     *
     * Otherwise, returns the route name.
     *
     * @param  null|string $name
     * @return Route|string
     */
    public function name($name = null)
    {
        if (is_string($name) && !empty($name)) {
            $this->name = $name;
            return $this;
        }
        return $this->name;
    }
}
