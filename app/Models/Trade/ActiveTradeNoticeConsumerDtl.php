<?php

namespace App\Models\Trade;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActiveTradeNoticeConsumerDtl extends TradeParamModel
{
    use HasFactory;
    public $timestamps=false;
    protected $connection;
    public function __construct($DB=null)
    {
        parent::__construct($DB);
    }
}
