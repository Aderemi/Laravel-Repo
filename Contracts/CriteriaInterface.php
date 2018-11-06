<?php

namespace LaraRepo\Repositories\Contracts;

use LaraRepo\Repositories\Criteria\Criteria;
use LaraRepo\Repositories\Criteria\Filter;

/**
 * Interface FilterInterface
 * @package LaraRepo\Repositories\Contracts
 */
interface CriteriaInterface {

    /**
     * @return mixed
     */
    public function getCriteria();

    /**
     * @param Criteria $criterion
     * @return $this
     */
    public function getByCriterion(Criteria $criterion);

    /**
     * @param Criteria $criteria
     * @return $this
     */
    public function pushCriterion(Criteria $criteria);

    /**
     * @return $this
     */
    public function  applyCriteria();
}
