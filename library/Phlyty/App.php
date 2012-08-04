<?php

namespace Phlyty;

use Zend\Http\PhpEnvironment\Request;
use Zend\Http\PhpEnvironment\Response;

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
}
