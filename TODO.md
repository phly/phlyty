Implement helpers:
- view() -> get template engine
- render($template, $vars) -> render a view (likely writes content to response)
- flash($name, $message) - message available in the next request. Assigns $flash as object/placeholder to view
- flashNow($name, $message) - message available in this request. Assigns $flash as object/placeholder to view
- flashKeep() - add one more to the expiration hops of flash messages (useful for redirects)
- getLog() - get log instance
