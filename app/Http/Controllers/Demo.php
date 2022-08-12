<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Lang;
use App\Models;
use App\Models\ActiveSafDetail;
use Illuminate\Support\Facades\DB;

class Demo extends Controller
{
    
    public function collection_test(Request $request)
    {
        /*
        *collection method allow to chenig methods;
        */
        $collection = collect(['taylor', 'abigail', null])->map(function ($name) {
            return strtoupper($name);
        })
        ->reject(function ($name) {
            return empty($name);
        });
        // dd($collection);

        /*
        *create the instanse of collection ;
        */
        $collection = collect(['x', 'y', 1]);
        //dd($collection);

        /*
        *Add the costome method in collection using macro class ;
        */
        Collection::macro('duplicate', function () {
            return $this->map(function ($value) {
                return ($value.$value);
            });
        });
        Collection::macro('upperStr', function () {
            return $this->map(function ($value) {
                return ($value.$value);
            });
        });

        $collection = collect(['x', 'y', 1,'z']);
        $str = $collection->duplicate(); //colle the collection duplicate custome function
        //dd($str);

        Collection::macro('toLocale', function ($locale) {
            return $this->map(function ($value) use ($locale) {
                return Lang::get($value, [], $locale);
            });
        });
         
        $collection = collect(['first', 'second']);
         
        $translated = $collection->toLocale('ind');
        dd($translated);
    }

    public function query_test(Request $request)
    {
        DB::enableQueryLog();
        $data = ActiveSafDetail::with('active_saf_owner_details')
                                ->whereHas('active_saf_owner_details',function($query){
                                    $query->where('saf_dtl_id','active_saf_details.id');
                                })
                            ->select('id','holding_no')
                            ->where('status',1)                            
                            ->get();
        $query = DB::getQueryLog();
        dd($query);die;
        dd($data);
    }

}
