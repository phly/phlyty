<?php
/**
 * @link      http://github.com/weierophinney/Phlyty for the canonical source
 * @copyright Copyright (c) 2012 Matthew Weier O'Phinney
 * @license   BSD 3-Clause
 * @package   Phlyty
 */

namespace Phlyty\Exception;

/**
 * Exception indicating a duplicate route was detected
 *
 * @category   Phlyty
 * @package    Phlyty
 * @subpackage Exception
 */
class DuplicateRouteException extends \DomainException implements ExceptionInterface
{}
