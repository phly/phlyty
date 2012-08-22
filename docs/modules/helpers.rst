.. _phlyty.modules.helpers:

Helpers
=======

``Phlyty`` ships with a number of built-in helper methods in the ``Phlyty\App``
class. These fall under roughly four categories:

- Workflow-related helpers (``halt``, ``stop``, ``pass``, ``redirect``,
  ``events``, ``event``, ``trigger``, ``getLog``)
- HTTP-related helpers (``request``, ``response``)
- Route-related helpers (``params``, ``urlFor``)
- View-related helpers (``view``, ``viewModel``, ``render``, ``flash``)

Workflow Helpers
----------------

Workflow helpers shape the flow of the application. They allow you to return
from execution early, either because the response is ready, or because we know
an error condition has occurred; redirect to another URI; pass on execution to
another route and controller; or work with events. 

``halt($status, $message='')``
    Halts execution immediately, setting the response status to ``$status``,
    and, if ``$message`` is provided, setting the response body to that message.
    No further code will be executed in the controller following this call.

    .. code-block:: php

        $name = $app->params('name', false);
        if (!$name) {
            $app->halt(500, 'Missing name; cannot continue execution');
        }
        // do something with $name now...

``stop()``
    Halts execution, sending the response as it currently exists. You might call
    this if you wanted to return a file download, for instance.

    .. code-block:: php

        $image = $app->params('image', false);
        if ($image && file_exists($image)) {
            $stream = fopen($image, 'r');
            $out    = fopen('php://output', 'w');
            stream_copy_to_stream($stream, $out);
            $app->response()->setBody($out);
            $app->stop();
        }
        // show some error message here...

``pass()``
    Tells the application that no more processing of this controller should be
    done, but that it should continue iterating through routes to look for
    another one that matches the current URI.

    .. code-block:: php

        $app->get('/:locale', function ($app) {
            $locale = $app->params()->getParam('locale', 'en_US');
            Locale::setDefault($locale);
            $app->pass();
        });

        $app->get('/[:locale]', function ($app) {
            // This matches the previous route, which means when pass() is
            // called by the previous controller, this route will be matched
            // and this controller invoked.
            // 
            // Display home page
        });

``redirect($url, $status = 302)``
    Sets the response status code to ``$status`` and the ``Location`` header to
    ``$url``, and immediately halts execution and sends the response. Any code
    following the call in the controller will not be executed.

    .. code-block:: php

        $app->get('/user/:username', function ($app) {
            $username = $app->params()->getParam('username', false);
            if (!$username) {
                $this->redirect('/login');
            }
            // Code below here will only execute if we did not redirect
        });

``events()``
    Returns a ``Zend\EventManager\EventManager`` instance. This allows you to
    attach event listeners as well as trigger events. :ref:`See the section on events for more information <phlyty.modules.events>`.

    .. code-block:: php

        $app->events()->attach('route', function ($e) use ($app) {
            $route = $e->getRoute();
            if (!in_array($route->getName(), ['profile', 'comment', 'post']) {
                return;
            }
            
            // check if we have an authenticated user, and throw an exception
            // otherwise
            // ...
        }, -10); // registering to execute after routing finishes

``event()``
    Returns a new ``Phlyty\AppEvent`` instance with the target set to the
    ``Phlyty\App`` instance, and the route populated with the currently matched
    route.

``trigger($name, array $params = [])``
    Trigger the named event, optionally passing parameters to compose in the
    ``Phlyty\\AppEvent`` instance.

    .. code-block:: php

        $app->get('/', function ($app) {
            $app->trigger('homepage', $app->params()->getParams());
        });

``getLog()``
    Gets the currently registered ``Zend\Log\Logger`` instance, lazy-loading one
    if none is present. You will need to attach writers to the log instance, and
    then invoke one or more logging methods.

    .. code-block:: php

        $logger = $app->getLog()
        $logger->addWriter('stream', [ 
            'stream'        => 'php://stderr',
            'log_separator' => "\n",
        ]);
        $logger->info('This is an informational message');

HTTP-Related Helpers
^^^^^^^^^^^^^^^^^^^^

A web application is really about receiving an HTTP request, deciding what to do
with it, and returning an HTTP response back to the client. In ``Phlyty\App``,
the request and response objects help you with this.

``request()``
    Returns the request object. See `the ZF2 Zend\\Http\\PhpEnvironment\\Request documentation <http://packages.zendframework.com/docs/latest/apidoc/classes/Zend.Http.PhpEnvironment.Request.html>`_ for more details.

    .. code-block:: php

        // Getting query string (aka GET) parameters
        $query  = $app->request()->getQuery();
        $single = $app->request()->getQuery($name, $default);

        // Getting POST parameters
        $post   = $app->request()->getPost();
        $single = $app->request()->getPost($name, $default);

        // Getting headers
        $headers = $app->request()->getHeaders();
        $header  = $app->request()->getHeader($name, $default);
        $value   = $header->getFieldValue();

        // Getting ENV values
        $values = $app->request()->getEnv();
        $value  = $app->request()->getEnv($name, $default);

        // Getting $_SERVER values
        $values = $app->request()->getServer();
        $value  = $app->request()->getServer($name, $default);

        // Get the URI
        $uri = $app->request()->getUri(); // Zend\Uri\Uri object
        $uri = $app->request()->getUriString(); // string

        // Get the Cookie header
        $cookies = $app->request()->getCookie();
        $cookie  = $cookie[$cookieName];

        // Testing request type
        $app->request()->isXmlHttpRequest();
        $app->request()->isGet();
        $app->request()->isPost();
        $app->request()->isPut();
        $app->request()->isDelete();
        $app->request()->isOptions();
        $app->request()->isPatch();

        // Set the base url
        // This is a nice way to run your app in a subdirectory.
        $app->request()->setBaseUrl('/~matthew/sites/foo');

``response()``
    Returns the response object. See `the ZF2 Zend\\Http\\PhpEnvironment\\Response documentation <http://packages.zendframework.com/docs/latest/apidoc/classes/Zend.Http.PhpEnvironment.Response.html>`_ for more details.

    .. code-block:: php

        // Setting a header
        $app->response()->getHeader()->addHeaderLine($name, $value);

        // Setting the status code
        $app->response()->setStatusCode(201);

        // Setting the response body
        $app->response()->setContent($content);

Route-Related Helpers
^^^^^^^^^^^^^^^^^^^^^

The main purpose of a microframework is to map URL paths to their handlers. Once
you have, there are two principal route-related activities you will be
performing in most requests: you will need to access parameters matched in
the URL, and you will need to generate URLs based on the routes you've defined.

``params()``
    Returns the `Zend\\Mvc\\Router\\RouteMatch <http://packages.zendframework.com/docs/latest/apidoc/classes/Zend.Mvc.Router.RouteMatch.html>`_
    instance returned by the route that matched the URL. The API is roughly as
    follows:

    .. code-block:: php

        $params = $app->params();
        $single = $params->getParam('single', 'default value');
        $array  = $params->getParams();

``urlFor($route = null, array $params = [], array $options = [])``
    Generates a URL based on the named ``$route``, using ``$params`` to fill in
    named segments in the URL, and any route-specific generation ``$options``
    provided. If ``$route`` is not present, it will assume the current
    matched route; if ``$params`` is not present, any defaults used when
    creating the route will be used.

    If a base URL is present in the request, it will be prepended to the
    generated URL.

    .. code-block:: php

        $app->get('/blog[/:year[/:month[/:day]]]', function ($app) {
            // ...
        })->name('blog-by-date');

        $url = $app->urlFor('blog-by-date, [
            'year'  => 2012,
            'month' => '08',
            'day'   => 21,
        ]); // "/blog/2012/08/21"

View-Related Helpers
^^^^^^^^^^^^^^^^^^^^

The goal of a controller is to produce a response to return to the client. In
most cases, that response will contain some content. In web applications, this
is typically referred to as a "View" (from the design pattern
"Model-View-Controller", or "MVC"). Typically, the "view" is functionality that
renders a template.

``Phlyty`` provides helpers for setting and retrieving the view object that will
be used to render templates, as well as a method for actually rendering a named
template using the current view object. Other helpers allow you to set a "view
model" -- an object that encapsulates the data you wish to represent in the view
-- as well as retrieve instances of that view model. Finally, ``Phlyty``
provides functionality for setting and retrieving "flash" messages -- messages
you wish to present in the view layer -- but most likely on a subsequent page
(typically following a redirect -- for instance, to indicate that a record was
updated).

- View-related helpers (``view``, ``viewModel``, ``render``, ``flash``)

``setView(Phlyty\View\ViewInterface $view)``
    Sets the view object. The ``ViewInterface`` defines simply a method
    ``render($template, $viewModel = [])``.

``view()``
    Retrieves the current view object, which should implement the
    ``ViewInterface``. By default, this is ``Phlyty\View\MustacheView``, which
    is an implementation that utilizes `phly_mustache
    <http://weierophinney.github.com/phly_mustache>`_, a `Mustache
    implementation <http://mustache.github.com>`_.

``setViewModelPrototype($model)``
    Allows specifying a prototype object to use for view models. The object
    provided will be *cloned* when retrieved later.

    .. code-block:: php

        $model = new stdClass();
        $app->setViewModelPrototype($model);

``viewModel()``
    Retrieves a *clone* of the currently registered view model object. By
    default, if none has been registered, an instance of
    ``Phlyty\View\MustacheView`` is provided. 

    .. code-block:: php

        $model = $app->viewModel();
        $model->foo = $bar;
        $model->bindHelper('bar', function () {
            return $this->__escaper()->escapeHtml($this->foo) . '!';
        });

``render($template, $viewModel = [])``
    Renders a named ``$template`` using the currently registered view object,
    and passing the specified ``$viewModel``, if any. It is up to the view
    object to resolve the template name to a resource it may use, and to
    determine how to utilize the ``$viewModel`` provided.

    Once the content is rendered, it's injected as the content of the response
    object.

    .. code-block:: php

        $app->render('pages/foo', $model);

``flash($name, $message = null)``
    Create or retrieve a flash message. Flash messages expire after a single
    "hop"; in other words, after more than one page visit, the flash message
    will disappear. If you pass just a ``$name`` to ``flash()``, it will attempt
    to retrieve the message; passing a ``$message`` to it will set it.

    By default, the ``MustacheViewModel`` composes the ``$app`` instance,
    allowing you to retrieve flash messages. As an example, you could do the
    following to create a view variable for retrieving a formatted message:

    .. code-block:: php

        $model = $app->viewModel();
        $model->bindHelper('messages', function () {
            $message = $this->__app()->flash('foo');
            if (empty($message)) {
                return '';
            }
            return sprint(
                '<div class="flash">%s</div>',
                $this->__escaper()->escapeHtml($message)
            );
        });

For more about views, :ref:`see the section on Views<phlyty.modules.views>`.

    
