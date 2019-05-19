<?php

namespace VelitSol\EloquentFilter;


use Illuminate\Database\Eloquent\Builder;

class FiltrableQueryBuilder extends Builder
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
    public function filter($filters = null)
    {
        $model = $this->getModel();

        if (empty($filters)) {
            if (request()->has('filters')) {
                $filterCollection = collect(request()->get('filters'));
            } else if (request()->has('filter')) {
                $filterCollection = collect(request()->get('filter'));
            } else {
                $filterCollection = request()->all();
            }
        } else {
            $filterCollection = collect($filters);
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
                $this->filters[] = new Filter($key, $value);
            }
        });

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
