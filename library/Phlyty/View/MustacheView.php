<?php
/**
 * @link      http://github.com/weierophinney/Phlyty for the canonical source
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
     * @param  string $template  Either a template string or a template file in the template path
     * @param  mixed  $viewModel An array or object with items to inject in the template
     * @param  mixed  $partials  A list of partial names/template pairs for rendering as partials
     * @return string
     */
    public function render($template, $viewModel = [], $partials = null)
    {
        return parent::render($template, $viewModel, $partials);
    }
}
