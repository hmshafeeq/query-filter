<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Array To Filter
    |--------------------------------------------------------------------------
    | Name of array which will contain all the filters.
    | Examples:
    | If 'array' value is 'filters', than  Your filter form's element names should be like filters[fieldname1:eq], filters[fieldname2:like]
    | If 'array' value is 'filterable', than  Your filter form's element names should be like filterable[fieldname1:eq], filterable[fieldname2:like]
    | You can modify the value as per your need, dont play with key.
    */
    'array' => 'filters',

    /*
    |--------------------------------------------------------------------------
    | Operator Keywords
    |--------------------------------------------------------------------------
    | List of operators which can be used in a where clause like $model->where('field','operator', $value)
    | e.g User::where('type','like', 'super-')->where('latest', '>=', 2)->get();
    | You can modify the operator keys, don't play with the values.
    */
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

    /*
    |--------------------------------------------------------------------------
    | Method Keywords
    |--------------------------------------------------------------------------
    |
    | List of keywords for which laravel provides eloquent methods.
    | You can modify the operator keys, don't play with the values.
    |
    */
    'methods' => [
        'bw' => 'whereBetween',
        '!bw' => 'whereNotBetween',
        'in' => 'whereIn',
        '!in' => 'whereNotIn',
        'isnull' => 'whereNull',
        '!isnull' => 'whereNotNull',
        'rex' => 'has',
    ]
];
