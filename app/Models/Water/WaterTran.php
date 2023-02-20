<?php

namespace App\Models\Water;

use App\MicroServices\IdGeneration;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterTran extends Model
{
    use HasFactory;
    public $timestamps = false;

    /**
     * |--------------- Get Transaction Data -----------|
     */
    public function getTransactionDetailsById($req)
    {
        return WaterTran::where('related_id', $req)
            ->get();
    }

    /**
     * |---------------- Get transaction by the transaction details ---------------|
     */
    public function getTransNo($applicationId, $applicationFor)
    {
        return WaterTran::where('related_id', $applicationId)
            ->where('tran_type', "<>", "Demand Collection")
            ->where('status', true);
    }
    public function ConsumerTransaction($applicationId)
    {
        return WaterTran::where('related_id', $applicationId)
            ->where('tran_type', "=", "Demand Collection")
            ->where('status', true);
    }

    /**
     * | Get Transaction Details According to TransactionId
     * | @param 
     */
    public function getTransactionByTransactionNo($transactionNo)
    {
        return WaterTran::select(
            'water_trans.*',
            'water_tran_details.demand_id'
        )
            ->join('water_tran_details', 'water_tran_details.tran_id', '=', 'water_trans.id')
            ->where('tran_no', $transactionNo)
            ->where('water_trans.status', true)
            ->where('water_tran_details.status', true);
    }

    /**
     * | Enter the default details of the transacton which have 0 Connection charges
     * | @param
     * | @var 
     */
    public function saveZeroConnectionCharg($totalConnectionCharges, $ulbId, $req, $applicationId, $connectionId)
    {
        $refIdGeneration = new IdGeneration();
        $transactionNo = $refIdGeneration->generateTransactionNo();

        $Tradetransaction = new WaterTran;
        $Tradetransaction->related_id       = $applicationId;
        $Tradetransaction->ward_id          = $req->ward_id;
        $Tradetransaction->tran_type        = "Default";
        $Tradetransaction->tran_date        = Carbon::now();
        $Tradetransaction->payment_mode     = "Default";
        $Tradetransaction->amount           = $totalConnectionCharges;
        $Tradetransaction->emp_dtl_id       = authUser()->id;
        $Tradetransaction->created_at       = Carbon::now();
        $Tradetransaction->ip_address       = '';
        $Tradetransaction->ulb_id           = $ulbId;
        $Tradetransaction->tran_no          = $transactionNo;
        $Tradetransaction->save();
        $transactionId = $Tradetransaction->id;

        $mWaterTranDetail = new WaterTranDetail();
        $mWaterTranDetail->saveDefaultTrans($totalConnectionCharges, $applicationId, $transactionId, $connectionId);
    }

    public function chequeTranDtl($ulbId)
    {

        return WaterTran::select('*')
            ->where('payment_mode', 'DD')
            ->orWhere('payment_mode', 'CHEQUE')
            ->where('ulb_id', $ulbId);
    }

    /**
     * | Post Saf Transaction
     */
    public function waterTransaction($req, $demands)
    {
        $waterTrans = new WaterTran();
        $waterTrans->related_id = $req['id'];
        $waterTrans->amount = $req['amount'];
        $waterTrans->tran_type = 'Demand Collection';
        $waterTrans->tran_date = $req['todayDate'];
        $waterTrans->tran_no = $req['tranNo'];
        $waterTrans->payment_mode = $req['paymentMode'];
        $waterTrans->user_id = $req['userId'];
        $waterTrans->ulb_id = $req['ulbId'];
        $waterTrans->from_fyear = collect($demands)->last()['fyear'];
        $waterTrans->to_fyear = collect($demands)->first()['fyear'];
        $waterTrans->from_qtr = collect($demands)->last()['qtr'];
        $waterTrans->to_qtr = collect($demands)->first()['qtr'];
        $waterTrans->demand_amt = collect($demands)->sum('amount');
        $waterTrans->save();

        return [
            'id' => $waterTrans->id
        ];
    }
}
