<?php

namespace App\Repository\Property\Concrete;

use App\EloquentModels\Common\ModelWard;
use App\Repository\Common\CommonFunction;
use App\Repository\Property\Interfaces\IPropertyBifurcation;
use Illuminate\Http\Request;

class PropertyBifurcation implements IPropertyBifurcation
{

    protected $_common;
    protected $_modelWard;
    protected $_Saf;
    public function __construct()
    {
        $this->_common = new CommonFunction();
        $this->_modelWard = new ModelWard();
        $this->_Saf = new SafRepository();
    }
    public function addRecord(Request $request)
    {
        return $this->_Saf->masterSaf();
    }
}