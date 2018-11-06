<?php

namespace LaraRepo\Repositories\Eloquent;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Container\Container as App;
use Illuminate\Http\Request;
use LaraRepo\Repositories\Contracts\CriteriaInterface;
use LaraRepo\Repositories\Contracts\RepositoryInterface;
use LaraRepo\Repositories\Exceptions\RepositoryException;
use LaraRepo\Repositories\Contracts\FilterInterface;
use LaraRepo\Repositories\Criteria\Filter;
use LaraRepo\Repositories\Criteria\Criteria;
use Validator;
/**
 * Class Repository
 * @package LaraRepo\Repositories\Eloquent
 * @author: Aderemi Dayo <dayo.aderemi@supermartng.com>
 */
abstract class Repository implements RepositoryInterface, FilterInterface, CriteriaInterface
{

    /**
     * @var App
     */
    private $app;

    /**
     * @var
     */
    protected $model;

    protected $newModel;

    /**
     * @var Collection
     */
    protected $filter;

    /**
     * @var bool
     */
    protected $skipFilter = false;

    /**
     * @var bool
     */
    protected $skipCriteria = false;

    /**
     * @var Collection
     */
    protected $criteria;

    /**
     * Validator
     * @var array
     */
    protected $validators;

    /**
     * @var integer
     */
    protected $limit;

    /**
     * @var integer
     */
    protected $page;

    /**
     * @var array
     */
    protected $sortData;

    /**
     * @var array
     */
    protected $with = [];

    /**
     * @var array
     */
    protected $withCount = [];

    /**
     * @var array
     */
    public $basicFields = ['manual_fields' => ['*'], 'return_fields' => ["*"], 'criteria_fields' => ["*"]];

    /**
     * The request object
     * @var Request
     */
    public $request;

    /**
     * Prevents from overwriting same filter in chain usage
     * @var bool
     */
    protected $preventFilterOverwriting = true;

    /**
     * Prevents from overwriting same filter in chain usage
     * @var bool
     */
    protected $preventCriteriaOverwriting = true;

    /**
     * @param App $app
     * @param Collection $collection
     * @throws \LaraRepo\Repositories\Exceptions\RepositoryException
     */
    public function __construct(App $app, Collection $collection, Request $request)
    {
        $this->app = $app;
        $this->filter = $collection;
        $this->criteria = $collection;
        $this->resetScope();
        $this->makeModel();
        $this->request = $request;

        $this->page = (int)$request->query->get('page', 1);
        $this->limit = (int)$request->query->get('limit', \App\Helpers\Constants::DEFAULT_LIMIT);
        $this->basicFields['return_fields'] = $request->query->get('return_fields', $this->basicFields['return_fields']);
        $this->sort($request->query->get('sort_data', $request->query->get('sort', ['created' => 'desc'])));
    }

    /**
     * Specify Model class name
     *
     * @return mixed
     */
    public abstract function model();

    public function validator($method)
    {
        if(method_exists($this, "rules")){
            $rules = $this->rules();
            if($method == 'delete' && !isset($rules['delete'])) {
                return [];
            }
            return $rules[$method] ?? $rules["rules"];
        }
        return [];
    }

    private function processColumn($columns)
    {
        if($columns == '*')
        {
            if($this->getReturnFields()[0] === '*'){
                return ['*'];
            }
            return $this->processDotRelation($this->getReturnFields());
        }else{
            return $this->processDotRelation($columns);
        }
    }

    private function processDotRelation($columns)
    {
        $related_table_array = [];
        foreach($columns as $index => $value){

            if(strpos($value, ".")){
                $related_data = explode(".", $value);

                if($related_data[0] != $this->newModel->getTable()){
                    unset($columns[$index]);
                    if(in_array($related_data[0], $this->with)) continue;

                    if(isset($related_table_array[$related_data[0]]))
                        array_push($related_table_array[$related_data[0]], $related_data[1]);
                    else
                        $related_table_array[$related_data[0]] = array($related_data[1]);
                }
            }
        }

        foreach ($related_table_array as $index => $value){
            $value[] = 'id';
            $this->with(function($query) use($value){
                $query->select($value);
            });
        }
        return $columns;
    }

    /**
     * @param array $columns
     * @return mixed
     */
    public function all($columns = '*') {
        $this->applyFilter();
        $this->applyCriteria();
        $this->newQuery()->eagerLoadRelations()->eagerLoadRelationsCount();

        return $this->model->get($this->processColumn($columns));
    }
    /**
     * @param array $relations
     * @return $this
     */
    public function with($relations) {
        if (is_string($relations)) $relations = func_get_args();
        $this->with = array_unique(array_merge($this->with, $relations));

        return $this;
    }

    public function newQuery() {
        $this->model = $this->model->newQuery();
        return $this;
    }

    /**
     * @return $this
     */
    protected function eagerLoadRelations() {
        if(isset($this->with) && !empty($this->with)) {
            foreach ($this->with as $relation) {
                $this->model->with($relation);
            }
        }

        return $this;
    }

    /**
     * @param  string $value
     * @param  string $key
     * @return array
     */
    public function lists($value, $key = null)
    {
        $this->applyFilter();
        $this->applyCriteria();
        $lists = $this->model->lists($value, $key);
        if (is_array($lists)) {
            return $lists;
        }
        return $lists->all();
    }

    /**
     * @param array $sortData
     * @return Repository
     */
    public function sort(array $sortData = []): Repository
    {
        $sortData = $sortData ?? $this->sortData;
        foreach ($sortData as $fieldName => $direction) {
            $this->model->orderBy($fieldName, $direction);
        }
        return $this;
    }

    /**
     * @param string $columns
     * @return mixed
     */
    public function paginate($columns = '*')
    {
        $this->applyFilter();
        $this->applyCriteria();
        $this->newQuery()->eagerLoadRelations()->eagerLoadRelationsCount();

        return $this->model->paginate($this->limit, $this->processColumn($columns))->toArray();
    }

    public function groupBy($fields = [])
    {
        if (!empty($fields)) {
            $this->model->groupBy($fields);
        }
        return $this;
    }
    
    /**
     * @param array $data
     * @return mixed
     */
    public function create(array $data = [])
    {
        $data = $data ?? $this->request->post();
        return $this->model->create($data);
    }

    /**
     * save a model without massive assignment
     *
     * @param array $data
     * @return bool
     */
    public function saveModel(array $data)
    {
        foreach ($data as $k => $v) {
            $this->model->$k = $v;
        }
        return $this->model->save();
    }

    /**
     * @param array $data
     * @param $id
     * @param string $attribute
     * @return mixed
     */
    public function updateByField($id, $attribute = "_id", array $data = [])
    {
        $data = $data ?? $this->request->put();
        return $this->model->where($attribute, '=', $id)->update($data);
    }

    /**
     * @param  array $data
     * @param  $id
     * @return mixed
     */
    public function update(array $data = [])
    {
        $data = $data ?? $this->request->put();
        if (!($model = $this->model->find($data['id']))) {
            return false;
        }
        return $model->fill($data)->save();
    }

    /**
     * @return mixed
     */
    public function delete()
    {
        return $this->model->destroy();
    }

    /**
     * @param $id
     * @param string $columns
     * @return mixed
     */
    public function find($id, $columns = '*')
    {
        $this->applyFilter();
        $this->applyCriteria();
        $this->newQuery()->eagerLoadRelations()->eagerLoadRelationsCount();

        return $this->model->find($id, $this->processColumn($columns));
    }

    /**
     * @param $attribute
     * @param $value
     * @param string $columns
     * @return mixed
     */
    public function findBy($attribute, $value, $columns = '*')
    {
        $this->applyFilter();
        $this->applyCriteria();
        $this->newQuery()->eagerLoadRelations()->eagerLoadRelationsCount();

        return $this->model->where($attribute, '=', $value)->first($this->processColumn($columns));
    }

    /**
     * @param $attribute
     * @param $value
     * @param array $columns
     * @return mixed
     */
    public function findAllBy($attribute, $value, $columns = '*')
    {
        $this->applyFilter();
        $this->applyCriteria();
        $this->newQuery()->eagerLoadRelations()->eagerLoadRelationsCount();

        return $this->model->where($attribute, '=', $value)->get($this->processColumn($columns));
    }

    /**
     * Find a collection of models by the given query conditions.
     *
     * @param array $where
     * @param string $columns
     * @param bool $or
     *
     * @return \Illuminate\Database\Eloquent\Collection|null
     */
    public function findWhere($where, $columns = '*', $or = false)
    {
        $this->applyFilter();
        $this->applyCriteria();

        $model = $this->model;

        foreach ($where as $field => $value) {
            if ($value instanceof \Closure) {
                $model = (!$or)
                    ? $model->where($value)
                    : $model->orWhere($value);
            } elseif (is_array($value)) {
                if (count($value) === 3) {
                    list($field, $operator, $search) = $value;
                    $model = (!$or)
                        ? $model->where($field, $operator, $search)
                        : $model->orWhere($field, $operator, $search);
                } elseif (count($value) === 2) {
                    list($field, $search) = $value;
                    $model = (!$or)
                        ? $model->where($field, '=', $search)
                        : $model->orWhere($field, '=', $search);
                }
            } else {
                $model = (!$or)
                    ? $model->where($field, '=', $value)
                    : $model->orWhere($field, '=', $value);
            }
        }
        $this->newQuery()->eagerLoadRelations()->eagerLoadRelationsCount();

        return $model->get($this->processColumn($columns));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     * @throws RepositoryException
     */
    public function makeModel()
    {
        return $this->setModel($this->model());
    }

    /**
     * Set Eloquent Model to instantiate
     *
     * @param $eloquentModel
     * @return Model
     * @throws RepositoryException
     */
    public function setModel($eloquentModel)
    {
        $this->newModel = $this->app->make($eloquentModel);

        if (!$this->newModel instanceof Model)
            throw new RepositoryException("Class {$this->newModel} must be an instance of Illuminate\\Database\\Eloquent\\Model");

        return $this->model = $this->newModel::query();
    }

    /**
     * @return $this
     */
    public function resetScope()
    {
        $this->skipFilter(false);
        $this->skipCriteria(false);
        return $this;
    }

    /**
     * @param bool $status
     * @return $this
     */
    public function skipFilter($status = true)
    {
        $this->skipFilter = $status;
        return $this;
    }

    /**
     * @param bool $status
     * @return $this
     */
    public function skipCriteria($status = true)
    {
        $this->skipCriteria = $status;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getFilter()
    {
        return $this->filter;
    }

    private function getReturnFields($where='default')
    {
        if($where === 'default')
        {
            if($this->basicFields['return_fields'][0] !== '*') return $this->basicFields['return_fields'];
            if($this->basicFields['criteria_fields'][0] !== '*') return $this->basicFields['criteria_fields'];
            return ['*'];
        }
        return $this->basicFields[$where] ?? ['*'];
    }
    
    public function modifyReturnFields($fields, $where = 'manual_fields')
    {
        if(!is_array($fields)) throw new \Exception("ModifyReturnFields() expect \$field argument to be an array");

        if(in_array("*", $this->basicFields[$where]))
            $this->basicFields[$where] = $fields;
        else
            $this->basicFields[$where] = array_unique(array_merge($this->basicFields[$where], $fields));
    }

    public function setReturnFields($fields, $where = 'manual_fields')
    {
        if(!is_array($fields)) throw new \Exception("ModifyReturnFields() expect \$field argument to be an array");
        $this->basicFields[$where] = $fields;
    }

    /**
     * @param Filter $filter
     * @return $this
     */
    public function getByFilter(Filter $filter)
    {
        $r_model = $filter->apply($this->model, $this);
        if(isset($r_model->returnFields)){
            $this->modifyReturnFields($r_model->returnFields, 'criteria_fields');
        }
        if(isset($r_model->with)){
            $this->with($r_model->with);
        }
        $this->model = $r_model->model;
        return $this;
    }

    /**
     * @param Filter $filter
     * @return $this
     */
    public function pushFilter(Filter $filter)
    {
        if ($this->preventFilterOverwriting) {
            // Find existing filter
            $key = $this->filter->search(function ($item) use ($filter) {
                return (is_object($item) && (get_class($item) == get_class($filter)));
            });

            // Remove old filter
            if (is_int($key)) {
                $this->filter->offsetUnset($key);
            }
        }

        $this->filter->push($filter);
        return $this;
    }

    /**
     * @return $this
     */
    public function applyFilter()
    {
        if ($this->skipFilter === true)
            return $this;

        foreach ($this->getFilter() as $filter) {
            if ($filter instanceof Filter){
                $r_model = $filter->apply($this->model, $this);
                if(isset($r_model->returnFields)){
                    $this->modifyReturnFields($r_model->returnFields, 'criteria_fields');
                }
                if(isset($r_model->with)){
                    $this->with($r_model->with);
                }
                $this->model = $r_model->model;
            }
        }

        return $this;
    }

    public function applyCriteria()
    {
        if ($this->skipCriteria === true)
            return $this;

        foreach ($this->getCriteria() as $criterion) {
            if ($criterion instanceof Criteria){
                $r_model = $criterion->apply($this->model, $this);
                if(isset($r_model->returnFields)){
                    $this->modifyReturnFields($r_model->returnFields, 'criteria_fields');
                }
                if(isset($r_model->with)){
                    $this->with($r_model->with);
                }
                $this->model = $r_model->model;
            }
        }

        return $this;
    }

    public function getCriteria()
    {
        return $this->criteria;
    }

    /**
     * @param Filter $filter
     * @return $this
     */
    public function getByCriterion(Criteria $criteria)
    {
        $r_model = $criterion->apply($this->model, $this);
        if(isset($r_model->returnFields)){
            $this->modifyReturnFields($r_model->returnFields, 'criteria_fields');
        }
        if(isset($r_model->with)){
            $this->with($r_model->with);
        }
        $this->model = $r_model->model;

        return $this;
    }

    /**
     * @param Filter $filter
     * @return $this
     */
    public function pushCriterion(Criteria $criteria)
    {
        if ($this->preventCriteriaOverwriting) {
            // Find existing filter
            $key = $this->criteria->search(function ($item) use ($criteria) {
                return (is_object($item) && (get_class($item) == get_class($criteria)));
            });

            // Remove old filter
            if (is_int($key)) {
                $this->criteria->offsetUnset($key);
            }
        }

        $this->criteria->push($criteria);
        return $this;
    }

    protected function getByAttributes(Filter $filter, $attributes)
    {
        $filter->pushCompulsoryField($attributes);
        $this->pushFilter($filter);
        return $this;
    }

    /**
     * @param $relations
     * @return $this
     */
    public function withCount($relations)
    {
        if (is_string($relations)) $relations = func_get_args();
        $this->withCount = array_unique(array_merge($this->withCount, $relations));

        return $this;
    }

    /**
     * @return $this
     */
    protected function eagerLoadRelationsCount()
    {
        if (isset($this->withCount) && !empty($this->withCount)) {
            foreach ($this->withCount as $relation) {
                $this->model->withCount($relation);
            }
        }

        return $this;
    }
}
