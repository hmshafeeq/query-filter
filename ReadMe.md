# Laravel Eloquent Filter

[![GitHub issues](https://img.shields.io/github/issues/hmshafeeq/eloquent-filter.svg)](https://github.com/hmshafeeq/eloquent-filter/issues)
[![GitHub forks](https://img.shields.io/github/forks/hmshafeeq/eloquent-filter.svg)](https://github.com/hmshafeeq/eloquent-filter/network)
[![GitHub stars](https://img.shields.io/github/stars/hmshafeeq/eloquent-filter.svg)](https://github.com/hmshafeeq/eloquent-filter/stargazers)
### A laravel package to filter eloquent models and their relationships based on URL query strings.  

## Introduction
Suppose we have following two models, related to each other.
#### User Model 
````
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model; 
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract; 

class User extends Model implements
    AuthenticatableContract,
    AuthorizableContract
{
    use Authenticatable, Authorizable, CanResetPassword;
    use Notifiable; 
}
````
#### Post Model
````
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{ 
      /**
      * Get the publisher for this model.
      */
      public function publisher()
      {
          return $this->belongsTo(User::class, 'user_id', 'id');
      }
}

````
And now we want to return a list of posts filtered by multiple parameters through URL query string. When we navigate to:
````
/posts?score=4&view_count='10-100'&publisher['age']='20-30'&publisher['type']='guest'&created_at='02/02/2018-04/02/2018'
````
in controller if we dump $request->all(), we will see following parameters:
````
[
    score => '4',
    view_count  => '10-100',
    publisher => [
      age => '20-30',
      type => 'guest'
    ],
    created_at => '02/02/2018-04/02/2018'
]
```` 
To filter the post model by all those parameters we would need to do something like:
````
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Post;

class PostController extends Controller
{

    public function index(Request $request)
    {
        $query = Post::where('score', $request->input('score'));
        
        if ($request->has('view_count'))
             $query->whereBetween('view_count', explode('-',$request->get('view_count')));
        
        if ($request->has('created_at'))
             $query->whereBetween('created_at', explode('-',$request->get('created_at')));
        
        // filter relation 
        if ($request->has('publisher')){
            $query->whereHas('publisher', function ($q) use ($request)
            {
                $publisher = $request->get('publisher');
                if(!empty($publisher['age']))
                    $q->whereBetween('age', explode('-',$publisher['age']));
                if(!empty($publisher['type']))
                    $q->where('type', $publisher['type']);
                return $q;
            });
        }        
        return $query->get();
    }

}
````
You can filter same model (along with it's relationships) by using eloquent filter : 

````
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Post;

class PostController extends Controller
{

    public function index(Request $request)
    {
        return Post::filter()->get();
    }

}
````

## Installation
Install the package via composer

````
composer require velitsol/eloquent-filter
````
 
## Usage

First you need to add Filterable trait and filters attribute to your model as the following :

```php
use VelitSol\EloquentFilter;

class Post extends Model {
    use Filterable; 
}
```
call filter method just before get()
````
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Post;

class PostController extends Controller
{

    public function index(Request $request)
    {
        return Post::filter()->get();
    }

}
```` 

#### Supported Filters   
##### Equality 
````
/posts?field_name=value
````
##### Range 
````
/posts?field_name='start-end'
````
##### Relationships
````
/posts?relationship_name[field_name]=value  OR /posts?relationship_name[field_name]=range
````
#### Custom 
Sometimes we need to filter data on some status which depends on some other field. In below example,
we want to filter all paid items, we don't have is_paid boolean field in table, but we do have paid_at (date) field which is set when an item is paid. So
we can filter on the basis of it's value. For custom filter to work, we need to place _ at the end of field name.  
````
<div class="form-group col-md-3">
    {!! Form::label('paid_at_','Is Paid',['class' => 'control-label']) !!}
    {!! Form::select('paid_at_',[''=>'','is-not-null'=>'Yes','is-null'=>'No'],null, ['class' => 'form-control', 'autocomplete' => "off" ]) !!}
</div>
````
So query string should look like 
````
/posts?field_name_='is-null'
````

This package also supports filtering on the basis of appended attributes. 

### TODO
* Support for lt, gt, lte, gte, neq query strings 

## License
This open-source software is licensed under the [MIT license](https://opensource.org/licenses/MIT).

