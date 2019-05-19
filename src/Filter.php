<?php

namespace VelitSol\EloquentFilter;


class Filter
{
    public $name;
    public $operator;
    public $field;
    public $value;
    public $relation;

    private $maps = [
        'operators' => [
            'like' => 'like',
            'nlike' => 'not like',
            'eq' => '=',
            'neq' => '!=',
            'gte' => '>=',
            'lte' => '<=',
            'gt' => '>',
            'lt' => '<',
        ],
        'methods' => [
            'bw' => 'whereBetween',
            'nbw' => 'whereNotBetween',
            'in' => 'whereIn',
            'nin' => 'whereNotIn',
            'rex' => 'has',
        ]
    ];


    public function __construct($_key, $_value)
    {
        $fns = explode(':', $_key, 2);

        $this->field = self::getField($fns);
        $this->name = self::getName(array_last($fns));
        $this->operator = self::getOperator(array_last($fns));
        $this->value = self::getValue($_value, array_last($fns));
        $this->relation = self::getRelation($fns);
    }

    private function getField($fns)
    {
        if (str_contains(array_last($fns), ['rex'])) {
            return null;
        } else if (str_contains(array_first($fns), '.')) {
            return array_last(explode('.', array_first($fns)));
        }
        return array_first($fns);
    }

    private function getRelation($fns)
    {
        if (str_contains(array_last($fns), ['rex'])) {
            return array_first($fns);
        } else if (str_contains(array_first($fns), '.')) {
            return explode('.', array_first($fns))[0];
        }
        return null;
    }

    private function getOperator($key)
    {
        return isset($this->maps['operators'][$key]) ? $this->maps['operators'][$key] : null;
    }

    private function getValue($_value, $_key)
    {
        if (in_array($_key, $this->maps['operators'])) {
            $operator = isset($this->maps['operators'][$_key]) ? $this->maps['operators'][$_key] : '';
            if (str_contains('like', $operator)) {
                $_value = "%{$_value}%";
            }
        } else if (in_array($_key, ['bw', 'nbw'])) {
            if (self::isNumericRange($_value)) {
                $_value = array_map('trim', explode('-', $_value, 2));
            } else if (self::isDateRange($_value)) {
                $_value = array_map(function ($_v) {
                    return date('Y-m-d', strtotime(trim($_v)));
                }, explode('-', $_value, 2));
            }
        } else if (in_array($_key, ['in', 'nin'])) {
            $_value = array_map('trim', explode(',', $_value));
        }
        return $_value;
    }

    private function getName($_key)
    {
        return isset($this->maps['methods'][$_key]) ? $this->maps['methods'][$_key] : 'where';
    }

    private function isDateRange($fv)
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

    private function isNumericRange($fv)
    {
        return preg_match('/^[A-Za\s-\sz0-9-]+$/', trim($fv));
    }
}