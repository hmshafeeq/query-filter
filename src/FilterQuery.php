<?php

namespace VelitSol\EloquentFilter;


use Illuminate\Support\Str;

abstract class FilterQuery
{

    protected static $builder;

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
    public static function handle($builder)
    {
        self::$builder = $builder;
        $appends = self::getProtectedValue($builder->getModel(), 'appends');
        $filters = $builder->getParsedFilters();
        foreach ($filters as $filter) {
            if (!in_array($filter->field, $appends)) {
                if (!empty($filter->relation))
                    self::applyOnRelation($filter);
                else
                    self::applyOnModel($filter);
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
    private static function applyOnModel($filter)
    {
        if (!Str::contains($filter->field, "."))
            $filter->field = self::$builder->getModel()->getTable() . "." . $filter->field;

        if ($filter->method == 'whereRaw') {
            self::$builder->{$filter->method}($filter->value);
        } else if ($filter->method == 'whereNull') {
            if ($filter->value) {
                self::$builder->whereNull($filter->field);
            } else {
                self::$builder->whereNotNull($filter->field);
            }
        } else {
            if (in_array($filter->method, ['whereNotBetween', 'whereBetween', 'whereNotIn', 'whereIn', 'whereDate', 'whereMonth', 'whereDay', 'whereYear', 'whereTime'])) {
                self::$builder->{$filter->method}($filter->field, $filter->value);
            } else if (in_array($filter->method, ['whereNull', 'whereNotNull', 'orWhereNull', 'orWhereNotNull'])) {
                self::$builder->{$filter->method}($filter->field);
            } elseif (!empty($filter->operator)) {
                self::$builder->{$filter->method}($filter->field, $filter->operator, $filter->value);
            } else {
                self::$builder->{$filter->method}($filter->field, $filter->value);
            }
        }
    }

    private static function applyOnRelation($filter)
    {
        if (!empty($filter->field)) {
            // filtering on relation
            self::$builder->whereHas($filter->relation, function ($q) use ($filter) {

                if (!Str::contains($filter->field, "."))
                    $filter->field = $q->getModel()->getTable() . "." . $filter->field;

                if ($filter->method == 'whereRaw') {
                    $q->{$filter->method}($filter->value);
                } else if ($filter->method == 'whereNull') {
                    if ($filter->value) {
                        $q->whereNull($filter->field);
                    } else {
                        $q->whereNotNull($filter->field);
                    }
                } else {

                    if (in_array($filter->method, ['whereNotBetween', 'whereBetween', 'whereNotIn', 'whereIn', 'whereDate', 'whereMonth', 'whereDay', 'whereYear', 'whereTime'])) {
                        $q->{$filter->method}($filter->field, $filter->value);
                    } else if (in_array($filter->method, ['whereNull', 'whereNotNull', 'orWhereNull', 'orWhereNotNull'])) {
                        $q->{$filter->method}($filter->field);
                    } elseif (!empty($filter->operator)) {
                        $q->{$filter->method}($filter->field, $filter->operator, $filter->value);
                    } else {
                        $q->{$filter->method}($filter->field, $filter->value);
                    }
                }
            });
        } else {
            // querying existence or absence or relation
            if ($filter->value == null) {
                self::$builder->{$filter->method}($filter->relation);
            } else {
                if ($filter->value) {
                    self::$builder->has($filter->relation);
                } else {
                    self::$builder->doesntHave($filter->relation);
                }
            }
        }
    }

    //</editor-fold>

}
