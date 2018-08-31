<?php

namespace VelitSol\EloquentFilter;


trait Filtrable
{
    public static function bootFiltrableTrait()
    {
        $self = new static();


    }

    public function newEloquentBuilder($query)
    {
        return new FiltrableQueryBuilder($query);
    }



}


