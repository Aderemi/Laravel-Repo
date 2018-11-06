# Laravel Repositories

Laravel Repositories is a package for Laravel which is used to abstract the database layer. This makes applications much easier to maintain.

## Installation

Run the following command from you terminal:


 ```bash
 composer require "Aderemi/LaraRepo: @dev"
 ```

or add this to require section in your composer.json file:

 ```
 "supermart_nigeria/library": "@dev"
 ```

then run ```composer update```


## Usage

First, create your repository class. Note that your repository class MUST extend ```LaraRepo\Repositories\Eloquent\Repository``` and implement model() method

```php
<?php namespace App\Models\Repositories;

use LaraRepo\Repositories\Contracts\RepositoryInterface;
use LaraRepo\Repositories\Eloquent\Repository;

class AdSlotRepository extends Repository {

    public function model() {
        return 'App\AdSlot';
    }
}
```

By implementing ```model()``` method you telling repository what model class you want to use. Now, create ```App\Models\AdSlot``` model:

```php
<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class AdSlot extends Model {

    protected $primaryKey = 'id';

    protected $table = 'ad_slots';

    protected $slot = [
        "head_section"       => 1
    ];
}
```

And finally, use the repository in the controller:

```php
<?php namespace App\Http\Controllers;

use App\Models\Repositories\AdSlotRepository as AdSlot;

class AdSlotsController extends Controller {

    private $adSlot;

    public function __construct(AdSlot $adSlot) {

        $this->adSlot = $adSlot;
    }

    public function index() {
        return \Response::json($this->adSlot->all());
    }
}
```

## Available Methods

The following methods are available:

##### LaraRepo\Repositories\Contracts\RepositoryInterface

```php
public function all($columns = array('*'))
public function lists($value, $key = null)
public function paginate($perPage = 1, $columns = array('*'));
public function create(array $data)
// if you use mongodb then you'll need to specify primary key $attribute
public function update(array $data, $id, $attribute = "id")
public function delete($id)
public function find($id, $columns = array('*'))
public function findBy($field, $value, $columns = array('*'))
public function findAllBy($field, $value, $columns = array('*'))
public function findWhere($where, $columns = array('*'))
```

##### LaraRepo\Repositories\Contracts\CriteriaInterface

```php
public function apply($model, Repository $repository)
```

### Example usage


Create a new adSlot in repository:

```php
$this->adSlot->create(Input::all());
```

Update existing adSlot:

```php
$this->adSlot->update(Input::all(), $adSlot_id);
```

Delete adSlot:

```php
$this->adSlot->delete($id);
```

Find adSlot by adSlot_id;

```php
$this->adSlot->find($id);
```

you can also chose what columns to fetch:

```php
$this->adSlot->find($id, ['name', 'adLocation', 'created_by']);
```

Get a single row by a single column criteria.

```php
$this->adSlot->findBy('name', $name);
```

Or you can get all rows by a single column criteria.
```php
$this->adSlot->findAllBy('adLocation', $adLocation);
```

Get all results by multiple fields

```php
$this->adSlot->findWhere([
    'adLocation' => $adLocation,
    ['created_by','>',$created]
]);
```

## Criteria

Criteria is a simple way to apply specific condition, or set of conditions to the repository query. Your criteria class MUST extend the abstract ```LaraRepo\Repositories\Criteria\Criteria``` class.

Here is a simple criteria:

```php
<?php 
namespace App\Models\Repositories\Criteria;

use LaraRepo\Repositories\Criteria\Criteria;
use LaraRepo\Repositories\Contracts\RepositoryInterface as Repository;

class CreatedBefore extends Criteria {

    /**
     * @param $date
     * @return null
     */
    public function __construct($date)
    {
	  $this->fields[0] = $date;
    }

    protected function getSearchableFieldsConfig(): array
    {
        return [
            $this->fields[0] => [
                'type' => self::FIELD_DATE,
                'searchType' => self::DATE_SEARCH_BEFORE
            ]
        ];
    }

}
```

The difference between criteria and filter is :
Criteria can be used by any model While filter is tie to a model

Here is a filter
```php
<?php namespace App\Models\Repositories\Criteria;

use LaraRepo\Repositories\Criteria\Filter;

class AdSlotOnlyOnPage extends Filter {

    protected $basicFields = ['id', 'adLocation', 'name']; // Fields to select
    protected function getSearchableFieldsConfig(): array
    {
        return [
            "adLocation" => [
                'searchType' => self::STR_SEARCH_EQUALS
            ],
            "createdBy" => [
                'searchType' => self::DATE_SEARCH_BETWEEN,
                'orWhere' => true
            ],
            "ads.publishedBy" => [
                'searchType' => self::DATE_SEARCH_BETWEEN,
                'orWhere' => true
            ],
            "ads@name" => [
                'searchType' => self::STR_SEARCH_CONTAINS
            ]
        ];
    }

}
```
Now, inside your controller class you call pushCriteria method:
```php
<?php namespace App\Http\Controllers;

use App\Models\Repositories\Filters\AdSlotOnlyOnPage;
use App\Repositories\AdSlotsRepository as AdSlot;

class AdSlotsController extends Controller {

    /**
     * @var AdSlot
     */
    private $adSlot;

    public function __construct(AdSlot $adSlot) {

        $this->adSlot = $adSlot;
    }

    public function index() {
        $on_page = new AdSlotOnlyOnPage();
        //$on_page->fill(["adLocation" => 'landingPage']);
        $this->adSlot->pushFilter($on_page);
        //ONE LINER: $this->adSlot->pushFilter((new AdSlotOnlyOnPage())->fill(["adLocation" => 'landingPage']))
        return \Response::json($this->adSlot->all());
    }

    public function getAdSlotCreatedAt(Request $request) {
        $created_before = new CreatedBefore("created_at");
        $created_before->fill(["created_at" => $request->created_at]);
        $this->adSlot->pushCriteria($created_before);
        //ONE LINER: return \Response::json($this->adSlot->pushCriteria((new CreatedBefore("created_at"))->fill($request->all())));
        return \Response::json($this->adSlot->all());
    }
    public function getAdSlotWithRelation() //This is eager-loaded
    {
        return $this->sendSuccess(["adSlots" => $this->adSlot->pushFilter(new AdSlotFilter())->with(['ads'])->all()]);
    }

    public function createAdSlot(Request $request)
    {
        $this->adSlot->validator['post'] = [
                        'name' => 'required|unique:ad_slots|max:50',
                        'adLocation' => 'required',
                     ];
        $added_adSlot = $this->adSlot->create();
        return \Response::json($added_adSlot); 
    }
    
    public function updateAdSlot(Request $request)
    {
        $this->adSlot->validator['put'] = [
                        'id' => 'required',
                        'name' => 'required|unique:ad_slots|max:50',
                        'adLocation' => 'required',
                     ];
        $updated_adSlot = $this->adSlot->update();
        return \Response::json($updated_adSlot); 
    }
}
```
Still editing....[STILL WORKING ON IT: This is just the intent]
# Laravel Repo
