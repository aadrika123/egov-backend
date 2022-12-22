<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Exception;

class RefPropType extends Model
{
    use HasFactory;

    /**
     * | Get All Property Types
     */
    public function propPropertyType()
    {
        return RefPropType::select('id', 'property_type')
            ->where('status', 1)
            ->get();
    }
}
