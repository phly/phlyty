<?php

namespace PhlytyTest\TestAsset;

use Zend\Http\PhpEnvironment\Response as BaseResponse;

class Response extends BaseResponse
{
    public $sentHeaders;
    public $sentContent;

    public function sendHeaders()
    {
        $this->sentHeaders = $this->getHeaders();
        return $this;
    }

    public function sendContent()
    {
        $this->sentContent = $this->getContent();
        return $this;
    }
}

