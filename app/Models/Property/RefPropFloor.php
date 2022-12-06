<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefPropFloor extends Model
{
    use HasFactory;

    /**
     * | Get All Property Types
     */
    public function getPropTypes()
    {
        return RefPropFloor::select('id', 'floor_name')
            ->where('status', 1)
            ->get();
    }
}
