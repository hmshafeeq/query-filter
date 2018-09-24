<?php

namespace VelitSol\EloquentFilter;


abstract class Filter
{

    /***
     * Access protected attribute of the model to do some hack
     * @param $obj
     * @param $name
     * @return array|mixed
     */
    private static function getProtectedValue($obj, $name)
    {
        try {
            $array = (array)$obj;
            $prefix = chr(0) . '*' . chr(0);
            return $array[$prefix . $name];
        } catch (\Exception $e) {
        }
        return [];
    }

    //<editor-fold desc="Query">

    /***
     * Apply filters on query
     * @param $builder
     */
    public static function applyOnQuery($builder)
    {

        $appends = self::getProtectedValue($builder->getModel(), 'appends');

        $filters = $builder->getParsedFilters();
        if (!empty($filters)) {
            foreach ($filters as $filterKey => $filterValue) {
                if (in_array($filterKey, $builder->getModelRelations())) {
                    foreach ($filterValue as $fK => $fV) {
                        $builder->whereHas($filterKey, function ($q) use ($fK, $fV, $appends) {
                            foreach ($fV as $k => $v) {
                                if (!in_array($k, $appends)) {
                                    self::applyFilterOnQuery($q, $fK, $k, $v);
                                }
                            }
                        });
                    }
                } else {
                    foreach ($filterValue as $k => $v) {
                        if (!in_array($k, $appends)) {
                            self::applyFilterOnQuery($builder, $filterKey, $k, $v);
                        }
                    }
                }
            }
        }
    }

    /***
     * Apply a filter on query
     * @param $builder
     * @param $filterKey
     * @param $key
     * @param $value
     */
    private static function applyFilterOnQuery(&$builder, $filterKey, $key, $value)
    {
        if ($filterKey == 'whereRaw') {
            $builder->{$filterKey}($value);
        } else {
            $builder->{$filterKey}($key, $value);
        }
    }

    //</editor-fold>

    //<editor-fold desc="Collection">

    /***
     * Apply filters on collection
     * @param $builder
     * @param $collection
     * @return mixed
     */
    public static function applyOnCollection($builder, $collection)
    {
        $appends = self::getProtectedValue($builder->getModel(), 'appends');

        $filters = $builder->getParsedFilters();

        if (!empty($filters)) {
            foreach ($filters as $filterKey => $filterValue) {
                foreach ($filterValue as $k => $v) {
                    if (in_array($k, $appends)) {
                        self::applyFilterOnCollection($collection, $filterKey, $k, $v);
                    }
                }
            }
        }
        return $collection;
    }

    /***
     * Apply a filter on some collection
     * @param $collection
     * @param $filterKey
     * @param $k
     * @param $v
     */
    private static function applyFilterOnCollection(&$collection, $filterKey, $k, $v)
    {
        // http://laravel.at.jeffsbox.eu/laravel-5-eloquent-collection-methods-wherebetween
        if ($filterKey == 'whereBetween') {
            /**
             * Filter items for the given key where value is between highest and lowest.
             *
             * @param  string $key
             * @param  array $values [up, down] limits of in between; null means open end
             * @param  bool $exclude 'true': > or <; 'false': >= or <=
             * @return static
             */
            $exclude = false;
            $collection = $collection->filter(function ($item) use ($k, $v, $exclude) {
                if (!empty($v)) {
                    if (is_null($v[0]) && !is_null($v[1])) //beginning open-ended
                    {
                        return $exclude ? data_get($item, $k) < $v[1] : data_get($item, $k) <= $v[1];
                    } else if (!is_null($v[0]) && is_null($v[1])) //end open-ended
                    {
                        return $exclude ? data_get($item, $k) > $v[0] : data_get($item, $k) >= $v[0];
                    } else if (!is_null($v[0]) && !is_null($v[1]) && ($v[0] < $v[1])) //between
                    {
                        return $exclude ? (data_get($item, $k) > $v[0] && data_get($item, $k) < $v[1])
                            : (data_get($item, $k) >= $v[0] && data_get($item, $k) <= $v[1]);
                    } else {
                        return data_get($item, $k);
                    }
                } else {
                    return data_get($item, $k);
                }
            });
        } else {
            $collection = $collection->{$filterKey}($k, $v);
        }
    }

    //</editor-fold>

    /***
     * Parse request and make a key value pair for filter array
     * @param $fn
     * @param $fv
     * @return array
     */
    public static function parse($fn, $fv)
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
                if (str_contains($fn, $fv))
                    $filter = ['whereRaw', $fn, str_replace('-', ' ', $fv)];
                else
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

}