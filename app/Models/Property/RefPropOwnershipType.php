<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefPropOwnershipType extends Model
{
    use HasFactory;

    /**
     * | Get Property Ownership Types
     */
    public function getPropOwnerTypes()
    {
        return RefPropOwnershipType::select('id', 'ownership_type')
            ->where('status', 1)
            ->get();
    }
}
