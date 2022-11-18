<?php

namespace App\Http\Controllers;

use App\Repository\Property\Interfaces\iObjectionRepository;
use Illuminate\Http\Request;

class ObjectionController extends Controller
{
    protected $objection;
    public function __construct(iObjectionRepository $objection)
    {
        $this->Repository = $objection;
    }

    //Objection for Clerical Mistake
    public function ClericalMistake(Request $request)
    {
        return $this->Repository->ClericalMistake($request);
    }

    //
    public function getOwnerDetails(Request $request)
    {
        return $this->Repository->getOwnerDetails($request);
    }

    //
    public function rectification(Request $request)
    {
        return $this->Repository->rectification($request);
    }
}
