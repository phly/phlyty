.. _phlyty.overview:

Overview
========

``Phlyty`` is a PHP microframework written using `Zend Framework 2 <http://packages.zendframework.com>`_ 
components. It's goals are:

- Route based on HTTP method and path, but allow the full spectrum of ZF2
  routes.
- Allow any callable as a "controller".
- Provide basic features and helpers surrounding flash messages, URL generation,
  and request/response handling.
- Provide view rendering out-of-the-box, but allow the user to plugin whatever
  view rendering solution they desire.

The features and API are roughly analagous to `Slim Framework
<http://www.slimframework.com>`_.

Installation
------------

I recommend using `Composer <https://getcomposer.org/>`. Once you have composer,
create the following ``composer.json`` file in your project:

.. code-block:: javascript

    {
        "repositories": [
            {
                "type": "composer",
                "url": "http://packages.zendframework.com/"
            }
        ],
        "minimum-stability": "dev",
        "require": {
            "phly/phlyty": "dev-master"
        }
    }

Then run ``php composer.phar install`` to install the library. This will ensure
you retrieve ``Phlyty`` and all its dependencies.

Basic Usage
-----------

The most basic "Hello World!" example looks something like this:

.. code-block:: php

    use Phlyty\App;
    include 'vendor/autoload.php';

    $app = new App();
    $app->get('/', function ($app) {
        echo "Hello, world!";
    });

    $app->run();

Assuming the above is in ``index.php``, you can fire up the PHP 5.4 development
web server to test it out:

.. code-block:: bash

    php -S 127.0.0.1:8080

If you then visit ``http://localhost:8080/``, you'll see your "Hello, world!"
text.

Routing
^^^^^^^

The main ``Phlyty\App`` class contains methods for each of the main HTTP request
methods, and these all have the same API: ``method($route, $controller)``. They
include:

- ``get()``
- ``post()``
- ``put()``
- ``delete()``
- ``options()``
- ``patch()``

All of them return a ``Phlyty\Route`` object, allowing you to further manipulate
the instance -- for example, to name the route, indicate what additional HTTP
methods to respond to, or to access the controller or the composed ZF2 route
object. (You can actually instantiate a ZF2 route object and pass that instead
of a string for the route, which gives you more power and flexibility!)

.. code-block:: php

    $app->map('/', function ($app) {
        echo "Hello, world!";
    })->name('home'); // name the route

Alternately, you can use teh ``map()`` method. This simply creates the route,
but does not assign it to a specific HTTP method. You would then use the
``via()`` method of the route object to assign it to one or more HTTP methods:

.. code-block:: php

    $app->map('/', function ($app) {
        echo "Hello, world!";
    })->via('get', 'post')->name('home'); // name the route, and have it respond
                                          // to both GET and POST requests

By default, if you pass a string as the ``$route`` argument, ``Phlyty\App`` will
create a ZF2 ``Segment`` route; you can read up on those `in the ZF2 manual <http://packages.zendframework.com/docs/latest/manual/en/modules/zend.mvc.routing.html#zend-mvc-router-http-segment>`_.
In such routes, a string preceded by a colon will indicate a named variable
to capture: ``/resource/:id`` would capture an "id" value. You can have many
named segments, and even optional segments.

Controllers and Helpers
^^^^^^^^^^^^^^^^^^^^^^^

Your controllers can be any PHP callable. In the examples, I use closures, but
any callable is accepted. The callable will receive exactly one argument, the
``Phlyty\App`` instance. 

From the App instance, you have the following helper methods available:

- ``params()`` returns a ``Zend\Mvc\Router\RouteMatch`` instance, from which you
  can then pull values. In the example in the previous paragraph, you can pull
  the "id" using ``$app->params()->getParam('id', false)``.
- ``request()`` returns a ``Zend\Http\PhpEnvironment\Request`` instance. This
  gives you access to headers, query, post, cookie, files, env, and system
  parameters. In most cases, you use ``getType($name, $default)``; e.g.
  ``$app->request()->getQuery('name', 'Matthew')`` would retrieve the "name"
  query string value, using "Matthew" as the default.
- ``response()`` returns a ``Zend\Http\PhpEnvironment\Response`` instance. This
  allows you to manipulate response headers, and to set the response body.
- ``flash($name, $message)`` lets you both set and receive flash messages.
- ``urlFor($route = null, array $params = [], array $options = [])`` allows you
  to generate a URI based on the routes you've created. If you pass no
  arguments, it assumes it should use the current route. Otherwise, you must
  pass a route name; as such, it's good practice to name your routes. (Any
  ``$params`` you provide will be used to replace named segments in the route.)
- ``pass()`` tells the application to move on to the next matching route, if
  any.
- ``redirect($uri, $status = 302)`` will redirect. Hint: use ``urlFor()`` to
  generate the ``$uri`` value!
- ``halt($status, $message = '')`` halts execution immediately, and sends the
  provided message.
- ``stop()`` halts execution, sending the current response.
- ``events()`` accesses the composed event manager, allowing you to register
  listeners and trigger events.
- ``event()`` returns a ``Phlyty\AppEvent`` instance with the current route
  composed.
- ``trigger`` triggers an event.
- ``view()`` returns the view renderer, which should implement
  ``Phlyty\View\ViewInterface``. You can call ``setView()`` to change the view
  implementation. Additionally, you can always instantiate and use your own view
  implementation.
- ``viewModel()`` returns a `Zend\View\Model\ModelInterface`` implementation; by
  default, it's of type ``Phlyty\View\MustacheViewModel``. This allows you to
  inject variables, set the template, etc. If you want to use an alternate view
  model, either directly instantiate it, or provide a prototype instance to
  ``setViewModelPrototype()``.
- ``render($template, $viewModel = [])`` will render a template and/or a view
  model, and place the rendered content into the Response body.

