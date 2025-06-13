<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\MicroServices\IdGenerator\PrefixIdGenerator;
use App\Models\Payment\RevDailycollection;
use App\Models\Payment\RevDailycollectiondetail;
use App\Models\Payment\TempTransaction;
use App\Models\Property\PropChequeDtl;
use App\Models\Property\PropTransaction;
use App\Models\Trade\TradeChequeDtl;
use App\Models\Trade\TradeTransaction;
use App\Models\UlbMaster;
use App\Models\Water\WaterChequeDtl;
use App\Models\Water\WaterTran;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * | Created On-31-01-2023 
 * | Created by-Mrinal Kumar
 * | Payment Cash Verification
 */

class CashVerificationController extends Controller
{
    /**
     * | Unverified Cash Verification List
     * | Serial : 1
     */
    public function cashVerificationList(Request $request)
    {
        try {
            $ulbId =  authUser($request)->ulb_id;
            $userId =  $request->id;
            $date = date('Y-m-d', strtotime($request->date));
            $propertyModuleId = Config::get('module-constants.PROPERTY_MODULE_ID');
            $waterModuleId = Config::get('module-constants.WATER_MODULE_ID');
            $tradeModuleId = Config::get('module-constants.TRADE_MODULE_ID');
            $mTempTransaction =  new TempTransaction();

            if (isset($userId)) {
                $data = $mTempTransaction->transactionDtl($date, $ulbId)
                    ->where('user_id', $userId)
                    ->get();
            }

            if (!isset($userId)) {
                $data = $mTempTransaction->transactionDtl($date, $ulbId)
                    ->get();
            }

            $collection = collect($data->groupBy("id")->all());

            $data = $collection->map(function ($val) use ($date, $propertyModuleId, $waterModuleId, $tradeModuleId) {
                $total =  $val->sum('amount');
                $prop  = $val->where("module_id", $propertyModuleId)->sum('amount');
                $water = $val->where("module_id", $waterModuleId)->sum('amount');
                $trade = $val->where("module_id", $tradeModuleId)->sum('amount');
                return [
                    "id" => $val[0]['id'],
                    "user_name" => $val[0]['name'],
                    "property" => $prop,
                    "water" => $water,
                    "trade" => $trade,
                    "total" => $total,
                    "date" => Carbon::parse($date)->format('d-m-Y'),
                    // "verified_amount" => 0,
                ];
            });

            $data = (array_values(objtoarray($data)));

            return responseMsgs(true, "List cash Verification", $data, "012201", "1.0", "", "POST", $request->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "012201", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }

    /**
     * | Verified Cash Verification List
     * | Serial : 2
     */
    public function verifiedCashVerificationList(Request $req)
    {
        try {
            $ulbId =  authUser($req)->ulb_id;
            $userId =  $req->id;
            $date = date('Y-m-d', strtotime($req->date));
            $propertyModuleId = Config::get('module-constants.PROPERTY_MODULE_ID');
            $waterModuleId = Config::get('module-constants.WATER_MODULE_ID');
            $tradeModuleId = Config::get('module-constants.TRADE_MODULE_ID');

            $revDailycollection =  RevDailycollection::select('users.id', 'name', 'deposit_amount', 'module_id', 'tran_no')
                ->join('rev_dailycollectiondetails as rdc', 'rdc.collection_id', 'rev_dailycollections.id')
                ->join('users', 'users.id', 'rev_dailycollections.tc_id')
                ->groupBy('users.id', 'name', 'rdc.deposit_amount', 'module_id', 'tran_no')
                ->where('deposit_date', $date)
                ->where('rev_dailycollections.status', 1)
                ->get();
            $collection = collect($revDailycollection->groupBy("id")->all());

            $data = $collection->map(function ($val) use ($date, $propertyModuleId, $waterModuleId, $tradeModuleId) {
                $total =  $val->sum('deposit_amount');
                $prop = $val->where("module_id", $propertyModuleId)->sum('deposit_amount');
                $water = $val->where("module_id", $waterModuleId)->sum('deposit_amount');
                $trade = $val->where("module_id", $tradeModuleId)->sum('deposit_amount');
                return [
                    "id" => $val[0]['id'],
                    "user_name" => $val[0]['name'],
                    "property" => $prop,
                    "water" => $water,
                    "trade" => $trade,
                    "total" => $total,
                    "date" => Carbon::parse($date)->format('d-m-Y'),
                    "verifyStatus" => true,
                    "tranNo" => $val[0]['tran_no'],
                ];
            });

            $data = (array_values(objtoarray($data)));

            return responseMsgs(true, "TC Collection", remove_null($data), "012202", "1.0", "", "POST", $req->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "012202", "1.0", "", "POST", $req->deviceId ?? "");
        }
    }

    /**
     * | Tc Collection Dtl
     * | Serial : 3
     */
    public function tcCollectionDtl(Request $request)
    {
        try {
            $request->validate([
                "date" => "required|date",
                "userId" => "required|numeric",

            ]);
            $userId =  $request->userId;
            $ulbId =  authUser($request)->ulb_id;
            $date = date('Y-m-d', strtotime($request->date));
            $propertyModuleId = Config::get('module-constants.PROPERTY_MODULE_ID');
            $waterModuleId = Config::get('module-constants.WATER_MODULE_ID');
            $tradeModuleId = Config::get('module-constants.TRADE_MODULE_ID');
            $mTempTransaction = new TempTransaction();
            $details = $mTempTransaction->transactionList($date, $userId, $ulbId);
            if ($details->isEmpty())
                throw new Exception("No Application Found for this id");

            $data['property'] = collect($details)->where('module_id', $propertyModuleId)->values();
            $data['water'] = collect($details)->where('module_id', $waterModuleId)->values();
            $data['trade'] = collect($details)->where('module_id', $tradeModuleId)->values();
            $data['Cash'] = collect($details)->where('payment_mode', '=', 'CASH')->sum('amount');
            $data['Cheque'] = collect($details)->where('payment_mode', '=', 'CHEQUE')->sum('amount');
            $data['DD'] = collect($details)->where('payment_mode', '=', 'DD')->sum('amount');
            $data['totalAmount'] =  $details->sum('amount');
            $data['numberOfTransaction'] =  $details->count();
            $data['collectorName'] =  collect($details)[0]->user_name;
            $data['date'] = Carbon::parse($date)->format('d-m-Y');
            $data['verifyStatus'] = false;

            return responseMsgs(true, "TC Collection", remove_null($data), "012203", "1.0", "", "POST", $request->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "012203", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }

    /**
     * | Verified tc collection
     * | Retrieves and summarizes verified collection details for a given collector on a specified date.
     * | Serial : 4
     */
    public function verifiedTcCollectionDtl(Request $request)
    {
        try {
            $request->validate([
                "date" => "required|date",
                "userId" => "required|numeric",
            ]);
            $userId =  $request->userId;
            $ulbId =  authUser($request)->ulb_id;
            $date = date('Y-m-d', strtotime($request->date));
            $propertyModuleId = Config::get('module-constants.PROPERTY_MODULE_ID');
            $waterModuleId = Config::get('module-constants.WATER_MODULE_ID');
            $tradeModuleId = Config::get('module-constants.TRADE_MODULE_ID');

            $mRevDailycollection = new RevDailycollection();
            $details = $mRevDailycollection->collectionDetails($ulbId)
                ->where('deposit_date', $date)
                ->where('tc_id', $userId)
                ->get();

            if ($details->isEmpty())
                throw new Exception("No Application Found for this id");

            $data['property'] = collect($details)->where('module_id', $propertyModuleId)->values();
            $data['water'] = collect($details)->where('module_id', $waterModuleId)->values();
            $data['trade'] = collect($details)->where('module_id', $tradeModuleId)->values();

            $data['Cash'] = collect($details)->where('payment_mode', 'CASH')->sum('amount');
            $data['Cheque'] = collect($details)->where('payment_mode', 'CHEQUE')->sum('amount');
            $data['DD'] = collect($details)->where('payment_mode', 'DD')->sum('amount');

            $data['totalAmount'] =  $details->sum('amount');
            $data['numberOfTransaction'] =  $details->count();
            $data['collectorName']   =  collect($details)[0]->tc_name;
            $data['collectorMobile'] =  collect($details)[0]->tc_mobile;
            $data['verifierName']    =  collect($details)[0]->verifier_name;
            $data['verifierMobile']  =  collect($details)[0]->verifier_mobile;
            $data['tranNo']  =  collect($details)[0]->tran_no;
            $data['verifyStatus']    =  true;
            $data['date'] = Carbon::parse($date)->format('d-m-Y');

            return responseMsgs(true, "TC Collection", remove_null($data), "012204", "1.0", "", "POST", $request->deviceId ?? "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "012204", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }

    /**
     * | For Verification of cash
     * | Verifies and processes cash transactions for property, water, and trade modules in a transactional manner.
     * | serial : 5
       | cashVerify:1
     */
    public function cashVerify(Request $request)
    {
        try {
            $user = authUser($request);
            $userId = $user->id;
            $ulbId = $user->ulb_id;
            $property =  $request->property;
            $water    =  $request->water;
            $trade    =  $request->trade;
            $mRevDailycollection = new RevDailycollection();
            $cashParamId = Config::get('PropertyConstaint.CASH_VERIFICATION_PARAM_ID');


            $idGeneration = new PrefixIdGenerator($cashParamId, $ulbId);
            $tranNo = $idGeneration->generate();

            $mReqs = new Request([
                "tran_no" => $tranNo,
                "user_id" => $userId,
                "demand_date" => Carbon::now(),  //   <- to be changed
                "deposit_date" => Carbon::now(),
                "ulb_id" => $ulbId,
                "tc_id" => 1,                    //   <- to be changed
            ]);

            #_Multiple Database Connection Started
            DB::beginTransaction();
            DB::connection('pgsql_master')->beginTransaction();
            DB::connection('pgsql_water')->beginTransaction();
            DB::connection('pgsql_trade')->beginTransaction();
            $collectionId =  $mRevDailycollection->store($mReqs);

            if ($property) {
                foreach ($property as $propertyDtl) {
                    $pTempTransaction = TempTransaction::find($propertyDtl['id']);
                    $tran_no =  $propertyDtl['tran_no'];
                    PropTransaction::where('tran_no', $tran_no)
                        ->update(
                            [
                                'verify_status' => 1,
                                'verify_date' => Carbon::now(),
                                'verified_by' => $userId
                            ]
                        );
                    $this->dailyCollectionDtl($propertyDtl, $collectionId);
                    if (!$pTempTransaction)
                        throw new Exception("No Transaction Found for this id");

                    $logTrans = $pTempTransaction->replicate();
                    $logTrans->setTable('log_temp_transactions');
                    $logTrans->id = $pTempTransaction->id;
                    $logTrans->save();
                    $pTempTransaction->delete();
                }
            }

            if ($water) {
                foreach ($water as $waterDtl) {
                    $wTempTransaction = TempTransaction::find($waterDtl['id']);
                    WaterTran::where('tran_no', $waterDtl['tran_no'])
                        ->update(
                            [
                                'verify_status' => 1,
                                'verified_date' => Carbon::now(),
                                'verified_by' => $userId
                            ]
                        );
                    $this->dailyCollectionDtl($waterDtl, $collectionId);
                    if (!$wTempTransaction)
                        throw new Exception("No Transaction Found for this id");

                    $logTrans = $wTempTransaction->replicate();
                    $logTrans->setTable('log_temp_transactions');
                    $logTrans->id = $wTempTransaction->id;
                    $logTrans->save();
                    $wTempTransaction->delete();
                }
            }

            if ($trade) {
                foreach ($trade as $tradeDtl) {
                    $tTempTransaction = TempTransaction::find($tradeDtl['id']);
                    TradeTransaction::where('tran_no', $tradeDtl['tran_no'])
                        ->update(
                            [
                                'is_verified' => 1,
                                'verify_date' => Carbon::now(),
                                'verify_by' => $userId
                            ]
                        );
                    $this->dailyCollectionDtl($tradeDtl, $collectionId);
                    if (!$tTempTransaction)
                        throw new Exception("No Transaction Found for this id");

                    $logTrans = $tTempTransaction->replicate();
                    $logTrans->setTable('log_temp_transactions');
                    $logTrans->id = $tTempTransaction->id;
                    $logTrans->save();
                    $tTempTransaction->delete();
                }
            }

            DB::commit();
            DB::connection('pgsql_master')->commit();
            DB::connection('pgsql_water')->commit();
            DB::connection('pgsql_trade')->commit();
            return responseMsgs(true, "Cash Verified", '', "012205", "1.0", "", "POST", $request->deviceId ?? "");
        } catch (Exception $e) {
            DB::rollBack();
            DB::connection('pgsql_master')->rollBack();
            DB::connection('pgsql_water')->rollBack();
            DB::connection('pgsql_trade')->rollBack();
            return responseMsgs(false, $e->getMessage(), "", "012205", "1.0", "", "POST", $request->deviceId ?? "");
        }
    }

    /**
     * | Stores detailed daily collection information for a given transaction under a specific collection ID.
     * | serial : 5.1
       | cashVerify:1.1
     */
    public function dailyCollectionDtl($tranDtl, $collectionId)
    {
        $RevDailycollectiondetail = new RevDailycollectiondetail();
        $mReqs = new Request([
            "collection_id" => $collectionId,
            "module_id" => $tranDtl['module_id'],
            "demand" => $tranDtl['amount'],
            "deposit_amount" => $tranDtl['amount'],
            "cheq_dd_no" => $tranDtl['cheque_dd_no'],
            "bank_name" => $tranDtl['bank_name'],
            "deposit_mode" => strtoupper($tranDtl['payment_mode']),
            "application_no" => $tranDtl['application_no'],
            "transaction_id" => $tranDtl['id']
        ]);
        $RevDailycollectiondetail->store($mReqs);
    }

    /**
     * | Cash Verification Receipt
     * | Retrieves and returns detailed cash receipt information by receipt number, 
     * | including payment breakdown and collector/verifier details.
     */
    public function cashReceipt(Request $request)
    {
        $request->validate([
            'receiptNo' => 'required'
        ]);
        try {
            $ulbId = authUser($request)->ulb_id;
            $mUlbMasters = new UlbMaster();
            $propertyModuleId = Config::get('module-constants.PROPERTY_MODULE_ID');
            $waterModuleId = Config::get('module-constants.WATER_MODULE_ID');
            $tradeModuleId = Config::get('module-constants.TRADE_MODULE_ID');

            $mRevDailycollection = new RevDailycollection();
            $details = $mRevDailycollection->collectionDetails($ulbId)
                ->where('rev_dailycollections.tran_no', $request->receiptNo)
                ->get();

            if ($details->isEmpty())
                throw new Exception("No Application Found for this id");

            $data['property'] = collect($details)->where('module_id', $propertyModuleId)->values();
            $data['water']    = collect($details)->where('module_id', $waterModuleId)->values();
            $data['trade']    = collect($details)->where('module_id', $tradeModuleId)->values();

            $data['Cash']   = collect($details)->where('payment_mode', 'CASH')->sum('amount');
            $data['Cheque'] = collect($details)->where('payment_mode', 'CHEQUE')->sum('amount');
            $data['DD']     = collect($details)->where('payment_mode', 'DD')->sum('amount');

            $data['totalAmount'] =  $details->sum('amount');
            $data['numberOfTransaction'] =  $details->count();
            $data['collectorName']       =  collect($details)[0]->tc_name;
            $data['collectorMobile']     =  collect($details)[0]->tc_mobile;
            $data['verifierName']        =  collect($details)[0]->verifier_name;
            $data['verifierMobile']      =  collect($details)[0]->verifier_mobile;
            $data['receiptNo']           =  collect($details)[0]->tran_no;
            $data['verificationDate']    =  collect($details)[0]->verification_date;
            $data['ulb']                 =  collect($details)[0]->ulb_name;
            $data['ulbDetails']          = $mUlbMasters->getUlbDetails($ulbId);

            return responseMsgs(true, "Cash Receipt", $data, "012206", "1.0", responseTime(), "POST", $request->deviceId ?? "");
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "012206", "1.0", responseTime(), "POST", $request->deviceId ?? "");
        }
    }

    
    #_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#_#
    /**
     * | Edit Cheque No
     * | Updates the cheque number for a transaction across multiple modules with validation 
     * | and transactional safety.
       | Currently not in use
     */
    public function editChequeNo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric',
            'moduleId' => 'required|numeric',
            'chequeNo' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'validation error',
                'errors' => $validator->errors()
            ], 401);
        }
        try {

            $propertyModuleId = Config::get('module-constants.PROPERTY_MODULE_ID');
            $waterModuleId = Config::get('module-constants.WATER_MODULE_ID');
            $tradeModuleId = Config::get('module-constants.TRADE_MODULE_ID');
            $tranDtl = TempTransaction::find($request->id);
            $tranId = $tranDtl->transaction_id;

            #_Multiple Database Connection Started
            DB::beginTransaction();
            DB::connection('pgsql_master')->beginTransaction();
            $tranDtl
                ->update(
                    ['cheque_dd_no' => $request->chequeNo]
                );

            if ($request->moduleId == $propertyModuleId) {
                PropChequeDtl::where('transaction_id', $tranId)
                    ->update(
                        ['cheque_no' => $request->chequeNo]
                    );
            }

            if ($request->moduleId == $waterModuleId) {
                WaterChequeDtl::where('transaction_id', $tranId)
                    ->update(
                        ['cheque_no' => $request->chequeNo]
                    );
            }

            if ($request->moduleId == $tradeModuleId) {
                TradeChequeDtl::where('tran_id', $tranId)
                    ->update(
                        ['cheque_no' => $request->chequeNo]
                    );
            }

            DB::commit();
            DB::connection('pgsql_master')->commit();
            return responseMsgs(true, "Edit Successful", "", "012207", "1.0", responseTime(), "POST", $request->deviceId ?? "");
        } catch (Exception $e) {
            DB::rollBack();
            DB::connection('pgsql_master')->rollBack();
            return responseMsgs(false, $e->getMessage(), "", "012207", "1.0", responseTime(), "POST", $request->deviceId ?? "");
        }
    }
}
