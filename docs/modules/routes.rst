.. _phlyty.modules.routes:

Routes
======

Routing in ``Phlyty`` is the act of matching *both* an HTTP request method *and*
path information to the controller which will handle it.

Withing ``Phlyty``, Zend Framework 2 routes are used. By default, ZF2's
"Segment" route is used. `Visit the Zend Framework 2 documentation for full
documentation of the segment route <http://packages.zendframework.com/docs/latest/manual/en/modules/zend.mvc.routing.html#zend-mvc-router-http-segment>`_.

At its most basic, the segment route takes literal paths interspersed with named
captures of the form ``:name``, called *segments*. The segment name must consist of
alphanumeric characters *only*. Additionally, you can indicate *optional*
captures using brackets ("[" and "]"). These two simple rule allow using
segments in creative ways:

- ``/calendar/event/:year-:month-:day.:format`` would match
  "/calendar/event/2012-08-19.json", and capture year as "2012", month as "08",
  day as "19", and format as "json".
- ``/news/:post[/:page]`` would match both "/news/foo-bar" as well as
  "/news/foo-bar/3". 

All that said, you may desire more flexibility at times.

Constraints and Defaults
------------------------

For example, what if you want to add constraints to your named segments? As an
example, what if "page", or "year", or "month", or "day" should only ever
consist of digits? 

What if you want to supply defaults for some values?

To do these things, create the ZF2 route manually, and then pass it to the
appropriate HTTP-specific method of ``Phlyty\App``. As an example, let's work
with the "calendar" route we established above. We'll provide both constraints
and defaults for the route.

.. code-block:: php

    use Phlyty\\App;
    use Zend\Mvc\Router\Http\Segment as SegmentRoute;

    $route = SegmentRoute::factory(array(
        'route' => '/calendar/event/:year-:month-:day[.:format]',
        'constraints' => array(
            'year'   => '20\d{2}',
            'month'  => '(0|1)\d',
            'day'    => '(0|1|2|3)\d',
            'format' => '(html|json|xml)',
        ),
        'defaults' => array(
            'format' => 'html',
        ),
    ));

    $app = new App();

    $app->get($route, function ($app) {
        // handle route here
    })->name('calendar');

Note how we pass the ``SegmentRoute`` instance as the argument to
``$app->get()``. This allows us to create a fully-configured, robust route
instance with constraints and defaults, while still honoring the interface that
``Phlyty\\App`` offers.

You could extend this to provide tree routes, literal routes, and more;
basically, any route type Zend Framework 2 provides may be used. 

For more information on ZF2 routes, `please visit the ZF2 routes documentation
<http://zf2.readthedocs.org/en/latest/modules/zend.mvc.routing.html>`_.
