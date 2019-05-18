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
             * @param string $key
             * @param array $values [up, down] limits of in between; null means open end
             * @param bool $exclude 'true': > or <; 'false': >= or <=
             * @return static
             */
            $exclude = false;
            $collection = $collection->filter(function ($item) use ($k, $v, $exclude) {
                if (!empty($v)) {
                    $value = data_get($item, $k);
                    // remove comma in case of numeric value
                    if (preg_match('/[0-9]+[.,]?[0-9.]*/', data_get($item, $k))) {
                        $value = str_replace(',', '', $value);
                        if (!empty($v[0])) {
                            $v[0] = str_replace('$', '', $v[0]);
                        }
                        if (!empty($v[1])) {
                            $v[1] = str_replace('$', '', $v[1]);
                        }
                    }
                    if (is_null($v[0]) && !is_null($v[1])) //beginning open-ended
                    {
                        return $exclude ? $value < $v[1] : $value <= $v[1];
                    } else if (!is_null($v[0]) && is_null($v[1])) //end open-ended
                    {
                        return $exclude ? $value > $v[0] : $value >= $v[0];
                    } else if (!is_null($v[0]) && !is_null($v[1]) && ($v[0] < $v[1])) //between
                    {
                        return $exclude ? ($value > $v[0] && $value < $v[1])
                            : ($value >= $v[0] && $value <= $v[1]);
                    } else {
                        return $value;
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
        if (substr($fn, -1) != '_' && (static::isNumericRange($fv) || static::isDateRange($fv))) {
            $fv = explode('-', $fv);
        }

        if (is_array($fv)) {
            // if filter is a 'date', then change the format to Y-m-d
            if (str_contains($fn, 'date') || preg_match('/^([0]?[1-9]|[1|2][0-9]|[3][0|1])[.\/-]([0]?[1-9]|[1][0-2])[.\/-]([0-9]{4}|[0-9]{2})$/', trim($fv[0]))) {
                $fv = [date('Y-m-d', strtotime(trim($fv[0]))), date('Y-m-d', strtotime(trim($fv[1])))];
            }
            $filter = ['whereBetween', $fn, $fv];
        } else {
            // key = ab_cd_ef_ for rawWhere from url
            if (substr($fn, -1) == '_') {
                $fn = substr($fn, 0, -1);
                if (str_contains($fn, $fv))
                    $filter = ['whereRaw', $fn, str_replace('-', ' ', $fv)];
                else {
                    // Remove dashes from  string, only if they are outside of double quotes
                    // is-null => is null
                    // is-like-"%0618-603TM BE19%" => is like "%0618-603TM BE19%"
                    $fv = preg_replace("/-(?=(?:[^\"]*\"[^\"]*\")*[^\"]*$)/", ' ', $fv);
                    $filter = ['whereRaw', $fn, '`' . $fn . '` ' . $fv];
                }
            } else {
                // if filter is a 'date', then change the format to Y-m-d
                if (str_contains($fn, 'date'))
                    $fv = date('Y-m-d', strtotime(trim($fv)));
                $filter = ['where', $fn, $fv];
            }
        }
        return $filter;
    }

    private static function isDateRange($fv)
    {
        $formats = [
            'm/d/Y' => '/^\d{1,2}\/\d{1,2}\/\d{4}\s{0,1}-\s{0,1}\d{1,2}\/\d{1,2}\/\d{4}$/',
            'Y-m-d' => '/^\d{4}\-\d{1,2}\-\d{1,2}\s{0,1}-\s{0,1}\d{4}\-\d{1,2}\-\d{1,2}$/'
        ];
        foreach ($formats as $format) {
            if (preg_match($format, trim($fv))) {
                return true;
            }
        }
        return false;
    }

    private static function isNumericRange($fv)
    {
        return preg_match('/^(\d{1,9}(-)\d{1,9}?)$/', trim($fv));
    }
}