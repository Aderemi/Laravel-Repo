<?php

namespace LaraRepo\Repositories\Contracts;

use LaraRepo\Repositories\Criteria\Filter;

/**
 * Interface FilterInterface
 * @package LaraRepo\Repositories\Contracts
 */
interface FilterInterface {

    /**
     * @param bool $status
     * @return $this
     */
    public function skipFilter($status = true);

    /**
     * @return mixed
     */
    public function getFilter();

    /**
     * @param Filter $filter
     * @return $this
     */
    public function getByFilter(Filter $filter);

    /**
     * @param Filter $filter
     * @return $this
     */
    public function pushFilter(Filter $filter);

    /**
     * @return $this
     */
    public function  applyFilter();

    /**
     * @return $this
     */
    public function  applyCriteria();
}
