<?php

namespace LaraTest\Console\Commands;

class MakeAppTest extends TestCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:test-app';

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
    protected $type = 'All App';


    protected function getTestClassContent($file) {

    }

    public function fire()
    {
        if ($this->getNameInput() === '_all') {
            $appFiles = $this->getAppFiles(app_path());
            foreach ($appFiles as $appFile) {
                $path = str_replace(app_path(), base_path('tests'), $appFile);
                $path = str_replace('.php', 'Test.php', $path);
                if (!$this->files->exists($path)) {
                    $this->makeDirectory($path);
                    $this->files->put($path, $this->buildClassForFile($appFile), FILE_APPEND);
                    $this->info($this->type . ' created successfully.');
                }
            }
        } else {
        }
    }

    public function buildClassForFile($file)
    {
        $fullClassName = $this->getClassFullName($file) ;
        $className = $this->getClassShortName($fullClassName);
        dd($fullClassName, $className);

        $stub = $this->files->get(__DIR__ . '/stubs/test.stub');
        $stub = str_replace_first('DummyNamespace', "Tests\\" . $fullClassName, $stub);
        $stub = str_replace('DummyClass', ' ' . $className . 'Test', $stub);

        return $stub;
    }

    public function getAppFiles($paths)
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