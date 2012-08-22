<?php
/**
 * @link      http://github.com/weierophinney/Phlyty for the canonical source
 * @copyright Copyright (c) 2012 Matthew Weier O'Phinney
 * @license   BSD 3-Clause
 * @package   Phlyty
 */

namespace Phlyty\View;

use Closure;
use Phlyty\App;
use Zend\Escaper\Escaper;

/**
 * Base view model for use with Mustache.
 *
 * The main purpose of this class is to provide the view model with the App
 * instance, thus giving it access to the various helper methods available in
 * that class. Additionally, it provides an instance of Escaper, allowing you
 * to do context-specific escaping within helpers you define in the view model.
 *
 * @category   Phlyty
 * @package    Phlyty
 * @subpackage View
 */
class MustacheViewModel
{
    /**
     * @var App
     */
    protected $app;

    /**
     * @var Escaper
     */
    protected $escaper;

    /**
     * Constructor
     *
     * Receive and set the App instance as a protected property.
     * If an Escaper instance is passed, it will be assigned;
     * otherwise, an Escaper instance will be instantiated and
     * assigned.
     *
     * @param  App          $app
     * @param  null|Escaper $escaper
     */
    public function __construct(App $app, Escaper $escaper = null)
    {
        $this->app = $app;

        if (null === $escaper) {
            $escaper = new Escaper();
        }

        $this->escaper = $escaper;
    }

    /**
     * Retrieve application instance
     *
     * @return App
     */
    public function __app()
    {
        return $this->app;
    }

    /**
     * Retrieve escaper instance
     *
     * @return Escaper
     */
    public function __escaper()
    {
        return $this->escaper;
    }

    /**
     * Bind a helper within the view model object's context
     *
     * Allows using '$this' within the helper in order to access public
     * properties and methods. Binds the helper to the public property
     * given by $name.
     *
     * @param  string $name
     * @param  callable $helper
     * @return MustacheViewModel
     */
    public function bindHelper($name, callable $helper)
    {
        $helper = Closure::bind($helper, $this);
        $this->{$name} = $helper;
        return $this;
    }
}
