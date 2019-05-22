<?php

namespace VelitSol\EloquentFilter;


abstract class FilterCollection
{


    private static $builder;

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

    //<editor-fold desc="Collection">

    /***
     * Apply filters on collection
     * @param $builder
     * @param $collection
     * @return mixed
     */
    public static function handle($builder, $collection)
    {
        self::$builder = $builder;

        $appends = self::getProtectedValue($builder->getModel(), 'appends');

        $filters = $builder->getParsedFilters();

        foreach ($filters as $filter) {
            if (in_array($filter->field, $appends)) {
                self::apply($collection, $filter);
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
    private static function apply(&$collection, $filter)
    {
        // http://laravel.at.jeffsbox.eu/laravel-5-eloquent-collection-methods-wherebetween
        if ($filter->name == 'whereBetween') {
            /**
             * Filter items for the given key where value is between highest and lowest.
             *
             * @param string $key
             * @param array $values [up, down] limits of in between; null means open end
             * @param bool $exclude 'true': > or <; 'false': >= or <=
             * @return static
             */
            $collection = $collection->filter(function ($item) use ($filter) {
                $exclude = false;
                if (!empty($filter->value)) {
                    $value = data_get($item, $filter->field);
                    // remove comma in case of numeric value
                    if (preg_match('/[0-9]+[.,]?[0-9.]*/', data_get($item, $filter->field))) {
                        $value = str_replace('$', '', str_replace(',', '', $value));
                        if (!empty($filter->value[0])) {
                            $filter->value[0] = str_replace('$', '', $filter->value[0]);
                        }
                        if (!empty($filter->value[1])) {
                            $filter->value[1] = str_replace('$', '', $filter->value[1]);
                        }
                    }
                    if (is_null($filter->value[0]) && !is_null($filter->value[1])) //beginning open-ended
                    {
                        return $exclude ? $value < $filter->value[1] : $value <= $filter->value[1];
                    } else if (!is_null($filter->value[0]) && is_null($filter->value[1])) //end open-ended
                    {
                        return $exclude ? $value > $filter->value[0] : $value >= $filter->value[0];
                    } else if (!is_null($filter->value[0]) && !is_null($filter->value[1]) && ($filter->value[0] < $filter->value[1])) //between
                    {
                        return $exclude ? ($value > $filter->value[0] && $value < $filter->value[1])
                            : ($value >= $filter->value[0] && $value <= $filter->value[1]);
                    } else {
                        return $value;
                    }
                } else {
                    return data_get($item, $filter->field);
                }
            });
        } else {
            $collection = $collection->{$filter->name}($filter->field, $filter->value);
        }
    }

    //</editor-fold>

}