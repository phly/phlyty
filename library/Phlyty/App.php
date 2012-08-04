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

/**
 * Application container
 *
 * @category   Phlyty
 * @package    Phlyty
 */
class App
{
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
}
