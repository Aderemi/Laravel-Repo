<?php

namespace LaraRepo\Repositories\Console\Commands\Creators;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Config;
use Doctrine\Common\Inflector\Inflector;

/**
 * Class FilterCreator
 *
 * @package LaraRepo\Repositories\Console\Commands\Creators
 */
class FilterCreator extends BaseCreator {

    /**
     * @var
     */
    protected $filter;

    /**
     * @return mixed
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * @param mixed $filter
     */
    public function setFilter($filter)
    {
        $this->filter = $filter;
    }

    /**
     * Create the filter.
     *
     * @param $filter
     * @param $model
     *
     * @return int
     */
    public function create($filter, $model)
    {
        // Set the filter.
        $this->setFilter($filter);

        // Set the model.
        $this->setModel($model);

        // Create the folder directory.
        $this->createDirectory();

        // Return result.
        return $this->createClass();
    }

    /**
     * Get the filter directory.
     *
     * @return string
     */
    public function getDirectory()
    {
        // Model
        $model = $this->getModel();

        // Get the filter path from the config file.
        $directory = Config::get('repositories.filter_path');

        // Check if the model is not null.
        if(isset($model) && !empty($model))
        {
            // Update the directory with the model name.
            $directory .= DIRECTORY_SEPARATOR . $this->pluralizeModel();
        }

        // Return the directory.
        return $directory;
    }


    /**
     * Get the populate data.
     *
     * @return array
     */
    protected function getPopulateData()
    {
        // Filter.
        $filter =  $this->getFilter();

        // Model
        $model    = $this->pluralizeModel();

        // Filter namespace.
        $filter_namespace = Config::get('repositories.filter_namespace');

        // Filter class.
        $filter_class     = $filter;

        // Check if the model isset and not empty.
        if(isset($model) && !empty($model))
        {
            // Update the filter namespace with the model folder.
            $filter_namespace .= '\\' . $model;
        }

        // Populate data.
        $populate_data = [
            'filter_namespace' => $filter_namespace,
            'filter_class'     => $filter_class
        ];

        // Return the populate data.
        return $populate_data;
    }

    /**
     * Get the path.
     *
     * @return string
     */
    protected function getPath()
    {
        // Path
        $path = $this->getDirectory() . DIRECTORY_SEPARATOR . $this->getFilter() . '.php';

        // Return the path.
        return $path;
    }

    /**
     * Get the stub.
     *
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function getStub()
    {
        // Stub
        $stub = $this->files->get($this->getStubPath() . "filter.stub");

        // Return the stub.
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
        foreach ($populate_data as $search => $replace)
        {
            // Populate the stub.
            $stub = str_replace($search, $replace, $stub);
        }

        // Return the stub.
        return $stub;
    }
}
