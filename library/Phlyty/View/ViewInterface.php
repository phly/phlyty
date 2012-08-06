<?php
/**
 * @link      http://github.com/weierophinney/Phlty for the canonical sournce
 * @copyright Copyright (c) 2012 Matthew Weier O'Phinney
 * @license   BSD 3-Clause
 * @package   Phlyty
 */

namespace Phlyty\View;

/**
 * View interface
 *
 * @category   Phlyty
 * @package    Phlyty
 * @subpackage View
 */
interface ViewInterface
{
    /**
     * Render a template, optionally passing a view model/variables
     * 
     * @param  string $template 
     * @param  mixed $viewModel
     * @return string
     */
    public function render($template, $viewModel = []);
}
