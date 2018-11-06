<?php

namespace LaraRepo\Repositories\Console\Commands\Creators;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Config;
use Doctrine\Common\Inflector\Inflector;

/**
 * Class RepositoryCreator
 *
 * @package LaraRepo\Repositories\Console\Commands\Creators
 */
class RepositoryCreator extends BaseCreator {

    /**
     * @var
     */
    protected $repository;

    /**
     * @return mixed
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * @param mixed $repository
     */
    public function setRepository($repository)
    {
        $this->repository = $repository;
    }

    /**
     * Create the repository.
     *
     * @param $repository
     * @param $model
     * @return int
     */
    public function create($repository, $model)
    {
        // Set the repository.
        $this->setRepository($repository);

        // Set the model.
        $this->setModel($model);

        // Create the directory.
        $this->createDirectory();

        // Return result.
        return $this->createClass();
    }

    /**
     * Get the repository directory.
     *
     * @return mixed
     */
    protected function getDirectory()
    {
        // Get the directory from the config file.
        $directory = Config::get('repositories.repository_path');

        // Return the directory.
        return $directory;
    }

    /**
     * Get the repository name.
     *
     * @return mixed|string
     */
    protected function getRepositoryName()
    {
        // Get the repository.
        $repository_name = $this->getRepository();

        // Check if the repository ends with 'Repository'.
        if(!strpos($repository_name, 'Repository') !== false)
        {
            // Append 'Repository' if not.
            $repository_name .= 'Repository';
        }

        // Return repository name.
        return $repository_name;
    }

    /**
     * Get the model name.
     *
     * @return string
     */
    protected function getModelName()
    {
        // Set model.
        $model      = $this->getModel();

        // Check if the model isset.
        if(isset($model) && !empty($model))
        {
            // Set the model name from the model option.
            $model_name = $model;
        }

        else
        {
            // Set the model name by the stripped repository name.
            $model_name = Inflector::singularize($this->stripRepositoryName());
        }

        // Return the model name.
        return $model_name;
    }

    /**
     * Get the stripped repository name.
     *
     * @return string
     */
    protected function stripRepositoryName()
    {
        // Lowercase the repository.
        $repository = strtolower($this->getRepository());

        // Remove repository from the string.
        $stripped   = str_replace("repository", "", $repository);

        // Uppercase repository name.
        $result = ucfirst($stripped);

        // Return the result.
        return $result;
    }

    /**
     * Get the path.
     *
     * @return string
     */
    protected function getPath()
    {
        // Path.
        $path = $this->getDirectory() . DIRECTORY_SEPARATOR . $this->getRepositoryName() . '.php';

        // return path.
        return $path;
    }


    /**
     * Get the populate data.
     *
     * @return array
     */
    protected function getPopulateData()
    {
        // Repository namespace.
        $repository_namespace = Config::get('repositories.repository_namespace');

        // Repository class.
        $repository_class     = $this->getRepositoryName();

        // Model path.
        $model_path           = Config::get('repositories.model_namespace');

        // Model name.
        $model_name           = $this->getModelName();

        // Populate data.
        $populate_data = [
            'repository_namespace' => $repository_namespace,
            'repository_class'     => $repository_class,
            'model_path'           => $model_path,
            'model_name'           => $model_name
        ];

        // Return populate data.
        return $populate_data;
    }

    /**
     * Get the stub.
     *
     * @return string
     */
    protected function getStub()
    {
        // Stub
        $stub = $this->files->get($this->getStubPath() . "repository.stub");

        // Return stub.
        return $stub;
    }

    /**
     * Populate the stub.
     *
     * @return mixed
     */
    protected function populateStub()
    {
        // Populate data
        $populate_data = $this->getPopulateData();

        // Stub
        $stub = $this->getStub();

        // Loop through the populate data.
        foreach ($populate_data as $key => $value)
        {
            // Populate the stub.
            $stub = str_replace($key, $value, $stub);
        }

        // Return the stub.
        return $stub;
    }

    protected function createClass()
    {
        // Result.
        $result = $this->files->put($this->getPath(), $this->populateStub());

        // Return the result.
        return $result;
    }
}
