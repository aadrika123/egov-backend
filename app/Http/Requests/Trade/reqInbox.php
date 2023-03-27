<?php

namespace App\Http\Requests\Trade;
class ReqInbox extends TradeRequest
{
    
    /**
     * Get the validation rules that apply to the request.ddd
     *
     * @return array
     */
    public function rules()
    {
        return [
            "key"       =>  "string",
            "wardNo"    =>  "digits_between:1,9223372036854775807",
            "formDate"  =>  "date",
            "toDate"    =>  "date",
        ];
    }
}
