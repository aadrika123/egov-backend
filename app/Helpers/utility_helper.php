<?php

use Illuminate\Support\Facades\DB;

if(!function_exists("print_var"))
{
    function print_var($data = '')
    {
        echo "<pre>";print_r($data);echo("</pre>");
    }
}

if(!function_exists("adjToArray"))
{
    function adjToArray( object $data)
    {
        $arrays= $data->toArray();
        return $arrays;
    }
}

if(!function_exists("remove_null"))
{
    function remove_null($data)
    {
        $filtered = $data->filter(function ($value, $key) {
            return $value != null;
        });
        return $filtered;

        $collection = collect($data)->map(function ($name) {            
            if(is_object($name))
            {
                    $paren_c =  collect($name)->map(function ($v) {
                        
                        if($v==null)
                            return "";
                        else
                            return $v;
                });
                return($paren_c);             

            }
            else
            {
                if($name==null)
                    return "";
                else
                    return $name;
            }
    });       
    return $collection;
    }
}


