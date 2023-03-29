<?php

namespace App\Repository\Notice;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * Created By Sandeep Bara
 * Date 2023-03-027
 * Notice Module
 */

 interface INotice
{
    function add(Request $request);
    public function noticeList(Request $request);
    public function noticeView(Request $request);
}
