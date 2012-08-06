<?php
/**
 * @link      http://github.com/weierophinney/Phlyty for the canonical source
 * @copyright Copyright (c) 2012 Matthew Weier O'Phinney
 * @license   BSD 3-Clause
 * @package   Phlyty
 */

namespace Phlyty\Exception;

/**
 * Exception indicating an invalid route type
 *
 * @category   Phlyty
 * @package    Phlyty
 * @subpackage Exception
 */
class InvalidRouteException extends \InvalidArgumentException implements ExceptionInterface
{}
