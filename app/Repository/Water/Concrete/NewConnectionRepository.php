<?php 

namespace App\Repository\Water\Concrete;

use App\Models\Water\WaterApplication;
use App\Repository\Water\Interfaces\iNewConnection;
use Illuminate\Http\Request;

/**
 * | -------------- Repository for the New Water Connection Operations ----------------------- |
 * | Created On-07-10-2022 
 * | Created By-Anshu Kumar
 */

 class NewConnectionRepository implements iNewConnection
 {
    public function store(Request $req)
    {
       // dd($req->all());
    //    $str="connectionTypeId";
    //    $pieces = preg_split('/(?=[A-Z])/',$str);
    //    $p=implode('_',$pieces);
    //    return strtolower($p);
    $a= $req->all();
    $newApplication=new WaterApplication();
    $test=implode(PHP_EOL,eloquentItteration($a,'$newApplication'));
    // return $test;
    // $newApplication->connection_type_id=1;
    $newApplication->save();
    if($newApplication){
        return 'Saved Successfully';
    }
    else{
        return 'Error';
    }
    //     $arr=[];
    //    foreach($a as $key=>$as){
    //         $pieces = preg_split('/(?=[A-Z])/',$key);           // for spliting the variable by its caps value
    //         $p=implode('_',$pieces);                            // Separating it by _ 
    //         $final=strtolower($p);                              // converting all in lower case
    //         $c=$newApplication.$final.'='.$as;              // Creating the Eloquent
    //         array_push($arr,$c);
    //    }
    //    $newApplication->save();
    // }
}
}