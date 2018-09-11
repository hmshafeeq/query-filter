<?php

namespace VelitSol\EloquentFilter;


abstract class Filter
{


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
                                    if ($fK == 'whereRaw') {
                                        $q->{$fK}($v);
                                    } else {
                                        $q->{$fK}($k, $v);
                                    }
                                }
                            }
                        });
                    }
                } else {
                    foreach ($filterValue as $k => $v) {
                        if (!in_array($k, $appends)) {
                            if ($filterKey == 'whereRaw') {
                                $builder->{$filterKey}($v);
                            } else {
                                $builder->{$filterKey}($k, $v);
                            }
                        }
                    }
                }
            }
        }
    }

    public static function applyOnCollection($builder, $collection)
    {
        $appends = self::getProtectedValue($builder->getModel(), 'appends');

        $filters = $builder->getParsedFilters();

        if (!empty($filters)) {
            foreach ($filters as $filterKey => $filterValue) {
                foreach ($filterValue as $k => $v) {
                    if (in_array($k, $appends)) {
                        $collection->{$filterKey}($k, $v);
                    }
                }
            }
        }
    }

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