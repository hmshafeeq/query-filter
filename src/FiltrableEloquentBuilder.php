<?php

namespace VelitSol\EloquentFilter;

use Exception;
use Illuminate\Database\Eloquent\Builder;

class FiltrableEloquentBuilder extends Builder
{
    /***
     * Filter array
     * @var array
     */
    protected $filters = [];

    /***
     * Model relations which are also required to be filterd
     * @var array
     */
    protected $modelRelations = [];

    /***
     * Use request to build filter array
     * @return $this
     */
    public function filter($params = [])
    {
        $params = request()->get(config('filterable.array'), $params);

        if (!empty($params)) {
            collect($params)->filter(function ($value) {
                return !empty($value) || $value == "0";
            })->each(function ($value, $key) {
                $this->filters[] = new Filter($key, $value);
            });
        } else {
            throw new Exception("Filterable array is not specified or is missing in request");
        }
        // apply filters on query
        FilterQuery::handle($this);

        return $this;
    }

    /***
     * Ovveride get method of the eloquent builder
     * @param array $columns
     * @return Builder[]|\Illuminate\Database\Eloquent\Collection|mixed
     */
    public function get($columns = ['*'])
    {
        if (count($this->filters) == 0) {
            return parent::get($columns);
        } else {
            $collection = parent::get($columns);
            // apply on collection - for appended attributes
            $collection = FilterCollection::handle($this, $collection);
            return $collection;
        }
    }

    public function getParsedFilters()
    {
        return $this->filters;
    }

    public function getModelRelations()
    {
        return $this->modelRelations;
    }


}
