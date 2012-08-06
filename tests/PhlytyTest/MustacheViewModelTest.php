<?php

namespace PhlytyTest;

use Phlyty\App;
use Phlyty\View\MustacheViewModel;
use PHPUnit_Framework_TestCase as TestCase;
use Zend\Escaper\Escaper;

class MustacheViewModelTest extends TestCase
{
    public function setUp()
    {
        $this->app   = new App();
        $this->model = new MustacheViewModel($this->app);
    }

    public function testMagicAppMethodReturnsAppInstance()
    {
        $this->assertSame($this->app, $this->model->__app());
    }

    public function testMagicEscaperMethodReturnsEscaperInstance()
    {
        $escaper = $this->model->__escaper();
        $this->assertInstanceOf('Zend\Escaper\Escaper', $escaper);
    }

    public function testCanPassEscaperInstanceToConstructor()
    {
        $escaper = new Escaper();
        $model   = new MustacheViewModel($this->app, $escaper);
        $this->assertSame($escaper, $model->__escaper());
    }

    public function testBindingHelperGivesItObjectsScope()
    {
        $callable = function ($message) {
            return $this->__escaper()->escapeHtml($message);
        };
        $this->model->bindHelper('message', $callable);
        $message  = '<p>This is a message</p>';
        $expected = $this->model->__escaper()->escapeHtml($message);
        $this->assertEquals($expected, call_user_func($this->model->message, $message));
    }
}
