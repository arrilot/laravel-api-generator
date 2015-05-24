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
     * @param  \Illuminate\Filesystem\Filesystem  $files
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

        //create controller

        //create transformer

        //create routes
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
            ->setTransformerData()
            ->setEndpoint();

        var_dump($this->stubVariables);
    }

    /**
     * Set the model name and namespace.
     *
     * @return $this
     */
    protected function setModelData($name)
    {
        if (str_contains($name, '/'))
        {
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
     * @return string
     */
    protected function setControllerData()
    {
//        $this->stubVariables['transformerName'] = $this->stubVariables['modelName'].'Transformer';
//        $this->stubVariables['transformerNamespace'] = $this->appNamespace.$this->convertSlashes(config('laravel-api-generator.transformers_path'));
//        $this->stubVariables['transformerFullName'] = trim($this->stubVariables['transformerNamespace'].'\\'.$this->stubVariables['transformerName'], '\\');

        return $this;
    }

    /**
     * Set the transformer name and namespace.
     *
     * @return string
     */
    protected function setTransformerData()
    {
        $this->stubVariables['transformerName'] = $this->stubVariables['modelName'].'Transformer';
        $this->stubVariables['transformerNamespace'] = $this->appNamespace.$this->convertSlashes(config('laravel-api-generator.transformers_path'));
        $this->stubVariables['transformerFullName'] = trim($this->stubVariables['transformerNamespace'].'\\'.$this->stubVariables['transformerName'], '\\');

        return $this;
    }

    /**
     * Set endpoint for a given model.
     * "Profile\Payer" -> "profile_payers"
     *
     * @return string
     */
    protected function setEndpoint()
    {
        $endpoint = str_replace('\\', '', $this->stubVariables['modelFullNameWithoutRoot']);
        $endpoint = snake_case($endpoint);

        $this->stubVariables['endpoint'] = str_plural($endpoint);

        return $this;
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