<?php

namespace VelitSol\EloquentFilter;


class FiltrableQueryBuilder extends \Illuminate\Database\Eloquent\Builder
{
    protected $filters = [];

    public function filter()
    {
        collect(request()->all())->filter(function ($value) {
            return !empty($value);
        })->each(function ($value, $key) {
            if (isset($this->eagerLoad[$key])) {
                foreach ($value as $k => $v) {
                    if (!empty($v)) {
                        list($expression, $name, $condition) = $this->parseFilter($k, $v);
                        $this->filters[$key][$expression][$name] = $condition;
                    }
                }
            } else {
                list($expression, $name, $condition) = $this->parseFilter($key, $value);
                $this->filters[$expression][$name] = $condition;
            }
        });
        return $this;
    }

    private function parseFilter($fn, $fv)
    {
        // if filter name does not contains a '_' at the end,
        // and value contains a '-' then explode the value by '-' to make it array.
        if (substr($fn, -1) != '_' && str_contains($fv, '-')) {
            $fv = explode('-', $fv);
        }

        if (is_array($fv)) {
            // if filter is a 'date', then change the format to Y-m-d
            if (str_contains($fn, 'date'))
                $fv = [date('Y-m-d', strtotime(trim($fv[0]))), date('Y-m-d', strtotime(trim($fv[1])))];
            $filter = ['whereBetween', $fn, $fv];
        } else {
            // key = ab_cd_ef_ for rawWhere from url
            if (substr($fn, -1) == '_') {
                $fn = substr($fn, 0, -1);
                $filter = ['whereRaw', $fn, '`' . $fn . '` ' . str_replace('-', ' ', $fv)];
            } else {
                // if filter is a 'date', then change the format to Y-m-d
                if (str_contains($fn, 'date'))
                    $fv = date('Y-m-d', strtotime(trim($fv)));
                $filter = ['where', $fn, $fv];
            }
        }
        return $filter;
    }

    private function applyFilter()
    {
        if (!empty($this->filters)) {
            foreach ($this->filters as $filterKey => $filterValue) {
                if (isset($this->eagerLoad[$filterKey])) {
                    foreach ($filterValue as $fK => $fV) {
                        // check if has() or whereHas were already applied to the model
                        // then remove that first.
                        $wheres = $this->getQuery()->wheres;
                        foreach ($wheres as $i => $where) {
                            if ($where['type'] == 'Exists') {
                                similar_text($filterKey, $where['query']->from, $result);
                                if ($result > 75) {
                                    unset($this->getQuery()->wheres[$i]);
                                    unset($this->getQuery()->bindings[$i]);
                                    $this->whereHas($filterKey, function ($q) use ($fK, $fV) {
                                        foreach ($fV as $k => $v) {
                                            if ($fK == 'whereRaw') {
                                                $q->{$fK}($v);
                                            } else {
                                                $q->{$fK}($k, $v);
                                            }
                                        }
                                    });
                                }
                            }
                        }
                    }
                } else {
                    foreach ($filterValue as $k => $v) {
                        if ($filterKey == 'whereRaw') {
                            $this->{$filterKey}($v);
                        } else {
                            $this->{$filterKey}($k, $v);
                        }
                    }
                }
            }
        }
        return $this;
    }

    public function get($columns = ['*'])
    {
        $this->applyFilter();

        return parent::get($columns);
    }


}
