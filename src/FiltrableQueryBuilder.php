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

        $filters = collect(request()->all())->filter(function ($value) {
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

        return $this;
    }

    /***
     * Ovveride get method of the eloquent builder
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|mixed
     */
    public function get($columns = ['*'])
    {
        // apply on quries
        Filter::applyOnQuery($this);

        $collection = parent::get($columns);

        // apply on collection
        $collection = Filter::applyOnCollection($this, $collection);

        return $collection;
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
