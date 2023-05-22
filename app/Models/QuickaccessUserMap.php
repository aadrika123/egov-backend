<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuickaccessUserMap extends Model
{
    use HasFactory;

    public function getListbyUserId($userId)
    {
        return QuickaccessUserMap::where('status', true)
            ->where('user_id', $userId)
            ->get();
    }
}
