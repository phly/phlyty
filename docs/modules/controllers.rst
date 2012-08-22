.. _phlyty.modules.controllers:

Controllers
===========

Controllers are simply any PHP callable. Controllers will receive exactly one
argument, the ``Phlyty\App`` instance that invokes the controller.

Controllers can thus be:

- anonymous functions, closures, or lambdas
- named functions
- static class methods
- instance methods
- functors (classes defining ``__invoke()``)

This also means you can define and configure your controllers where you want.

Anonymous Function
^^^^^^^^^^^^^^^^^^

Anonymous functions are functions not assigned to a variable, and defined
in-place. Using an anonymous function is perhaps the easiest way to define a
controller.

.. code-block:: php

    $app->get('/', function ($app) {
        // do work here
    });

Closures
^^^^^^^^

Closures are anonymous functions that import variables from the current scope
into the scope of the function. This is done using the ``use`` directive when
declaring the function.

.. code-block:: php

    $config = include 'config.php';
    $app->get('/', function ($app) use ($config) {
        // You can access $config now.
        // Do work here.
    });

Lambdas
^^^^^^^

Lambdas are anonymous functions or closures that are assigned to a variable;
this allows using them in multiple contexts, as well as passing them around by
variable.

.. code-block:: php

    // As a normal lambda
    $lambda = function ($app) {
        // Do work here.
    };
    $app->get('/', $lambda);

    // As a closure
    $config = include 'config.php';
    $lambda = function ($app) use ($config) {
        // You can access $config now.
        // Do work here.
    });
    $app->get('/', $lambda);

Named Functions
^^^^^^^^^^^^^^^

You can also declare functions either in the global namespace or within a
user-defined namespace, and pass the string function name.

.. code-block:: php

    namespace My 
    {
        function home($app)
        {
            // do work here
        }
    }

    $app->get('/', 'My\\home');

Static Class Methods
^^^^^^^^^^^^^^^^^^^^

Static class methods may also be used. You may pass these either in the form of
``[$className, $method]`` or ``ClassName::method``. 

.. code-block:: php

    namespace My
    {
        class Hello
        {
            public static function world($app)
            {
                // do work here...
            }
        }
    }

    // Using array callback notation
    $app->get('/hello/:name', ['My\Hello', 'world']);

    // Using string callback notation
    $app->get('/hello/:name', 'My\Hello::world');

Instance Methods
^^^^^^^^^^^^^^^^

A typical PHP instance method callback can be used. This is great for situations
where you have configurable stateful behavior.

.. code-block:: php

    namespace My
    {
        class Hello
        {
            protected $config;

            public function __construct($config)
            {
                $this->config = $config;
            }

            public static function world($app)
            {
                // do work here...
            }
        }
    }

    $config = include 'config.php';
    $hello  = new My\Hello($config);

    // Using array callback notation
    $app->get('/hello/:name', [$hello, 'world']);

Functors
^^^^^^^^

"Functors" are objects that define the magic method ``__invoke``, and can thus
be called as if they are a function. (Interesting trivia: this is basically how
the PHP internal class ``Closure`` works.) In such an object, you'd simply have
a single method that could act as a controller, the ``__invoke()`` method. You
must instantiate a functor for it to work as such, however.


.. code-block:: php

    namespace My
    {
        class Hello
        {
            protected $config;

            public function __construct($config)
            {
                $this->config = $config;
            }

            public static function __invoke($app)
            {
                // do work here...
            }
        }
    }

    $config = include 'config.php';
    $hello  = new My\Hello($config);

    // As a functor
    $app->get('/hello/:name', $hello);
