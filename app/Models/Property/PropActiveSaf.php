<?php

namespace App\Models\Property;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropActiveSaf extends Model
{
    use HasFactory;

    /**
     * |-------------------------- safs list whose Holding are not craeted -----------------------------------------------|
     * | @var safDetails
     */
    public function allNonHoldingSaf()
    {
        $allSafList = PropActiveSaf::select(
            'id AS SafId'
        )
            ->get();
        return $allSafList;
    }
}
