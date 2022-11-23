<?php

namespace App\Http\Controllers\Property;


use App\Repository\Property\Interfaces\iObjectionRepository;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ObjectionController extends Controller
{
    protected $objection;
    public function __construct(iObjectionRepository $objection)
    {
        $this->Repository = $objection;
    }

    //Objection for Clerical Mistake
    public function applyObjection(Request $request)
    {
        return $this->Repository->applyObjection($request);
    }

    //objection type list
    public function objectionType(Request $request)
    {
        return $this->Repository->objectionType($request);
    }
}
