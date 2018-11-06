<?php namespace LaraRepo\Repositories\Criteria;

use LaraRepo\Repositories\Contracts\RepositoryInterface as Repository;

/**
 * Abstract Class Filter is the superclass for models that should be searchable
 *
 * @package LaraRepo\Repository\Filter\Services
 * @author Aderemi Dayo<dayo.aderemi@supermartng.com>
 */

abstract class Filter extends BaseCriteria
{
    private $compulsoryFields = [];

    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }

    /**
     * @param $field
     * @throws \Exception
     */
    public function pushCompulsoryField($field)
    {
        if(is_array($field))
        {
            if(!array_diff($field, $this->getSearchableFields()))
                throw new \Exception("All compulsory fields must be present in the searchableFieldConfigs");
            $this->compulsoryFields = array_merge($this->getCompulsoryFields(), $field);
        }
        else{
            if(!in_array($field, $this->getSearchableFields()))
                throw new \Exception("Compulsory field be must among the searchableFieldConfigs");

            $this->compulsoryFields[] = $field;
        }
    }

    public function getCompulsoryFields()
    {
        return count($this->compulsoryFields) > 0 ? $this->compulsoryFields : false;
    }
}
