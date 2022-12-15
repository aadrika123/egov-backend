<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Exception;

class RefPropUsageType extends Model
{
    use HasFactory;

    public function propUsageType()
    {
        return RefPropUsageType::select('id', 'usage_type as usageType', 'usage_code as usageCode')
            ->where('status', 1)
            ->get();
    }
}
