<?php

namespace LaraRepo\Repositories\Console\Commands\Creators;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Config;
use Doctrine\Common\Inflector\Inflector;

/**
 * Class BaseCreator
 *
 * @package LaraRepo\Repositories\Console\Commands\Creators
 */
class BaseCreator {

    /**
     * @var Filesystem
     */
    protected $files;

    /**
     * @var
     */
    protected $model;

    /**
     * @param Filesystem $files
     */
    public function __construct(Filesystem $files)
    {
        $this->files = $files;
    }

    /**
     * @return mixed
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @param mixed $model
     */
    public function setModel($model)
    {
        $this->model = $model;
    }

    /**
     * Create the filter directory.
     */
    public function createDirectory()
    {
        // Directory
        $directory = $this->getDirectory();

        // Check if the directory exists.
        if(!$this->files->isDirectory($directory))
        {
            // Create the directory if not.
            $this->files->makeDirectory($directory, 0755, true);
        }
    }

    /**
     * Get the stub path.
     *
     * @return string
     */
    protected function getStubPath()
    {
        // Path
        $path = __DIR__ . '/../../../../../../resources/stubs/';

        // Return the path.
        return $path;
    }

    /**
     * Create the repository class.
     *
     * @return int
     */
    protected function createClass()
    {
        // Result.
        $result = $this->files->put($this->getPath(), $this->populateStub());

        // Return the result.
        return $result;
    }

    /**
     * Pluralize the model.
     *
     * @return string
     */
    protected function pluralizeModel()
    {
        // Pluralized
        $pluralized = Inflector::pluralize($this->getModel());

        // Uppercase first character the modelname
        $model_name = ucfirst($pluralized);

        // Return the pluralized model.
        return $model_name;
    }

}
