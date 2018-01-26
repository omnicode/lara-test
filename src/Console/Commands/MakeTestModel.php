<?php

namespace LaraTest\Console\Commands;

class MakeTestModel extends TestCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:test-model';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a test class for models. Must specify a "class name" or "_all"';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Test Model';

    /**
     * @var string
     */
    protected $appPath = 'Models';

    protected $testCaseClass = 'TestCaseDatabaseTransactions';

    protected $testStab = 'test-case-database-transactions';

    protected $stub = 'test-model';

    private $methodRouteEnds = [

    ];

    private $routeDoesNotViewHas = [
        'create'
    ];

    /**
     * @var bool
     */
    private $oldClassHeader = false;

    private $methodBody;

    protected function getTestClassContent($file) {
        $fullClassName = $this->getClassFullName($file);
        $classShortName = $this->getClassShortName($file);
        $classMethods = $this->getClassMethods($fullClassName);

        $isAdminController = false;
        if (str_contains($file, 'Admin')) {
            $isAdminController = true;
        }


        $fileContent = file($file);
        $classContent = '';
        foreach ($classMethods as $method) {
            if (strpos($method, 'hasOne')) {

                preg_match('|\((.+?)\)|is', $methodBody, $relationMethod);
                $relationMethod = substr(array_first(explode(',', array_first($relationMethod))), 1);
                $relationMethod = rtrim(trim($relationMethod), ')');
                $newMethodBody = '$this->assertHasOneRelation(' . $className . '::class, \'' . $methodName . '\', ' . $relationMethod . ');';

            } elseif (strpos($method, 'hasMany')) {

                preg_match('|\((.+?)\)|is', $methodBody, $relationMethod);
                $relationMethod = substr(array_first(explode(',', array_first($relationMethod))), 1);
                $relationMethod = rtrim(trim($relationMethod), ')');
                $newMethodBody = '$this->assertHasManyRelation(' . $className . '::class, \'' . $methodName . '\', ' . $relationMethod . ');';

            } elseif (strpos($method, 'belongsToMany')) {

                preg_match('|\((.+?)\)|is', $methodBody, $relationMethod);
                $relationMethod = substr(array_first(explode(',', array_first($relationMethod))), 1);
                $relationMethod = rtrim(trim($relationMethod), ')');
                $newMethodBody = '$this->assertBelongsToManyRelation(' . $className . '::class, \'' . $methodName . '\', ' . $relationMethod . ');';

            } elseif (strpos($method, 'belongsTo')) {

                preg_match('|\((.+?)\)|is', $methodBody, $relationMethod);
                $relationMethod = substr(array_first(explode(',', array_first($relationMethod))), 1);
                $relationMethod = rtrim(trim($relationMethod), ')');
                $newMethodBody = '$this->assertBelongsToRelation(' . $className . '::class, \'' . $methodName . '\', ' . $relationMethod . ');';

            }
            $this->methodBody = $this->getMethodBody($fileContent, $fullClassName, $method);
            dd($this->methodBody);
            if ($method === 'index') {
                $classContent .= $this->getTestMethods($method, $classShortName, $isAdminController);
            } elseif ($method === 'create') {
                $classContent .= $this->getTestMethods($method, $classShortName, $isAdminController);
//            } elseif ($method === 'store') {
//                $stub = $this->getTestSoreMethods($method, $classShortName, $stub, $isAdminController);
            } else {
                if ($method == '__construct') {
                    $method = 'construct';
                }
                $classContent .= $this->getToDoTestMethod($method, $classShortName);
            }
        }

        return $classContent;
    }

    /**
     * @param $fileContent
     * @param $class
     * @param $method
     * @return string
     */
//    protected function getMethodBody($fileContent, $class, $method) {
//        $class = new \ReflectionClass($class);
//        $method = $class->getMethod($method);
//        $startLine = $method->getStartLine();
//        $endLine = $method->getEndLine();
//        $methodBody = array_slice($fileContent, $startLine + 1, $endLine - $startLine - 2);
//        return implode('', $methodBody);
//    }

    private function createTestMethodBody($type) {
        $methodBody = '';

        

    }

        /**
     * @return bool|null
     */
    public function fire1()
    {
        $this->fixTestCaseClass();

        if ($this->getNameInput() === '_all') {
            $modelFiles = $this->getModelFiles($this->getModelPaths());
        } else {
            $classPath = app_path('Models') . DIRECTORY_SEPARATOR . $this->getNameInput();
            $classPath = str_replace('/', DIRECTORY_SEPARATOR, $classPath);
            if(!$this->files->isFile($classPath)) {
                $this->info($classPath. ' path does not exists!');
                die();
            }
            $modelFiles = [$classPath];
        }

        $modelFiles = $this->checkingExistenceOfFiles($modelFiles);

        if (count($modelFiles) === 0) {
            $this->info('The script is finished. The script did not change anything.');
            die();
        }

        foreach ($modelFiles as $modelFile) {
            $path = str_replace(app_path(), base_path('tests'), $modelFile);
            $path = str_replace('.php', 'Test.php', $path);

            $modelOldTest = false;

            if($this->files->exists($path)) {
                $modelOldTest = $this->files->get($path);
                $modelOldTest = $this->deleteRelationsInOldTest($modelOldTest);
            }

            $this->makeDirectory($path);

            $modelNewTest = $this->buildClassForFile($modelFile);

            if($modelOldTest) {
                $modelNewTest = $this->changeModelOldTest($modelNewTest, $modelOldTest);
            }

            $this->files->put($path, $modelNewTest, FILE_APPEND);
            $this->info($this->type . ' created successfully.');
        }
    }

    /**
     * @param $modelFilesPath
     * @return bool
     */
    private function checkingExistenceOfFiles($modelFilesPath) {
        if (empty($modelFilesPath) || !is_array($modelFilesPath)) {
            return false;
        }

        $oldModelFiles = $this->getModelFiles($this->getModelPaths('getTestModelPath'));

        if (empty($oldModelFiles) || !is_array($oldModelFiles)) {
            return $modelFilesPath;
        }

        array_walk($oldModelFiles, function(&$value) {
            $modelPosition = strpos($value, 'Models');
            $value = substr($value,$modelPosition);
        });
        $oldModelFiles = array_filter($oldModelFiles, function($value) use($modelFilesPath) {
            for($i = 0; $i < count($modelFilesPath); $i++) {
                if(strpos($modelFilesPath[$i], substr($value, 0, -8) . '.php')) {
                    return $value;
                    break;
                }
            }
        });

        if(count($oldModelFiles) > 0) {

            $oldModelFilesText = implode(';' . PHP_EOL, $oldModelFiles);

            if ($this->confirm($oldModelFilesText . PHP_EOL . 'file(s) already exist. Do you wish to override it?')) {
                return $modelFilesPath;
            }


            $modelFilesPath = array_filter($modelFilesPath, function($value) use($oldModelFiles) {
                $property = true;

                foreach($oldModelFiles as $oldModelFile) {
                    if(strpos($value, substr($oldModelFile, 0, -8) . '.php')) {
                        $property = false;
                        break;
                    }
                }

                if($property) {
                    return $value;
                }
            });
        }

        return $modelFilesPath;
    }

    /**
     * @param $modelNewTest
     * @param $modelOldTest
     * @return string
     */
    private function changeModelOldTest($modelNewTest, $modelOldTest)
    {
        return str_replace_last('}', PHP_EOL . $modelOldTest . PHP_EOL . '}', $modelNewTest);
    }

    /**
     * @param $modelOldTest
     * @return bool
     */
    private function deleteRelationsInOldTest($modelOldTest)
    {
        $symbolPosition = strpos($modelOldTest, '{');
        $modelOldMethods = rtrim(trim(substr($modelOldTest, $symbolPosition + 1)), '}');
        $this->oldClassHeader = substr($modelOldTest, 0, $symbolPosition);
        $modelOldMethods = explode(' function ', $modelOldMethods);

        if(count($modelOldMethods) <= 1) {
            return false;
        }

        foreach($modelOldMethods as $key => $value) {
            $methodName = array_first(explode('(', $value));

            if (strpos($methodName, 'testRelation') === 0) {
                unset($modelOldMethods[$key]);
            }
        }

        return '    ' . implode(' function ', $modelOldMethods);
    }

    /**
     *
     */
    protected function fixTestCaseClass()
    {
        $path = base_path('tests/Models/TestCaseDatabaseTransactions.php');

        if (!$this->files->exists($path)) {
            $this->makeDirectory($path);
            $this->files->put($path, $this->files->get(__DIR__ . '/stubs/test-case-database-transactions.stub') . PHP_EOL, FILE_APPEND);
            $this->info('TestCaseDatabaseTransactions created successfully.');
        }
    }

    /**
     * @param $file
     * @return mixed|string
     */
    public function buildClassForFile($file)
    {
        $stub = $this->files->get($file);
        $fullClassName = $this->getClassFullName($file);
        $className = last(explode('\\', $fullClassName));
        $stub = str_replace_first($this->laravel->getNamespace(), "Tests\\", $stub);

        if(strpos($stub, config('app.name') . '\Models\BaseModel')) {
            $stub = str_replace_first(config('app.name') . '\Models\BaseModel', $fullClassName . ';' . PHP_EOL . 'use Tests\Models\TestCaseDatabaseTransactions', $stub);
        } else {
            $stub = str_replace_first(';', ';' . PHP_EOL  . PHP_EOL . 'use ' . $fullClassName . ';' . PHP_EOL . 'use Tests\Models\TestCaseDatabaseTransactions;', $stub);
        }

        $stub = str_replace_first('extends BaseModel', 'extends TestCaseDatabaseTransactions', $stub);
        $stub = str_replace(' ' . $className, ' ' . $className . 'Test', $stub);

        $stub = $this->processMethods($stub, $className);
        return $stub;
    }

    /**
     * @param $text
     * @param $className
     * @return string
     */
    private function processMethods($text, $className)
    {

        $arr = explode(' function ', $text);

        /* ********* To remove unnecessary properties in a class ********* */
        $classHeader = array_first(explode('{', array_first($arr)));

        if($this->oldClassHeader) {
            $classHeader = $this->oldClassHeader;
        }

        if(count($arr) <= 1) {
            return $classHeader . '{' . PHP_EOL  . PHP_EOL . '}';
        }

        $arr[0] = $classHeader . '{' . PHP_EOL . '    public';
        /* ********* To remove unnecessary properties in a class end ********* */

        $max = count($arr) - 1;

        for ($i = 0; $i < $max; $i++) {

            $methodName = array_first(explode('(', $arr[$i + 1]));

            preg_match('|{(.+?)}|is', $arr[$i + 1], $methodBody);
            $methodBody = array_last($methodBody);

            if (strpos($methodName, 'Attribute') && !strpos($methodBody, 'hasOne') && !strpos($methodBody, 'hasMany') && !strpos($methodBody, 'belongsTo')) {
                $arr[$i + 1] = false;

                if(isset($arr[$i]) && isset($arr[$i + 2])) {
                    $arr[$i] .= '    public ';
                } else {
                    $arr[$i] .= PHP_EOL . '}';
                }
            } else {
                $newMethodBody = false;

                $arr[$i + 1] = str_replace($methodName . '(', 'testRelation' . ucfirst($methodName). 'Method(', $arr[$i + 1]);

                /* ********** for method body *********** */
                $relationMethod = $this->changeMethodBodyByClass($className, $methodName, $methodBody, $newMethodBody);
                $headerClass = array_first($arr);

                if($this->checkPathInHeaderForClass($headerClass,$relationMethod)) {
                    $arr[0] = $headerClass;
                }
                /* ********** for method body end *********** */

                if ($newMethodBody) {
                    $arr[$i + 1] = str_replace($methodBody,PHP_EOL . '        ' . $newMethodBody . PHP_EOL . '    ', $arr[$i + 1]);
                }

            }

        }

        $newArray = [];
        foreach($arr as $array) {
            if($array) {
                $newArray[] = $array;
            }
        }

        $text = trim(implode( ' function ', $newArray));

        if(substr_count($text, '{') > 1) {
            $symbolPosition = strripos(rtrim($text, '}'), '}') + 1;
            return substr($text, 0, $symbolPosition) . PHP_EOL . '}';
        }

        $symbolPosition = strpos($text, '{') + 1;
        return substr($text, 0, $symbolPosition) . PHP_EOL . PHP_EOL . '}';

    }

    /**
     * @param $className
     * @param $methodName
     * @param $methodBody
     * @param $newMethodBody
     * @return bool|string
     */
    private function changeMethodBodyByClass($className, &$methodName, $methodBody, &$newMethodBody)
    {
        $relationMethod = false;

        if (strpos($methodBody, 'hasOne')) {

            preg_match('|\((.+?)\)|is', $methodBody, $relationMethod);
            $relationMethod = substr(array_first(explode(',', array_first($relationMethod))), 1);
            $relationMethod = rtrim(trim($relationMethod), ')');
            $newMethodBody = '$this->assertHasOneRelation(' . $className . '::class, \'' . $methodName . '\', ' . $relationMethod . ');';

        } elseif (strpos($methodBody, 'hasMany')) {

            preg_match('|\((.+?)\)|is', $methodBody, $relationMethod);
            $relationMethod = substr(array_first(explode(',', array_first($relationMethod))), 1);
            $relationMethod = rtrim(trim($relationMethod), ')');
            $newMethodBody = '$this->assertHasManyRelation(' . $className . '::class, \'' . $methodName . '\', ' . $relationMethod . ');';

        } elseif (strpos($methodBody, 'belongsToMany')) {

            preg_match('|\((.+?)\)|is', $methodBody, $relationMethod);
            $relationMethod = substr(array_first(explode(',', array_first($relationMethod))), 1);
            $relationMethod = rtrim(trim($relationMethod), ')');
            $newMethodBody = '$this->assertBelongsToManyRelation(' . $className . '::class, \'' . $methodName . '\', ' . $relationMethod . ');';

        } elseif (strpos($methodBody, 'belongsTo')) {

            preg_match('|\((.+?)\)|is', $methodBody, $relationMethod);
            $relationMethod = substr(array_first(explode(',', array_first($relationMethod))), 1);
            $relationMethod = rtrim(trim($relationMethod), ')');
            $newMethodBody = '$this->assertBelongsToRelation(' . $className . '::class, \'' . $methodName . '\', ' . $relationMethod . ');';

        }

        return $relationMethod;
    }

    /**
     * @param $classHeader
     * @param $className
     * @return bool
     */
    private function checkPathInHeaderForClass(&$classHeader, $className)
    {
        $className = array_first(explode('::', $className));
        $namespacePosition = strpos($classHeader, ';');
        $newClassHeader = substr($classHeader, $namespacePosition);

        if(!strpos($newClassHeader, '\\' . $className . ';')) {
            $path = $this->getPathClass(app_path() . DIRECTORY_SEPARATOR . 'Models', $className . '.php');

            if($path) {
                $path = 'use ' . config('app.name') . substr($path, 5, -4) . ';';
                $path = str_replace('/', '\\', $path);
                $classHeader = str_replace_first('use', $path . PHP_EOL . 'use', $classHeader);
                return true;
            }

            return false;
        }

        return false;
    }

    /**
     * @param $directory
     * @param null $filename
     * @return null|string
     */
    private function getPathClass($directory, $filename = null)
    {
        if (!is_dir($directory)) return null;

        if (!$filename) return $directory;

        $openDirectory = opendir($directory);

        while (($file = readdir($openDirectory)) !== false) {

            if ($file == "." || $file == "..") continue;

            if (is_file($directory . '/' . $filename )) {
                return $directory . '/' . $filename;
            }

            if(is_dir($directory . '/' . $file )) {
                $recursion = $this->getPathClass( $directory . '/' . $file , $filename );
            }

            if(isset($recursion)) return $recursion;

        }

        closedir($openDirectory);
    }

    /**
     * @param $paths
     * @return array
     */
    public function getModelFiles($paths)
    {
        if (!is_array($paths)) {
            $paths = [$paths];
        }

        $files = [];
        foreach ($paths as $path) {
            foreach ($this->files->allFiles($path) as $file) {
                $files[] = $file->getPathName();
            }
        }
        return $files;
    }

    /**
     * @return array
     */
    protected function getModelPaths($methodName = 'getModelPath')
    {
        return array_merge(
            [$this->{ $methodName }()], []
        );
    }

    /**
     * @return string
     */
    protected function getModelPath()
    {
        return app_path() . DIRECTORY_SEPARATOR . 'Models';
    }

    /**
     * @return string
     */
    protected function getTestModelPath()
    {
        return base_path('tests') . DIRECTORY_SEPARATOR . 'Models';
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__ . '/stubs/test.stub';
    }

    /**
     * Get the destination class path.
     *
     * @param  string $name
     * @return string
     */
    protected function getPath($name)
    {
        $name = str_replace($this->laravel->getNamespace(), '', $name);

        return $this->laravel['path.base'] . '/tests/' . str_replace('\\', '/', $name) . '.php';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace;
    }

}