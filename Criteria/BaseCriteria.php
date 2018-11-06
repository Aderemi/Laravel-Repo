<?php namespace LaraRepo\Repositories\Criteria;

use Illuminate\Http\Request;

use LaraRepo\Repositories\Contracts\RepositoryInterface as Repository;

/**
 * Abstract Class Filter is the superclass for models that should be searchable
 *
 * @package LaraRepo\Repositories\Criteria\
 * @authors Oyelakin Gbadebo<oyelaking@supermartng.com>
 *          Aderemi Dayo<akinsnazri@gmail.com>
 */

abstract class BaseCriteria {
    //the types of fields
    const FIELD_NUMERIC = "numeric";
    const FIELD_STRING = "string";
    const FIELD_DATE = "date";

    //Or search field
    const OrWhere = 'or';

    //types of search to perform on string fields
    const STR_SEARCH_STARTS_WITH = 3;
    const STR_SEARCH_ENDS_WITH = 5;
    const STR_SEARCH_CONTAINS = 7;
    const STR_SEARCH_EQUALS = 83;

    //types of search to perform on the number fields
    const NUM_SEARCH_LESS_THAN = 11;
    const NUM_SEARCH_LESS_THAN_OR_EQUAL = 13;
    const NUM_SEARCH_GREATER_THAN = 17;
    const NUM_SEARCH_GREATER_THAN_OR_EQUAL = 19;
    const NUM_SEARCH_BETWEEN_EXCLUSIVELY = 23;
    const NUM_SEARCH_BETWEEN_RIGHT_EXCLUSIVELY = 29;
    const NUM_SEARCH_BETWEEN_LEFT_EXCLUSIVELY = 31;
    const NUM_SEARCH_EQUALS = 37;
    const NUM_SEARCH_BETWEEN = 41;

    //types of search to perform on the date fields
    const DATE_SEARCH_BEFORE = 43;
    const DATE_SEARCH_BEFORE_EXCLUSIVELY = 47;
    const DATE_SEARCH_AFTER = 53;
    const DATE_SEARCH_AFTER_EXCLUSIVELY = 59;
    const DATE_SEARCH_BETWEEN_EXCLUSIVELY = 67;
    const DATE_SEARCH_BETWEEN_LEFT_EXCLUSIVELY = 107;
    const DATE_SEARCH_BETWEEN_RIGHT_EXCLUSIVELY = 109;
    const DATE_SEARCH_BETWEEN = 79;
    const WHERE_RELATION = 89;
    const DATE_SEARCH_ON = 97;
    const NUM_SEARCH_NOT_EQUALS = 101;
    const SEARCH_IN = 103;
    const SEARCH_BOOLEAN = 107;
    const SEARCH_NULL = 109;
    /**
     * @TODO: Add this later
     */
    //type of select
    const SELECT_FIELD = 3;
    const SELECT_SUM = 5;
    const SELECT_COUNT = 7;

    /**
     * Mapping of search type to object methods
     *
     * @var array
     */
    protected static $searchTypeToMethodMap = [
        self::STR_SEARCH_STARTS_WITH => "stringStartsWith",
        self::STR_SEARCH_CONTAINS => "stringContains",
        self::STR_SEARCH_ENDS_WITH => "stringEndsWith",
        self::STR_SEARCH_EQUALS => "stringEquals",
        self::NUM_SEARCH_LESS_THAN => "numLessThan",
        self::NUM_SEARCH_LESS_THAN_OR_EQUAL => "numLessThanOrEquals",
        self::NUM_SEARCH_BETWEEN_LEFT_EXCLUSIVELY => "numBetweenLeftExclusive",
        self::NUM_SEARCH_BETWEEN_RIGHT_EXCLUSIVELY => "numBetweenRightExclusive",
        self::NUM_SEARCH_EQUALS => "numEquals",
        self::NUM_SEARCH_NOT_EQUALS => "numNotEquals",
        self::NUM_SEARCH_GREATER_THAN => "numGreaterThan",
        self::NUM_SEARCH_GREATER_THAN_OR_EQUAL => "numGreaterThanOrEquals",
        self::NUM_SEARCH_BETWEEN => "numBetween",
        self::NUM_SEARCH_BETWEEN_EXCLUSIVELY => "numBetweenExclusive",
        self::DATE_SEARCH_AFTER => "dateAfter",
        self::DATE_SEARCH_ON => "dateOn",
        self::DATE_SEARCH_AFTER_EXCLUSIVELY => "dateAfterExclusive",
        self::DATE_SEARCH_BEFORE => "dateBefore",
        self::DATE_SEARCH_BEFORE_EXCLUSIVELY => "dateBeforeExclusive",
        self::DATE_SEARCH_BETWEEN => "dateBetween",
        self::DATE_SEARCH_BETWEEN_EXCLUSIVELY => "dateBetweenExclusive",
        self::DATE_SEARCH_BETWEEN_LEFT_EXCLUSIVELY => "dateBetweenLeftExclusive",
        self::DATE_SEARCH_BETWEEN_RIGHT_EXCLUSIVELY => "dateBetweenRightExclusive",
        self::WHERE_RELATION => "whereRelation",
        self::SEARCH_IN => "whereIn",
        self::SEARCH_BOOLEAN => "searchBool",
        self::SEARCH_NULL => "searchNull"
    ];

    /**
     * Model.
     *
     * @var Model
     */
    protected $model;

    /**
     * Model.
     *
     * @var Repository
     */
    protected $repository;

    /**
     * @var array
     */
    protected $data;

    /**
     * @var array
     */
    protected $valueMap;

    /**
     * @var array
     */
    protected $defaultData;

    /**
     * @var boolean
     */
    protected $recur = false;

    /**
     * The fields to return
     *
     * @var array
     */
    protected $basicFields;

    /**
     * The fields to return
     *
     * @var array
     */
    protected $with;

    /**
     * Check if current model is mongo
     *
     * @var array
     */
    protected $isMongo = false;
    /**
     * @return string
     */
    public function __construct($data = [])
    {
        $this->fill($data);
    }

    /**
     * @return $this
     * @throws \Exception
     */
    protected function buildQuery()
    {
        if($this->getCompulsoryFields()){
            foreach ($this->getCompulsoryFields() as $compulsoryField){
                if(is_null($this->data($compulsoryField)) || (empty($this->data($compulsoryField)) && $this->data($compulsoryField) != 0))
                    throw new \Exception("$compulsoryField is marked compulsory but not present in the filtering parameters");
            }
        }
        $this->beforeBuildingQuery();
        $fieldConfigs = $this->getSearchableFieldsConfig();

        foreach ($fieldConfigs as $field => $options)
        {
            $config = $this->processTags($field);
            $dotPosition = strpos($field, ".");
            $index = $dotPosition !== false ? str_replace('.', '_', $field) : $field;

            if (is_null($this->data($index)) || (empty($this->data($index)) && $this->data($index) != 0))
            {
                continue;
            }

            $methodName = self::$searchTypeToMethodMap[$options['searchType']] ?? "";

            if (empty($methodName))
            {
                throw new \Exception("Could not find method for search type '{$options['searchType']}'");
            }

            if($relation = substr($index, 0, $dotPosition ?: 0) && !$this->isMongo);
            else $relation = "";
            $rel = substr($index, 0, $dotPosition ?: 0);
            $relation = !$this->isMongo && strlen($rel) > 0 ? $rel : "";
            $this->processCall($methodName, $field, $index, $relation, isset($options['orWhere']) ? "or" : "and");
        }
        return $this;
    }

    public function getCompulsoryFields()
    {
        return false;
    }

    public function getSearchableFields()
    {
        return array_keys($this->getSearchableFieldsConfig());
    }

    private function formatDateTime($date, $early = true)
    {
        if(preg_match('/.*\d+:\d+:\d+/', $date))
        {
            return $this->isMongo ? new \DateTime(date($date)) : date($date);
        }
        return $this->isMongo ? new \DateTime(date($date.($early ?  " 00:00:00" :" 23:59:59"))) : date($date.($early ?  " 00:00:00" :" 23:59:59"));
        //date($date.(preg_match('/.*\d+:\d+:\d+/', $date) ? '' : $early ? " 00:00:00" :" 23:59:59"));
    }

    private function processTags($field):array
    {
        //If the field contains - , then we are two fields for between
        $between_pos = strpos($field, ' - ');
        $between = $between_pos !== false;
        //If the field contains ., then we are dealing with an object-property from a DB like mongo
        $index = strpos($field, '.') !== false ? str_replace('.', '_', $field) : $field;
        //If we are dealing with field of relational where
        $relationalWhere_pos = strpos($field, '@');
        $relationalWhere = $relationalWhere_pos !== false;

        $index = $relationalWhere ? str_replace('@', '_', $field) : $index;
        $return = ['relational_where' => $relationalWhere,  'between' => $between, 'index' => $index];
        $relation = $relationalWhere_pos ? substr($field, 0, $relationalWhere_pos) : "";
        $rem_chunck = str_replace($relation."@", "", $field);

        if($return['relational_where'] && $return['between'])
            $return['index'] = trim(substr($return['index'], strpos($return['index'], '(') , strpos($return['index'], ')') ), "()");//$config['between'] ? explode(" - ", $config['index']) : ["", ""];

        //Get the name of field from complex field like Order@status(from - to), Order@base_amount or status(from - to)
        $return['field'] = substr($rem_chunck, 0, (strrpos($rem_chunck, '(') ?: strlen($rem_chunck)));

        $return['relation'] = $relation;
        return $return;
    }

    public function processCall($methodName, $field, $index, $mySqlRelation = '', $orWhere = 'and', $disableRelation = false)
    {
        if($mySqlRelation === '' || $disableRelation){
            if($orWhere == 'and'){
                $this->$methodName($field, $this->data($index));
            }
            else{
                $this->$methodName($field, $this->data($index), $orWhere);
            }
            if($disableRelation) return $this;
        }else{
            if($mySqlRelation === '') throw new \Exception("No Relation Assigned");
            $this->whereRelation($methodName, $field, $index, $mySqlRelation, $orWhere);
        }
    }

    protected function data($index)
    {
        if(isset($this->data[$index]))
            return $this->data[$index];
        elseif(isset($this->valueMap[$index]) && isset($this->data[$this->valueMap[$index]]))
            return $this->data[$this->valueMap[$index]];
        elseif(isset($this->defaultData[$index]))
            return $this->defaultData[$index];
        else
            return null;
    }

    public function fill(array $searchData = [])
    {
        $this->data = $searchData;
        return $this;
    }

    protected function whereRelation($methodName, $field, $index, $mySqlRelation, $orWhere)
    {
        $this->model->whereHas($mySqlRelation, function ($query) use($methodName, $field, $index, $mySqlRelation, $orWhere) {
            $newThis = new static($this->data);
            $newThis->recur = true;

            $newThis->apply($query, $this->repository);
            $newThis->processCall($methodName, explode(".", $field)[1], $index, $mySqlRelation, $orWhere, true);
        });
    }

    protected function stringEndsWith(string $field, string $value, $boolean = 'and')
    {
        if(!$this->relation)
            $this->model->where($field, 'like', $value . '%', $boolean);
        return $this;
    }

    protected function stringEquals(string $field, string $value, $boolean = 'and')
    {
        $this->model->where($field, $value, $boolean);
        return $this;
    }

    protected function stringStartsWith(string $field, string $value, $boolean = 'and')
    {
        $this->model->where($field, 'like', "%$value", $boolean);
        return $this;
    }

    protected function stringContains(string $field, string $value, $boolean = 'and')
    {
        $splitValue = explode(" ", $value);
        foreach ($splitValue as $split) {
            $this->model->where($field, 'like', "%$split%", $boolean);
        }
        return $this;
    }

    protected function numLessThan(string $field, int $value, $boolean = 'and')
    {
        $this->model->where($field, '<', $value, $boolean);
        return $this;
    }

    protected function numLessThanOrEquals(string $field, int $value, $boolean = 'and')
    {
        $this->model->where($field, '<=', $value, $boolean);
        return $this;
    }

    protected function numBetween(string $field, array $value, $boolean = 'and')
    {
        $this->model->whereBetween($field, $value, $boolean);
        return $this;
    }

    protected function numBetweenExclusive(string $field, array $value, $boolean = 'and')
    {
        $this->numGreaterThan($field, $value[0]);
        $this->numLessThan($field, $value[1]);
        return $this;
    }

    protected function numBetweenLeftExclusive(string $field, array $value, $boolean = 'and')
    {
        $this->numGreaterThan($field, $value[0]);
        $this->numLessThanOrEquals($field, $value[1]);
        return $this;
    }

    protected function numBetweenRightExclusive(string $field, array $value, $boolean = 'and')
    {
        $this->numGreaterThanOrEquals($field, $value[0]);
        $this->numLessThan($field, $value[1]);
        return $this;
    }

    protected function numEquals(string $field, int $value, $boolean = 'and')
    {
        $this->model->where($field, '=', $value, $boolean);
        return $this;
    }

    protected function searchBool(string $field, $value, $boolean = 'and')
    {
        $val = false;
        if($value == 1 || $value === "true")
            $val = true;
        $this->model->where($field, $val, $boolean);
        return $this;
    }

    protected function searchNull(string $field, $value, $boolean = 'and')
    {
        if ((int)$value) {
            $this->model->whereNotNull($field);
        } else {
            $this->model->whereNull($field);
        }
        return $this;
    }

    protected function numNotEquals(string $field, int $value, $boolean = 'and')
    {
        $this->model->where($field, '!=', $value, $boolean);
        return $this;
    }

    protected function numGreaterThan(string $field, int $value, $boolean = 'and')
    {
        $this->model->where($field, '>', $value, $boolean);
        return $this;
    }

    protected function numGreaterThanOrEquals(string $field, int $value, $boolean = 'and')
    {
        $this->model->where($field, '>=', $value, $boolean);
        return $this;
    }

    protected function dateAfter(string $field, $value, $boolean = 'and')
    {
        $this->model->where($field, '>=', $this->formatDateTime($value), $boolean);
        return $this;
    }

    protected function dateOn(string $field, $value, $boolean = 'and')
    {
        $this->model->where($field, '>=', $this->formatDateTime($value), $boolean);
        $this->model->where($field, '<=', $this->formatDateTime($value, false), $boolean);
        return $this;
    }

    protected function dateAfterExclusive(string $field, $value, $boolean = 'and')
    {
        $this->model->where($field, '>', $this->formatDateTime($value, false), $boolean);
        return $this;
    }

    protected function dateBefore(string $field, string $value, $boolean = 'and')
    {
        $this->model->where($field, '<=', $this->formatDateTime($value, false), $boolean);
        return $this;
    }

    protected function dateBeforeExclusive(string $field, string $value, $boolean = 'and')
    {
        $this->model->where($field, '<', $this->formatDateTime($value, false), $boolean);
        return $this;
    }

    protected function dateBetween(string $field, array $value, $boolean = 'and')
    {
        $this->dateAfter($field, $value[0], $boolean);
        $this->dateBefore($field, empty($value[1]) ? date("Y-m-d H:i:s") : $value[1], $boolean);
        return $this;
    }

    protected function dateBetweenExlusive(string $field, array $value, $boolean = 'and')
    {
        $this->dateAfterExclusive($field, $value[0], $boolean);
        $this->dateBeforeExclusive($field, empty($value[1]) ? date("Y-m-d H:i:s") : $value[1], $boolean);
        return $this;
    }

    protected function dateBetweenRightExclusive(string $field, array $value, $boolean = 'and')
    {
        $this->dateAfterExclusive($field, $value[0], $boolean);
        $this->dateBefore($field, empty($value[1]) ? date("Y-m-d H:i:s") : $value[1], $boolean);
        return $this;
    }

    protected function dateBetweenLeftExclusive(string $field, array $value, $boolean = 'and')
    {
        $this->dateBeforeExclusive($field, empty($value[1]) ? date("Y-m-d H:i:s") : $value[1], $boolean);
        $this->dateAfter($field, $value[0] , $boolean);
        return $this;
    }

    protected function whereIn(string $field, array $inValues = [])
    {
        if(!is_array($inValues) || empty($inValues)){
            return;
        }
        $this->model->whereIn($field, $inValues);
        return $this;
    }

    /**
     * @param Model $model
     * @param Repository $repository
     * @return mixed
     * @throws \Exception
     */
    public function apply($model, Repository $repository)
    {
        $this->model = $model;
        $class = '\Jenssegers\Mongodb\Eloquent\Builder';
        $this->isMongo = $this->model instanceof $class;

        $this->repository = $repository;

        if(empty($this->data)){
            $this->fill($repository->request->all());
        }

        if(!$this->recur)$this->buildQuery();

        $return = new \StdClass();
        if(null !== $this->basicFields && is_array($this->basicFields)){
            $return->returnFields = $this->basicFields;
        }
        if(null !== $this->with && is_array($this->with)){
            $return->with = $this->with;
        }
        $return->model = $this->model;


        return $return;
    }

    protected abstract function getSearchableFieldsConfig(): array;
    
    /**
     * Method that is called before the query is built. Can be useful
     * for transforming the search data. Can also be used to add compulsory
     * queries to the query builder e.t.c
     *
     * @return mixed
     */
    public function beforeBuildingQuery(){
        
    }
}
