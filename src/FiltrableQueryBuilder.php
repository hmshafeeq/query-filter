<?php

namespace VelitSol\EloquentFilter;


class FiltrableQueryBuilder extends \Illuminate\Database\Eloquent\Builder
{
    protected $filters = [];

    protected $modelRelations = [];

    public function filter()
    {
        $model = $this->getModel();
        collect(request()->all())->filter(function ($value) {
            return !empty($value);
        })->each(function ($value, $key) use ($model) {
            if (isset($this->eagerLoad[$key]) || count($model->{$key}()) > 0) {
                $this->modelRelations[] = $key;
                foreach ($value as $k => $v) {
                    if (!empty($v)) {
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


}
