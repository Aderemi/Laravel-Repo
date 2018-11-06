<?php
namespace LaraRepo\Repositories\Criteria;

use LaraRepo\Repositories\Contracts\RepositoryInterface as Repository;
/**
 * Abstract Class Filter is the superclass for models that should be searchable
 *
 * @package LaraRepo\Repositories\Criteria\
 * @author Aderemi Dayo<dayo.aderemi@supermartng.com>
 */

abstract class Criteria extends BaseCriteria
{
    protected $fields;
    
    public function __construct(array $data = [], $fields = [])
    {
        parent::__construct($data);
        $this->fields = $fields;
    }
}