<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropTransaction extends Model
{
    use HasFactory;

    public function getPropTransactions($id, $key)
    {
        return PropTransaction::where("$key", $id)
            ->get();
    }
}
