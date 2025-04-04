<?php
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;

return simpleDispatcher(function(RouteCollector $r) {
    $r->addRoute('POST', '/api/chat', ['\\src\\Controller\\ChatController', 'handleRequest']);
    $r->addRoute('POST', '/api/scrape', ['\\src\\Controller\\ScrapeController', 'handleScrape']);
});
?>