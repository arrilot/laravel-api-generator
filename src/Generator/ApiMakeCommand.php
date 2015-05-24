<?php

namespace Arrilot\Api\Generator;

use Illuminate\Console\AppNamespaceDetectorTrait;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputArgument;

class ApiMakeCommand extends Command
{
    use AppNamespaceDetectorTrait;

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:api';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create api controller, transformer and api routes for a given model (arrilot/laravel-api-generator)';

    /**
     * The application namespace.
     *
     * @var string
     */
    protected $appNamespace;

    /**
     * The array of variables available in stubs.
     *
     * @var array
     */
    protected $stubVariables = [];

    protected $modelsBaseNamespace;

    /**
     * Create a new controller creator command instance.
     *
     * @param \Illuminate\Filesystem\Filesystem $files
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $this->prepareVariablesForStubs($this->argument('name'));

        $this->createController();

        $this->createTransformer();

        $this->addRoutes();
    }

    /**
     * Prepare names, paths and namespaces for stubs.
     *
     * @param $name
     */
    protected function prepareVariablesForStubs($name)
    {
        $this->appNamespace = $this->getAppNamespace();

        $baseDir = config('laravel-api-generator.models_base_dir');

        $this->modelsBaseNamespace = $baseDir ? trim($baseDir, '\\').'\\' : '';

        $this->setModelData($name)
            ->setControllerData()
            ->setRouteData()
            ->setTransformerData();
    }

    /**
     * Set the model name and namespace.
     *
     * @return $this
     */
    protected function setModelData($name)
    {
        if (str_contains($name, '/')) {
            $name = $this->convertSlashes($name);
        }

        $name = trim($name, '\\');

        $this->stubVariables['modelFullNameWithoutRoot'] = $name;
        $this->stubVariables['modelFullName'] = $this->appNamespace.$this->modelsBaseNamespace.$name;

        $exploded = explode('\\', $this->stubVariables['modelFullName']);

        $this->stubVariables['modelName'] = array_pop($exploded);
        $this->stubVariables['modelNamespace'] = implode('\\', $exploded);

        return $this;
    }

    /**
     * Set the controller name and namespace.
     *
     * @return $this
     */
    protected function setControllerData()
    {
        $this->stubVariables['controllerName'] = $this->stubVariables['modelName'].'Controller';
        $this->stubVariables['controllerName'] = $this->stubVariables['modelName'].'Controller';
        $this->stubVariables['controllerNamespace'] = $this->appNamespace.$this->convertSlashes(config('laravel-api-generator.controllers_path'));
        $this->stubVariables['controllerFullName'] = trim($this->stubVariables['controllerNamespace'].'\\'.$this->stubVariables['controllerName'], '\\');

        return $this;
    }

    /**
     * Set route data for a given model.
     * "Profile\Payer" -> "profile_payers".
     *
     * @return $this
     */
    protected function setRouteData()
    {
        $name = str_replace('\\', '', $this->stubVariables['modelFullNameWithoutRoot']);
        $name = snake_case($name);

        $this->stubVariables['routeName'] = str_plural($name);

        return $this;
    }

    /**
     * Set the transformer name and namespace.
     *
     * @return $this
     */
    protected function setTransformerData()
    {
        $this->stubVariables['transformerName'] = $this->stubVariables['modelName'].'Transformer';
        $this->stubVariables['transformerNamespace'] = $this->appNamespace.$this->convertSlashes(config('laravel-api-generator.transformers_path'));
        $this->stubVariables['transformerFullName'] = trim($this->stubVariables['transformerNamespace'].'\\'.$this->stubVariables['transformerName'], '\\');

        return $this;
    }

    /**
     *  Create controller class file from a stub.
     */
    protected function createController()
    {
        $this->createClass('controller');
    }

    /**
     *  Create controller class file from a stub.
     */
    protected function createTransformer()
    {
        $this->createClass('transformer');
    }

    /**
     *  Add routes to routes file.
     */
    protected function addRoutes()
    {
        $stub = $this->constructStub(base_path(config('laravel-api-generator.route_stub')));

        $routesFile = app_path(config('laravel-api-generator.routes_file'));

        // read file
        $lines = file($routesFile);
        $lastLine = $lines[count($lines) - 1];

        // modify file
        if (strcmp($lastLine, '});') === 0) {
            $lines[count($lines) - 1] = '    '.$stub;
            $lines[] = "\r\n});";
        } else {
            $lines[] = "\r\n".$stub;
        }

        // save file
        $fp = fopen($routesFile, 'w');
        fwrite($fp, implode('', $lines));
        fclose($fp);

        $this->info('Routes added successfully.');
    }

    /**
     * Create class with a given type.
     *
     * @param $type
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function createClass($type)
    {
        $path = $this->getPath($this->stubVariables[$type.'FullName']);
        if ($this->files->exists($path)) {
            $this->error(ucfirst($type).' already exists!');

            return;
        }

        $this->makeDirectoryIfNeeded($path);

        $this->files->put($path, $this->constructStub(base_path(config('laravel-api-generator.'.$type.'_stub'))));

        $this->info(ucfirst($type).' created successfully.');
    }

    /**
     * Get the destination file path.
     *
     * @param string $name
     *
     * @return string
     */
    protected function getPath($name)
    {
        $name = str_replace($this->appNamespace, '', $name);

        return $this->laravel['path'].'/'.str_replace('\\', '/', $name).'.php';
    }

    /**
     * Build the directory for the class if needed.
     *
     * @param string $path
     *
     * @return string
     */
    protected function makeDirectoryIfNeeded($path)
    {
        if (!$this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }
    }

    /**
     * Get stub content and replace all stub placeholders
     * with data from $this->stubData.
     *
     * @param string $path
     *
     * @return string
     */
    protected function constructStub($path)
    {
        $stub = $this->files->get($path);

        foreach ($this->stubVariables as $var => $value) {
            $stub = str_replace('{{'.$var.'}}', $value, $stub);
        }

        return $stub;
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the model'],
        ];
    }

    /**
     * Convert "/" to "\".
     *
     * @param $string
     *
     * @return string
     */
    protected function convertSlashes($string)
    {
        return str_replace('/', '\\', $string);
    }
}
