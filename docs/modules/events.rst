.. _phlyty.modules.events:

Events
======

``Phlyty\App`` composes a `Zend\\EventManager\\EventManager
<http://zf2.readthedocs.org/en/latest/modules/zend.event-manager.event-manager.html>`_
instance. This allows you to trigger events, and attach listeners to events. It
also allows the application to trigger events -- and for you as a developer to
write listeners for those events.

To attach to an event, you simply call ``attach()``; the first argument is the
event name, the second is a valid PHP callable, usually a closure.

.. code-block:: php

    $events = $app->events();

    $events->attach('do', function ($e) {
        echo "I've been triggered!";
    });

    $events->trigger('do'); // "I've been triggered!"

The ``EventManager`` allows you to specify the *priority* at which a listener is
triggered. This allows you to order listeners -- but, more importantly, it
allows you to decide when a listener is triggered in relation to the *default*
listeners. This is important when you consider that the application triggers a
number of events; many of these have listeners registered by default in the
application, *at the default priority*. This means:

- If you register with a *higher* (positive) priority, the listener will be
  triggered *earlier*.
- If you register with a *lower* (negative) priority, the listener will be
  triggered *later*.

The priority argument comes after the listener argument.

.. code-block:: php

    $events = $app->events();

    $events->attach('do', function ($e) {
        echo "Default priority\n";
    });
    $events->attach('do', function ($e) {
        echo "Low priority\n";
    }, -100);
    $events->attach('do', function ($e) {
        echo "High priority\n";
    }, 100);

    $events->trigger('do');

    /* output:
    High priority
    Default priority
    Low priority
    \*/

Defined Events
--------------

As noted previously, the application triggers several events, some of which have
default handlers defined.

**begin**
    Triggered at the very beginning of ``run()``.

**route**
    Triggered during routing. A default route listener is defined and registered
    with default priority. 

**halt**
    Triggered when ``halt()`` is invoked.

**404**
    Triggered if no route matches the current URL.

**501**
    Triggered if a controller bound to a route cannot be invoked (usually
    because it's not a valid callable).

**500**
    Triggered when an exception is raised anywhere during ``run()``.

**finish**
    Triggered immediately prior to sending the response.

Use Cases
---------

You may attach to any of these events in order to alter the application work
flow. 

Error Pages
^^^^^^^^^^^

As an example, if you wish to display a 404 page for your application, you might
register a listener as follows:

.. code-block:: php

    $app->events()->attach('404', function ($e) {
        $app = $e->getTarget();
        $app->render('404');
    });

You could do similarly for 500 and 501 errors. 

Caching
^^^^^^^

You could implement a quick-and-dirty caching layer using the "begin" and
"finish" events.

.. code-block:: php

    // Assume we've instantiated $cache prior to this
    $app->events()->attach('begin', function ($e) use ($cache) {
        $app  = $e->getTarget();
        $req  = $app->request();
        if (!$req->isGet()) {
            return;
        }

        $url  = $req->getUriString();
        $data = $cache->get($url);
        if (!$data) {
            return;
        }

        $app->response()->setContent($data);
        $app->response()->send();
        exit();
    }, 1000); // register at high priority

    $app->events()->attach('finish', function ($e) use ($cache) {
        $app  = $e->getTarget();
        if (!$app->request()->isGet()) {
            return;
        }
        if (!$app->response()->isOk()) {
            return;
        }

        $url  = $app->request()->getUriString();
        $data = $app->response()->getContent();
        $cache->save($url, $data);
    }, -1000); // register at low priority

The above would look for a cache entry matching the current URI, but only if we
have a GET request. If a cache entry is found, we set the response content with
the data, send it, and exit immediately.

Otherwise, when the request is finished, we check if we had a successful GET
request, and, if so, save the response body into the cache using the current
request URI.
