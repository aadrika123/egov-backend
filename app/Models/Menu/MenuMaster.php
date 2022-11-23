<?php

namespace App\Models\Menu;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuMaster extends Model
{
    use HasFactory;

    public function fetchAllMenues()
    {
        return MenuMaster::orderByDesc('id')
            ->get();
    }
}
