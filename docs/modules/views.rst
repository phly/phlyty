.. _phlyty.modules.views:

Views
=====

Views are the presentation layer of the application. Typically, you will use a
templating engine to create the presentation, though ``Phlyty`` makes no
assumptions about what or how that engine works. It only requires that you
provide a class implementing ``Phlyty\View\ViewInterface`` that provides a
``render`` method; it is then up to you to pass the two arguments to that method
on to your templating engine in order to obtain a representation.

If the above does not suit your needs, you can, of course, always instantiate
your own view objects and use them as you see fit in the application.

The ViewInterface
-----------------

The ``ViewInterface`` is a trivial definition:

.. code-block:: php

    namespace Phlyty\View;

    interface ViewInterface
    {
        /**
        * Render a template, optionally passing a view model/variables
        *
        * @param  string $template
        * @param  mixed $viewModel
        * @return string
        \*/
        public function render($template, $viewModel = []);
    }

Mustache Integration
--------------------

``Phlyty`` uses `phly_mustache <http://weierophinney.github.com/phly_mustache`
by default, and provides some convenience classes and functionality around this
templating engine.

First, it provides ``Phlyty\View\MustacheView``. This is a simple extension of
``Phly\Mustache\Mustache`` that alters the ``render()`` method to make it suit
the ``ViewInterface``.

Second, it provides ``Phlyty\View\MustacheViewModel``. This class can simplify
creation of your view models by providing several convenience features. First,
it composes the application instance, as well as an instance of
``Zend\Escaper\Escaper``. These allow you to access any application helpers
you might want when providing your view representation, as well as
context-specific escaping mechanisms (for instance, to escape CSS, JavaScript,
HTML attributes, etc.). Additionally, it provides a convenience method,
``bindHelper()``, which allows you to create closures as model properties, and
have them bound to the model instance; this allows the closures to have access
to the model via ``$this``, and thus access the application and escaper
instances, as well as all properties.

The application instance is available via the pseudo-magic method ``__app()``,
and the escaper via ``__escaper()``.

.. code-block:: php

    $model = $app->viewModel();
    $model->route = 'bar';
    $model->bindHelper('link', function () {
        return $this->__app()->urlFor($this->route);
    });

The template might look like this:

.. code-block:: html

    You should <a href="{{link}}">visit</a>

