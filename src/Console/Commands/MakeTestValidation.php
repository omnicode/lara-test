<?php

namespace LaraTest\Console\Commands;

use function ICanBoogie\upcase;

class MakeTestValidation extends TestCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:test-validation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new test class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Test Validation';

    protected function getTestClassContent($file) {

    }

    public function fire()
    {
        $this->fixTestCaseClass();
        if ($this->getNameInput() === '_all') {
            $validatorFiles = $this->getValidatorFiles($this->getValidationPaths());
            foreach ($validatorFiles as $validatorFile) {
                $path = str_replace(app_path(), base_path('tests'), $validatorFile);
                $path = str_replace('.php', 'Test.php', $path);
                $this->makeDirectory($path);

                $this->files->put($path, $this->buildClassForFile($validatorFile), FILE_APPEND);
                $this->info($this->type . ' created successfully.');
            }
        } else {
            return parent::fire();
        }
    }

    protected function fixTestCaseClass()
    {
        $path = base_path('tests/Validators/TestCaseValidator.php');

        if (!$this->files->exists($path)) {
            $this->makeDirectory($path);
            $this->files->put($path, $this->files->get(__DIR__ . '/stubs/test-validator.stub').PHP_EOL, FILE_APPEND);
            $this->info('TestCaseVAlidator created successfully.');
        }
    }

    public function buildClassForFile($file)
    {
        $stub = $this->files->get($file);
        $fullClassName = $this->getClassFullName($file) ;
        $className = last(explode('\\', $fullClassName));
        $stub = str_replace_first($this->laravel->getNamespace(), "Tests\\", $stub);
        $stub = str_replace_first('LaraValidation\LaraValidator;', $fullClassName
            . ';use Tests\Validators\TestCaseValidator;', $stub);
        $stub = str_replace_first('LaraValidator', 'TestCaseValidator', $stub);
        $stub = str_replace(' ' . $className, ' ' . $className . 'Test', $stub);

        $stub = $this->processMethods($stub, $className);
        return $stub;
    }

    private function processMethods($text, $className)
    {
        $validatorObjStr = '$' . lcfirst($className) . ' = new ' . $className . '($this->validator);'
            . '$validator = ' . '$' . lcfirst($className) .'->';
        $arr = explode(' function ', $text);
        $max = count($arr) - 1;
        for( $i = 0; $i < $max; $i++) {
            $methodType = last(explode(' ', $arr[$i]));
            $methodName = array_first(explode('(', $arr[$i + 1]));

            if ($methodType != 'public') {
                $this->info($methodType . ' ' . $methodName);
            } elseif(str_contains($methodName, 'validation')) {
                $arr[$i + 1] = str_replace($methodName . '(', 'test' . ucfirst($methodName). 'Method(', $arr[$i + 1]);
                $arr[$i + 1] = str_replace_first('{' ,'{ ' . $validatorObjStr . $methodName . '();' , $arr[$i + 1]);
                $arr[$i + 1] = str_replace('return $this->validator' ,'$this->assertEquals($this->validator, $validator)', $arr[$i + 1]);
                $text = implode( ' function ', $arr);
            }
        }
        return $text;
    }

    public function getValidatorFiles($paths)
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

    protected function getValidationPaths()
    {
        return array_merge(
            [$this->getValidationPath()], []
        );
    }

    protected function getValidationPath()
    {
        return app_path() . DIRECTORY_SEPARATOR . 'Validators';
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