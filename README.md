Phlyty - A Microframework using ZF2 components
==============================================

Phlyty is a microframework using [ZF2 components](http://packages.zendframework.com/ "ZF2").

Basically, ZF2 components are used to provide the boring parts:

- Routing
- HTTP request and response
- Events
- Logging

I used [phly_mustache](http://weierophinney.github.com/phly_mustache) as the
templating engine.

Like a variety of other microframeworks, the core application object simply
allows you to attach callables to defined routes. You then interact with the
HTTP request and response, and potentially router, and return something that the
application can then send back in the HTTP response.

The goal is to use PHP 5.4 idioms and provide a lightweight mechanism for
building prototypes and simple websites.

At this time, the basic functionality is all written and tested, and
basic end-user and API documentation are now available via:

- http://phlyty.rtfd.org
