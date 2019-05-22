<?php

namespace VelitSol\EloquentFilter;


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
        if ($filter->name == 'whereRaw') {
            self::$builder->{$filter->name}($filter->value);
        } else if ($filter->name == 'whereNull') {
            if ($filter->value) {
                self::$builder->whereNull($filter->field);
            } else {
                self::$builder->whereNotNull($filter->field);
            }
        } else {
            if (!empty($filter->operator)) {
                self::$builder->{$filter->name}($filter->field, $filter->operator, $filter->value);
            } else {
                self::$builder->{$filter->name}($filter->field, $filter->value);
            }
        }
    }

    private static function applyOnRelation($filter)
    {
        if (!empty($filter->field)) {
            // filtering on relation
            self::$builder->whereHas($filter->relation, function ($q) use ($filter) {
                if ($filter->name == 'whereRaw') {
                    $q->{$filter->name}($filter->value);
                } else if ($filter->name == 'whereNull') {
                    if ($filter->value) {
                        $q->whereNull($filter->field);
                    } else {
                        $q->whereNotNull($filter->field);
                    }
                } else {
                    if (!empty($filter->operator)) {
                        $q->{$filter->name}($filter->field, $filter->operator, $filter->value);
                    } else {
                        $q->{$filter->name}($filter->field, $filter->value);
                    }
                }
            });
        } else {
            // querying existence or absence or relation
            if ($filter->value == null) {
                self::$builder->{$filter->name}($filter->relation);
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