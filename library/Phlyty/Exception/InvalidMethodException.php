<?php
/**
 * @link      http://github.com/weierophinney/Phlty for the canonical sournce
 * @copyright Copyright (c) 2012 Matthew Weier O'Phinney
 * @license   BSD 3-Clause
 * @package   Phlyty
 */

namespace Phlyty\Exception;

/**
 * Exception indicating an invalid HTTP method
 *
 * @category   Phlyty
 * @package    Phlyty
 * @subpackage Exception
 */
class InvalidMethodException extends \InvalidArgumentException implements ExceptionInterface
{}
