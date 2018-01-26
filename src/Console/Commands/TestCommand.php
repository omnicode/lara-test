<?php
namespace LaraTest\Console\Commands;

use Illuminate\Console\AppNamespaceDetectorTrait;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Routing\Router;

abstract class TestCommand extends GeneratorCommand
{

    use AppNamespaceDetectorTrait;

    protected $appPath;

    protected $stub;

    protected $testCaseClass = false;

    protected $testStab = false;

    protected $all = '_all';

    private $fileContent = '';

    /**
     * TestCommand constructor.
     * @param Filesystem $files
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct($files);
    }

    /**
     * @param $file
     * @return mixed
     */
    abstract protected function getTestClassContent($file);

    /**
     *
     */
    public function fire()
    {
        $filePath = str_replace('/', DIRECTORY_SEPARATOR, $this->getNameInput());
        $files = $this->getFilesBy($filePath);
        $this->fixTestCaseClass();

        foreach ($files as $file) {
            $path = str_replace(app_path(), base_path('tests'), $file);
            $path = str_replace('.php', 'Test.php', $path);

            if (!$this->files->exists($path)) {
                $this->makeDirectory($path);
                $this->prepareFileContent($file);
                $info = 'created';
            } else {
                $info = 'updated';
                $this->addFileContent($file, $path);
            }

            $this->files->put($path, $this->fileContent, FILE_APPEND);
            $this->resetFileContent();
            $this->info(sprintf('%s %s %s successfully.',
                str_replace(base_path('tests') . DIRECTORY_SEPARATOR, '', $path), $this->type, $info ));
        }
    }

    /**
     * @param string $filePath
     * @return array|mixed
     */
    private function getFilesBy($filePath = '')
    {
        list($paths, $files) = $this->getPathsAndFilesBy($filePath);

        foreach ($files as $file) {
            if (!$this->files->exists($file)) {
                $this->confirmWithMessage($file, 'class');
            }
        }

        foreach ($paths as $path) {
            if ($this->files->exists($path)) {
                foreach ($this->files->allFiles($path) as $file) {
                    $files[] = $file->getRealPath();
                }
            } else {
                $this->confirmWithMessage($path, 'directory');
            }
        }

        $files = array_unique($files);
        return $files;
    }

    /**
     * @param string $filePath
     * @return array
     */
    private function getPathsAndFilesBy($filePath = '')
    {
        $paths = [];
        $files = [];

        if (str_contains($filePath, ',')) {
            // for miangamic ham path, and class name
            $filePaths = explode(',', $filePath);

            foreach ($filePaths as $path) {
                list($paths, $files) = $this->addFilePathToPathsOrFiles($path, $paths, $files);
            }

        } elseif (!empty($filePath)) {
            list($paths, $files) = $this->addFilePathToPathsOrFiles($filePath, $paths, $files);
        } else {
            $paths[] = $this->getAppPath();
        }

        $files = array_unique($files);
        $paths = $this->processPaths($paths);
        return [$paths, $files];
    }

    /**
     * @param $filePath
     * @param $paths
     * @param $files
     * @return array
     */
    private function addFilePathToPathsOrFiles($filePath, $paths, $files)
    {
        if (ends_with($filePath, '.php')) {
            //for directly class path
            $files[] = $this->getAppPath($filePath, false);
        } else {
            //for path class test
            $paths[] = $this->getAppPath($filePath);
        }

        return [$paths, $files];
    }

    /**
     * @param string $name
     * @param bool $directory
     * @return string
     */
    private function getAppPath($name = '', $directory = true)
    {
        $name = trim($name);

        if ($name === $this->all) {
            $name = '';
        } elseif (!empty($name)) {
            $name = DIRECTORY_SEPARATOR . $name;
        }

        if ($directory) {
            $name .= DIRECTORY_SEPARATOR;
        }

        return app_path($this->appPath . $name);
    }

    /**
     * @param $paths
     * @return array
     */
    private function processPaths($paths)
    {
        $paths = array_unique($paths);
        if (!empty($paths)) {
            sort($paths);
            $parentPaths = [array_shift($paths)];

            foreach ($paths as $path) {
                if(!starts_with($path, $parentPaths)) {
                    $parentPaths[] = $path;
                }
            }

            $paths = $parentPaths;
        }

        return $paths;
    }

    /**
     *
     */
    private function fixTestCaseClass()
    {
        $path = base_path(sprintf(
            'tests%s%s%s%s.php',
            DIRECTORY_SEPARATOR,
            $this->appPath,
            DIRECTORY_SEPARATOR,
            $this->testCaseClass
        ));
        if (true) { //Tmp TODO
//        if (!$this->files->exists($path)) {
            $this->makeDirectory($path);
            $this->files->put($path, $this->files->get(sprintf('%s/stubs/%s.stub', __DIR__, $this->testStab)).PHP_EOL, FILE_APPEND);
            $this->info(sprintf('%s created successfully.', $this->testCaseClass));
        } else {
            if ($this->confirm(sprintf('%s class already exists do you wont to overwrite it!!!', $this->testCaseClass))) {
                $this->files->put($path, $this->files->get(sprintf('%s/stubs/%s.stub', __DIR__, $this->testStab)).PHP_EOL, FILE_APPEND);
                $this->info(sprintf('%s class overwrite successfully.', $this->testCaseClass));
            }
        }
    }

    /**
     * @param $file
     */
    private function prepareFileContent($file)
    {
        $fullClassName = $this->getClassFullName($file);
        $className = $this->getClassShortName($file);
        $nameSpace = "Tests" .DIRECTORY_SEPARATOR . str_replace_last(DIRECTORY_SEPARATOR . $className, '', $fullClassName);
        $nameSpace = str_replace($this->laravel->getNamespace(), '', $nameSpace);

        $this->fileContent = $this->files->get(sprintf('%s/stubs/%s.stub', __DIR__, $this->stub));
        $this->fileContent = str_replace('TestNamespace', $nameSpace, $this->fileContent);
        $this->fileContent = str_replace('    testMethod', 'testMethod', $this->fileContent);
        $this->fileContent = str_replace('TestClass ', $className . 'Test ', $this->fileContent);
        $this->fileContent = $this->fixParentClass($nameSpace, $this->fileContent);

        $classContent = $this->getTestClassContent($file);
        $this->insertContentToTestClass($classContent);

        if (!str_contains($this->fileContent, 'function')) {
            $toDoTestMethod = $this->getCorrectedToDoTestMethodText('toDo', $className);
            $this->insertContentToTestClass($toDoTestMethod);
        }

        $this->resetTestClassContent();
    }

    /**
     * @param $file
     * @param $path
     */
    private function addFileContent($file, $path)
    {
        $this->fileContent = $this->files->get($path);
        $this->fileContent = str_replace_last('}', sprintf('testMethod%s}', PHP_EOL), $this->fileContent);
//        $useClass = sprintf('%suseTestClass%sclass ', PHP_EOL, PHP_EOL);
        $useClass = sprintf('useTestClass%sclass ', PHP_EOL, PHP_EOL);
        $this->fileContent = str_replace_last(PHP_EOL. 'class ',$useClass , $this->fileContent);

        $classContent = $this->getTestClassContent($file);
        $this->insertContentToTestClass($classContent);
        $this->resetTestClassContent();
    }

    /**
     *
     */
    private function resetTestClassContent()
    {
        $this->fileContent = str_replace('useTestClass', '', $this->fileContent);
        $this->fileContent = str_replace('{' . PHP_EOL. '    testTrait', '{', $this->fileContent);
        $this->fileContent = str_replace('testTrait', '', $this->fileContent);
        $this->fileContent = str_replace(PHP_EOL  . 'testMethod', '', $this->fileContent);
    }

    /**
     * @param $className
     * @param $stub
     * @return string
     */
    private function fixParentClass($className, $stub)
    {
        $parentFullClass = '\TestCase';

        if ($this->testCaseClass) {
            $parentFullClass = sprintf('Tests\%s\%s', $this->appPath, $this->testCaseClass);
        }

        $classNamePartsCount = count(explode(DIRECTORY_SEPARATOR, $className));
        $parentClassNamePartsCount = count(explode(DIRECTORY_SEPARATOR, $parentFullClass));

        if ($classNamePartsCount + 1 > $parentClassNamePartsCount) {
            $stub = str_replace_first('useTestClass', sprintf('use %s;%s    useTestClass', $parentFullClass, PHP_EOL), $stub);
        }


        return str_replace_first(' TestParentClass', ' ' . last(explode('\\', $parentFullClass)), $stub);
    }

    /**
     * @param $val
     * @param $name
     */
    private function confirmWithMessage($val, $name)
    {
        if (!$this->confirm(sprintf('%s %s does not exists do you wont to continue', $val, $name))) {
            die();
        }
    }

    /**
     *
     */
    private function resetFileContent()
    {
        $this->fileContent = '';
    }

    /**
     * @param $methodName
     * @param $methodContent
     * @return mixed|string
     */
    protected function getCorrectedTestMethodTextBy($methodName, $methodContent)
    {
        $method =  $this->methodTemplate($methodName, $methodContent);
        if(str_contains($this->fileContent, 'test' . $methodName)) {
            if (!str_contains($this->fileContent, $method)) {
                if ($this->confirm(sprintf('This %s test method already exists in @TODO FIX NAME test class. Do you wont to overwrite it', $methodName))) {
                    $this->overwriteTestMethod($methodName, $methodContent);
                }
            }
            return '';
        }

        return $method;
    }

    /**
     * @param $methodName
     * @param $funcContent
     * @return mixed|string
     */
    protected function methodTemplate($methodName, $funcContent)
    {
        $methodTemplate =  sprintf(
              '    /**%s'
            . '     *%s'
            . '     */%s'
            . '    public function test_f_name()%s'
            . '    {%s'
            . '        f_content'
            . '    }%s'
            . '%s',
            PHP_EOL,
            PHP_EOL,
            PHP_EOL,
            PHP_EOL,
            PHP_EOL,
            PHP_EOL,
            PHP_EOL,
            PHP_EOL
        );

        $methodTemplate = str_replace('_f_name', $methodName, $methodTemplate);
        $methodTemplate = str_replace('        f_content', $funcContent, $methodTemplate);
        return $methodTemplate;
    }

    /**
     * @param $contents
     * @return string
     */
    protected function methodContentRowTemplate($contents)
    {
        if (!is_array($contents)) {
            $contents = [$contents];
        }

        $str = '';

        foreach ($contents as  $content) {
            $str .= sprintf('        %s;%s', $content, PHP_EOL);
        }

        return $str;
    }

    /**
     * @param $method
     * @param $class
     * @param string $message
     * @return mixed|string
     */
    protected function getCorrectedToDoTestMethodText($method, $class, $message = '')
    {
        $methodName = sprintf('%sMethodIn%sClass', ucfirst($method), $class) ;
        $message = !empty($message) ? '//TODO ' . $message : '//TODO';
        $methodContent = $this->methodContentRowTemplate($message);

        if (str_contains($this->fileContent, $methodName)) {

            if (!str_contains($this->fileContent, $methodContent)) {
                $this->overwriteTestMethod($methodName, $methodContent);
            }

            return '';
        }

        return $this->methodTemplate($methodName, $methodContent);
    }

    /**
     * @param $method
     * @param $content
     */
    protected function overwriteTestMethod($method, $content)
    {
        $methodFullContent = $this->methodTemplate($method, $content);
        $methodNamePos = strpos($methodFullContent, $method);
        $methodChangedContent = subStr($methodFullContent, $methodNamePos);

        $strPos = strpos($this->fileContent, $method);
        $subStr = substr($this->fileContent, $strPos);
        $strPosFunc = strpos($subStr, 'function');

        if (!$strPosFunc) {
            $strPosFunc = strpos($subStr, 'testMethod');
        }

        $subStr = substr($subStr, 0, $strPosFunc);
        $methodEndPos = strrpos($subStr, '}');
        $methodActualContent = substr($subStr, 0, $methodEndPos + 5);

        $this->fileContent = str_replace($methodActualContent, $methodChangedContent, $this->fileContent);
    }


    /**
     * @param $file
     * @return mixed
     */
    protected function getClassFullName($file) {
        $file = str_replace(app_path() . '\\', $this->laravel->getNamespace(), $file);
        return str_replace('.php', '', $file);
    }

    /**
     * @param $file
     * @return mixed
     */
    protected function getClassShortName($file) {
        $file = str_replace('.php', '', $file);
        return last(explode(DIRECTORY_SEPARATOR, $file));
    }

    protected function getClassMethods($class)
    {
        $parentClass = get_parent_class($class);

        $traits = array_keys(class_uses($class));
        $traitsMethods = [];

        foreach ($traits as $trait) {
            $traitsMethods = array_merge($traitsMethods, get_class_methods($trait));
        }

        if ($parentClass) {
            $classMethods = array_diff(get_class_methods($class), get_class_methods($parentClass));
            return array_diff($classMethods, $traitsMethods);
        }

        return get_class_methods($class);
    }

    /**
     * @param $method
     */
    protected function insertContentToTestClass($method)
    {
        $this->fileContent  = str_replace('testMethod', $method . 'testMethod', $this->fileContent);
    }

    /**
     * @param $stab
     * @return string
     */
    protected function getStubBy($stab = false)
    {
        if (empty($stab)) {
            $stab = $this->stub;
        }

        return sprintf('%s/stubs/%s', __DIR__, $stab);
    }

    protected function getStub() {

    }

    /**
     * @param $router
     * @return array
     */
    public function getRouteLists(Router $router)
    {
        $routeContent = [];
        foreach ($router->getRoutes()->getRoutes() as $index => $route) {
            if (!empty($route->getAction()['controller']) && starts_with($route->getAction()['controller'], 'MyDevData')) {
                $action = $route->getAction();
                $controllerArr = explode('@', $action['controller']);
                $controller = array_first($controllerArr);
                $controllerMethod = array_last($controllerArr);
                $routeMethod = $this->getRouteMethod($route);

                $routeContent[] = [
                    'controller' => $controller,
                    'controller-method' => $controllerMethod,
                    'route' => [
                        'name' => !empty($action['as']) ? $action['as'] : false,
                        'method' => $routeMethod,
                        'uri'=> $route->getUri(),
                    ],
                    'middleware' => !empty($action['middleware']) ? $action['middleware'] : false,
                ];
            }
        }

        return $routeContent;
    }

    /**
     * @param $route
     * @return string
     */
    private function getRouteMethod($route)
    {
        $routeMethods = $route->getMethods();
        $routeMethod = array_diff($routeMethods, ['method']);
        $routeMethod = array_first($routeMethod);
        return strtolower($routeMethod);
    }

    /**
     * @param $fileContent
     * @param $class
     * @param $method
     * @return string
     */
    protected function getMethodBody($fileContent, $class, $method) {
        $class = new \ReflectionClass($class);
        $method = $class->getMethod($method);
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();
        $methodBody = array_slice($fileContent, $startLine + 1, $endLine - $startLine - 2);
        return implode('', $methodBody);
    }

}
