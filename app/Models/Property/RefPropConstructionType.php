<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Exception;

class RefPropConstructionType extends Model
{
    use HasFactory;

    public function propConstructionType()
    {
        return RefPropConstructionType::select('id', "construction_type as constructionType")
            ->where('status', 1)
            ->get();
    }
}
