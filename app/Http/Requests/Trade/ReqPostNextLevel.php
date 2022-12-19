<?php

namespace App\Http\Requests\Trade;

class ReqPostNextLevel extends TradeRequest
{
    public function rules()
    {        
        $regex = '/^[a-zA-Z1-9][a-zA-Z1-9\.\-, \s]+$/';
        $rules = [
            "btn" => "required|in:btc,forward,backward",
            "licenceId" => "required|digits_between:1,9223372036854775807",
            "comment" => "required|min:10|regex:$regex",
        ];
        return $rules;
    }
    public function messages()
    {
        $message = [
            "btn.in"=>"Button Value must be In BTC,FORWARD,BACKWARD",
            "comment.required" => "Comment Is Required",
            "comment.min" => "Comment Length can't be less than 10 charecters",
        ];
        return $message;
    }
}
