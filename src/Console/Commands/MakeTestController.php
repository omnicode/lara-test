<?php

namespace LaraTest\Console\Commands;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use LaraTest\Console\Commands\Traits\ControllerMaker;

class MakeTestController extends TestCommand
{
    use ControllerMaker;
    /**
     * @var string
     */
    protected $name = 'make:test-controller';

    /**
     * @var string
     */
    protected $description = 'Create Controller test';

    /**
     * @var string
     */
    protected $type = 'Test Controller';

    /**
     * @var string
     */
    protected $appPath = 'Http' . DIRECTORY_SEPARATOR . 'Controllers';

    protected $testCaseClass = 'TestCaseController';

    protected $testStab = 'test-case-controller';

    protected $stub = 'test-controller';

    private $config = '';

    private $defaultAttr = '_default';

    private $controllerPathWithRoutes;

    private $currentMethodRoutes = [];

    private $classFullName;

    private $currentClassContent;

    /**
     * MakeTestController constructor.
     * @param Filesystem $files
     * @param Router $router
     */
    public function __construct(Filesystem $files, Router $router)
    {
        parent::__construct($files);
        $this->setControllerPathWithRoutes($router);
    }

    /**
     * @param Router $router
     */
    public function setControllerPathWithRoutes(Router $router)
    {
        $routeLists = $this->getRouteLists($router);
        foreach ($routeLists as $routeList) {
            $controller = array_pull($routeList, 'controller');
            $controller = str_replace($this->getAppNamespace(), app_path() . DIRECTORY_SEPARATOR, $controller);
            $controller .= '.php';
            $controllerMethod = array_pull($routeList, 'controller-method');
            $this->controllerPathWithRoutes[$controller][$controllerMethod][] = $routeList;
        }
    }

    /**
     * @param $file
     * @return mixed
     */
    protected function getTestClassContent($file)
    {
        $this->currentClassContent = file($file);
        $this->classFullName = $this->getClassFullName($file);
        $classShortName = $this->getClassShortName($file);
        $classMethods = $this->getClassMethods($this->classFullName);

        if (empty($classMethods)) {
            return '';
        }

        $isAdminController = str_contains($file, 'Admin');
        $this->config = Config::get('lara_test.controller');

        $classContent = '';
        if (!empty($this->controllerPathWithRoutes[$file])) {
            foreach ($classMethods as $method) {
                $this->currentMethodRoutes = !empty($this->controllerPathWithRoutes[$file][$method])
                    ? $this->controllerPathWithRoutes[$file][$method] : [];
                if ($method == '__construct') {
                    $method = 'construct';
                }
                $classContent .= $this->getTestMethods($method, $classShortName, $isAdminController);
            }
        } else {
            foreach ($classMethods as $method) {

                if ($method == '__construct') {
                    $method = 'construct';
                }

                $classContent .= $this->addToDoTestMethod($method, $classShortName);
            }
        }

        return $classContent;
    }

    /**
     * @param $method
     * @param $classShortName
     * @return mixed|string
     */
    private function addToDoTestMethod($method, $classShortName)
    {
        $message = sprintf('There is not route can directly call this %s method', $method);
        return $this->getCorrectedToDoTestMethodText($method, $classShortName, $message);
    }

    /**
     * @param $method
     * @param $class
     * @param $isAdminController
     * @return mixed
     */
    private function getTestMethods($method, $class, $isAdminController)
    {
        $routes = $this->getRoutes();

        if (empty($routes)) {
            $methodName = ucfirst($method);
            return $this->getCorrectedToDoTestMethodText($methodName, $class);
        }

        if (count($routes) == 1) {
            return $this->getTestMethodsBy($method, $class, reset($routes), $isAdminController);
        }

        $content = '';
        foreach ($routes as $item) {
            $content .= $this->getTestMethodsBy($method, $class, $item, $isAdminController, true);
        }

        return $content;
    }

    /**
     * @param $method
     * @param $class
     * @param $isAdminController
     * @param bool $isMultiRoute
     * @return mixed|string
     */
    private function getTestMethodsBy($method, $class, $route, $isAdminController, $isMultiRoute = false)
    {
        $content1 = $this->getTestMethodIfNotAuthenticated($method, $class, $route, $isMultiRoute);
        $content2 = $this->getTestMethodIfAuthenticated($method, $class, $route, $isAdminController, $isMultiRoute);
        return $content1 . $content2;
    }

    /**
     * @param $method
     * @param $route
     * @param $isMultiRoute
     * @return string
     */
    private function getMethodNameBy($method, $route, $isMultiRoute)
    {
        $routeName = $route['name'];
        $routeParts = explode('.', $routeName);
        $routeName = '';

        foreach ($routeParts as $routePart) {
            $routeName .= ucfirst(camel_case($routePart));
        }

        $method = ucfirst($method);
        $methodName = $isMultiRoute ? sprintf('ForRote_%s_%s', $routeName, $method) : $method;
        return ucfirst($methodName);
    }

    /**
     * @return array
     */
    private function getRoutes()
    {
        $routes = [];

        foreach ($this->currentMethodRoutes as $methodRoute) {
            $routes[] = $methodRoute['route'];
        }

        return $routes;
    }

    /**
     * @param $class
     * @return string
     */
    private function getControllerStartPart($class)
    {
        return str_plural(lcfirst(str_replace('Controller', '', $class)));
    }


    /**
     * @param $method
     * @param $class
     * @param $route
     * @param bool $isMultiRoute
     * @return mixed|string
     */
    protected function getTestMethodIfNotAuthenticated($method, $class, $route, $isMultiRoute = false)
    {
        $methodName = $this->getUserAuthenticateStateMethodName($method, $class, $route, $isMultiRoute, false);
        $contents = [
            $this->getRowRoutePart($route),
            '$this->assertRedirectedToRoute(\'signin\')'
        ];
        $methodContent = $this->methodContentRowTemplate($contents);
        return $this->getCorrectedTestMethodTextBy($methodName, $methodContent);
    }

    /**
     * @param $method
     * @param $class
     * @param $route
     * @param $isMultiRoute
     * @param $isAuthenticated
     * @return string
     */
    private function getUserAuthenticateStateMethodName($method, $class, $route, $isMultiRoute, $isAuthenticated = true)
    {
        $authedStr = $isAuthenticated ? '' : 'Not';
        $methodName = $this->getMethodNameBy($method, $route, $isMultiRoute);

        return sprintf('%sMethodIn%sIfUser%sAuthenticated', $methodName, $class, $authedStr);
    }

    /**
     * @param $method
     * @param $class
     * @param $route
     * @param $isAdminController
     * @param $isMultiRoute
     * @return mixed|string
     */
    protected function getTestMethodIfAuthenticated($method, $class, $route, $isAdminController, $isMultiRoute)
    {
        $methodActualContent = $this->getMethodBody($this->currentClassContent, $this->classFullName, $method);
//        if (str_contains($methodActualContent, 'if'))  {
//            return $this->getTestBranchesMethodIfAuthenticated($method, $class, $route, $isAdminController, $isMultiRoute);
//        } else {
            $methodName = $this->getUserAuthenticateStateMethodName($method, $class, $route, $isMultiRoute);
            $methodContent = $this->getMethodContentForAuthenticatedTest($method, $class, $route, $isAdminController);
            return $this->getCorrectedTestMethodTextBy($methodName, $methodContent);
//        }
    }

    /**
     * @param $method
     * @param $class
     * @param $route
     * @param $isAdminController
     * @return string
     */
    public function getMethodContentForAuthenticatedTest($method, $class, $route, $isAdminController)
    {
        $login  = $isAdminController ? 'loginAsAdmin' : 'login';
        $contents = [
            sprintf('$this->%s()',$login),
            $this->getRowRoutePart($route),
            '$this->assertResponseOk()'
        ];
        $methodContent = $this->methodContentRowTemplate($contents);
        $methodContent .= $this->getMethodContentViewHasPart($method, $class, $route);

        return $methodContent;
    }

    /**
     * @param $route
     * @return string
     */
    private function getRowRoutePart($route)
    {
        $routeName = $route['name'];
        $routeMethod = $route['method'];
        return sprintf("%sthis->route('%s', '%s')", '$', $routeMethod,  $routeName);
    }

    /**
     * @param $class
     * @param $route
     * @return string
     */
    public function getMethodContentViewHasPart($method, $class, $route)
    {
        $methodContent = $this->getMethodBody($this->currentClassContent, $this->classFullName, $method);
        $pos = strpos($methodContent, 'compact');

        if ($pos === false) {
            return '';
        }

        $substr = substr($methodContent, $pos);
        $startQuotePos = strpos($substr, '(');
        $endQuotePos = strpos($substr, ')');
        $attributesStr = substr($substr, $startQuotePos + 1, $endQuotePos - $startQuotePos - 1);
        $attributesStr = str_replace("'", '', $attributesStr);
        $attributesStr = str_replace('"', '', $attributesStr);
        $attributesStr = str_replace(' ', '', $attributesStr);
        $attributesStr = str_replace(PHP_EOL, '', $attributesStr);
        $attributeArr =  explode(',', $attributesStr);

        $viewHasContent = '';
        foreach ($attributeArr as $attribute) {
            if ($attribute == $this->defaultAttr) {
                $attribute = $this->getControllerStartPart($class);
            }
            $rowContent = sprintf("%sthis->assertViewHas('%s')",'$', $attribute);
            $viewHasContent .= $this->methodContentRowTemplate($rowContent);
        }

        return $viewHasContent;
    }

}