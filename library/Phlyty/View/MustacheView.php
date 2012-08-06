<?php
/**
 * @link      http://github.com/weierophinney/Phlty for the canonical sournce
 * @copyright Copyright (c) 2012 Matthew Weier O'Phinney
 * @license   BSD 3-Clause
 * @package   Phlyty
 */

namespace Phlyty\View;

use Phly\Mustache\Mustache;

/**
 * Mustache view -- proxies to phly_mustache
 *
 * @category   Phlyty
 * @package    Phlyty
 * @subpackage View
 */
class MustacheView extends Mustache implements ViewInterface
{
    /**
     * Render a template
     *
     * Proxies to parent object, but provides defaults for $viewModel and
     * $partials.
     * 
     * @param  mixed $template 
     * @param  mixed $viewModel 
     * @return string
     */
    public function render($template, $viewModel = [], $partials = null)
    {
        return parent::render($template, $viewModel, $partials);
    }
}
