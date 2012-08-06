<?php
/**
 * @link      http://github.com/weierophinney/Phlyty for the canonical source
 * @copyright Copyright (c) 2012 Matthew Weier O'Phinney
 * @license   BSD 3-Clause
 * @package   Phlyty
 */

namespace Phlyty;

use Zend\EventManager\Event;

/**
 * Application event
 *
 * @category   Phlyty
 * @package    Phlyty
 */
class AppEvent extends Event
{
    /**
     * Route currently matched
     *
     * @var Route
     */
    protected $route;

    /**
     * Set matched route
     *
     * @param  Route $route
     * @return AppEvent
     */
    public function setRoute(Route $route)
    {
        $this->route = $route;
        return $this;
    }

    /**
     * Retrieve matched route
     *
     * @return null|Route
     */
    public function getRoute()
    {
        return $this->route;
    }
}
