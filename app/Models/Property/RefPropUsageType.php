<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Exception;

class RefPropUsageType extends Model
{
    use HasFactory;

    public function getPropUsageTypes()
    {
        return RefPropUsageType::select('id', 'usage_type', 'usage_code')
            ->where('status', 1)
            ->get();
    }
}
