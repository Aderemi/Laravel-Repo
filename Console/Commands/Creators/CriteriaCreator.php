<?php

namespace LaraRepo\Repositories\Console\Commands\Creators;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Config;
use Doctrine\Common\Inflector\Inflector;

/**
 * Class CriteriaCreator
 *
 * @package LaraRepo\Repositories\Console\Commands\Creators
 */
class CriteriaCreator extends BaseCreator {

    /**
     * @var
     */
    protected $criteria;

    /**
     * @return mixed
     */
    public function getCriteria()
    {
        return $this->criteria;
    }

    /**
     * @param mixed $criteria
     */
    public function setCriteria($criteria)
    {
        $this->criteria = $criteria;
    }

    /**
     * Create the criteria.
     *
     * @param $criteria
     * @param $model
     *
     * @return int
     */
    public function create($criteria, $model)
    {
        // Set the criteria.
        $this->setCriteria($criteria);

        // Set the model.
        $this->setModel($model);

        // Create the folder directory.
        $this->createDirectory();

        // Return result.
        return $this->createClass();
    }

    /**
     * Get the criteria directory.
     *
     * @return string
     */
    public function getDirectory()
    {
        // Model
        $model = $this->getModel();

        // Get the criteria path from the config file.
        $directory = Config::get('repositories.criteria_path');

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
        // Criteria.
        $criteria =  $this->getCriteria();

        // Model
        $model    = $this->pluralizeModel();

        // Criteria namespace.
        $criteria_namespace = Config::get('repositories.criteria_namespace');

        // Criteria class.
        $criteria_class     = $criteria;

        // Check if the model isset and not empty.
        if(isset($model) && !empty($model))
        {
            // Update the criteria namespace with the model folder.
            $criteria_namespace .= '\\' . $model;
        }

        // Populate data.
        $populate_data = [
            'criteria_namespace' => $criteria_namespace,
            'criteria_class'     => $criteria_class
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
        $path = $this->getDirectory() . DIRECTORY_SEPARATOR . $this->getCriteria() . '.php';

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
        $stub = $this->files->get($this->getStubPath() . "criteria.stub");

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
