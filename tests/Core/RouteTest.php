<?php

use Emoncms\Core\Route;

class RouteTest extends \PHPUnit_Framework_TestCase
{
    public function testValidPostRoute()
    {
        $route = new Route('/controller/action', '/var/www/html', '/var/www/html', 'POST');
        $this->assertEquals('controller', $route->controller);
        $this->assertEquals('action', $route->action);
        $this->assertEquals('', $route->subaction);
        $this->assertEquals('html', $route->format);
        $this->assertEquals('POST', $route->method);
        $this->assertFalse($route->isRouteNotDefined());
    }

    public function testValidSubAction()
    {
        $route = new Route('/controller/action/subaction', '/var/www/html', '/var/www/html/emoncms', 'GET');
        $this->assertEquals('controller', $route->controller);
        $this->assertEquals('action', $route->action);
        $this->assertEquals('subaction', $route->subaction);
        $this->assertEquals('html', $route->format);
        $this->assertEquals('GET', $route->method);
        $this->assertFalse($route->isRouteNotDefined());

    }

    public function testValidUpdateRoute()
    {
        $route = new Route('/controller/action', '/var/www/html', '/var/www/html', 'UPDATE');
        $this->assertEquals('controller', $route->controller);
        $this->assertEquals('action', $route->action);
        $this->assertEquals('', $route->subaction);
        $this->assertEquals('html', $route->format);
        $this->assertEquals('GET', $route->method);
        $this->assertFalse($route->isRouteNotDefined());

    }

    public function testValidSubFolderRoute()
    {
        $route = new Route('/controller/action', '/var/www/html', '/var/www/html/emoncms', 'GET');
        $this->assertEquals('controller', $route->controller);
        $this->assertEquals('action', $route->action);
        $this->assertEquals('', $route->subaction);
        $this->assertEquals('html', $route->format);
        $this->assertEquals('GET', $route->method);
        $this->assertFalse($route->isRouteNotDefined());
    }

    public function testFormat()
    {
        $controllerJson = new Route('/controller.json', '/var/www/html', '/var/www/html/emoncms', 'GET');
        $this->assertEquals('json', $controllerJson->format);

        $actionJson = new Route('/controller/action.json', '/var/www/html', '/var/www/html/emoncms', 'GET');
        $this->assertEquals('json', $actionJson->format);

        $subActionJson = new Route('/controller/action/subaction.json', '/var/www/html', '/var/www/html/emoncms',
            'GET');
        $this->assertEquals('json', $subActionJson->format);
    }

    public function testIsRouteNotDefined()
    {
        $route = new Route('', null, null, null);
        $this->assertEquals('', $route->controller);
        $this->assertEquals('', $route->action);
        $this->assertEquals('', $route->subaction);
        $this->assertEquals('html', $route->format);
        $this->assertTrue($route->isRouteNotDefined());
    }

}