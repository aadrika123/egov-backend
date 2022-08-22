<?php

// Helper made by Sandeep Bara

// function for Static Message
if (!function_exists("responseMsg")) {
    function responseMsg($status, $message, $data)
    {
        $response = ['status' => $status, "message" => $message, "data" => $data];
        return response()->json($response, 200);
    }
}

if (!function_exists("print_var")) {
    function print_var($data = '')
    {
        echo "<pre>";
        print_r($data);
        echo ("</pre>");
    }
}

if (!function_exists("adjToArray")) {
    function adjToArray(object $data)
    {
        $arrays = $data->toArray();
        return $arrays;
    }
}

if (!function_exists("remove_null")) {
    function remove_null($data)
    {
        $collection = collect($data)->map(function ($name) {
            if (is_object($name)) {
                $paren_c =  collect($name)->map(function ($v) {

                    if (is_null($v))
                        return "";
                    else
                        return $v;
                });
                return ($paren_c);
            } else {
                if (is_null($name))
                    return "";
                else
                    return $name;
            }
        });
        return $collection;
    }
}

if(!function_exists("ConstToArray"))
{
    function ConstToArray( array $data,$type='')
    {
        $arra=[];
        $retuen = [];
        foreach ($data as $key => $value)
        {
            $arra['id'] = $key;
            if(is_array($value))
            {
                foreach ($value as $keys => $val)
                {
                    $arra[strtolower($keys)] = $val;
                }
            }
            else
            {
                $arra[strtolower($type)] = $value;
            }
            $retuen [] = $arra;
        }
        return $retuen;
    }
}

