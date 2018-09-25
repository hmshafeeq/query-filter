<?php

namespace VelitSol\EloquentFilter;


class FiltrableQueryBuilder extends \Illuminate\Database\Eloquent\Builder
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
    public function filter()
    {
        $model = $this->getModel();

        if (request()->has('filters')) {
            $filterCollection = collect(request()->get('filters'));
        } else {
            $filterCollection = request()->all();
        }

        $filters = collect($filterCollection)->filter(function ($value) {
            return !empty($value) || $value == "0";
        });

        $filters->each(function ($value, $key) use ($model) {

            if (isset($this->eagerLoad[$key]) || method_exists($model, $key)) {
                $this->modelRelations[] = $key;
                foreach ($value as $k => $v) {
                    if (!empty($v) || $v === 0) {
                        // e.g. where,'job_id',3
                        list($condition, $name, $val) = Filter::parse($k, $v);
                        $this->filters[$key][$condition][$name] = $val;
                    }
                }
            } else {
                // e.g. where,'job_id',3
                list($condition, $name, $val) = Filter::parse($key, $value);
                $this->filters[$condition][$name] = $val;
            }
        });

        // apply filters on query
        Filter::applyOnQuery($this);

        return $this;
    }

    /***
     * Ovveride get method of the eloquent builder
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|mixed
     */
    public function get($columns = ['*'])
    {
        if (count($this->filters) == 0) {
            return parent::get($columns);
        } else {
            $collection = parent::get($columns);
            // apply on collection - for appended attributes
            $collection = Filter::applyOnCollection($this, $collection);
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
