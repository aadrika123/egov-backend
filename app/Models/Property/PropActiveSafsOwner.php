<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropActiveSafsOwner extends Model
{
    use HasFactory;


    /**
     * | Update Owner Basic Details
     */
    public function edit($req)
    {
        $owner = PropActiveSafsOwner::find($req->ownerId);

        $reqs = [];

        $owner->update($reqs);
    }
}
