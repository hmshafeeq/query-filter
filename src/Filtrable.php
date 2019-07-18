<?php

namespace VelitSol\EloquentFilter;


trait Filtrable
{

    public function newEloquentBuilder($query)
    {
        return new FiltrableEloquentBuilder($query);
    }


}


