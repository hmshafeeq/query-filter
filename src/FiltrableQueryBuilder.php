<?php

namespace VelitSol\EloquentFilter;


class FiltrableQueryBuilder extends \Illuminate\Database\Eloquent\Builder
{
    public $filters = [];

    protected $modelRelations = [];

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
                    if (!empty($value) || $value == "0") {
                        list($expression, $name, $condition) = Filter::parse($k, $v);
                        $this->filters[$key][$expression][$name] = $condition;
                    }
                }
            } else {
                list($expression, $name, $condition) = Filter::parse($key, $value);
                $this->filters[$expression][$name] = $condition;
            }
        });

        return $this;
    }

    public function get($columns = ['*'])
    {
        Filter::apply($this);

        return parent::get($columns);
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
