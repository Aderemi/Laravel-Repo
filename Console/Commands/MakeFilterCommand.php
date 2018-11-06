<?php

namespace LaraRepo\Repositories\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use LaraRepo\Repositories\Console\Commands\Creators\FilterCreator;

/**
 * Class MakeFilterCommand
 *
 * @package LaraRepo\Repositories\Console\Commands
 */
class MakeFilterCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'make:filter';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new filter class';

    /**
     * @var
     */
    protected $creator;

    /**
     * @var
     */
    protected $composer;

    /**
     * @param FilterCreator $creator
     */
    public function __construct(FilterCreator $creator)
    {
        parent::__construct();

        // Set the creator.
        $this->creator  = $creator;

        // Set the composer.
        $this->composer = app()['composer'];
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // Get the arguments.
        $arguments = $this->argument();

        // Get the options.
        $options   = $this->option();

        // Write filter.
        $this->writeFilter($arguments, $options);

        // Dump autoload.
        $this->composer->dumpAutoloads();
    }

    /**
     * Write the filter.
     *
     * @param $arguments
     * @param $options
     */
    public function writeFilter($arguments, $options)
    {
        // Set filter.
        $filter = $arguments['filter'];

        // Set model.
        $model    = $options['model'];

        // Create the filter.
        if($this->creator->create($filter, $model))
        {
            // Information message.
            $this->info("Succesfully created the filter class.");
        }
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['filter', InputArgument::REQUIRED, 'The filter name.']
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['model', null, InputOption::VALUE_OPTIONAL, 'The model name.', null],
        ];
    }
}
