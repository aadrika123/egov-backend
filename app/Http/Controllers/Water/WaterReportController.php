<?php

namespace App\Http\Controllers\Water;

use App\Http\Controllers\Controller;
use App\Models\Water\WaterTran;
use App\Traits\Water\WaterTrait;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * | ----------------------------------------------------------------------------------
 * | Water Module |
 * |-----------------------------------------------------------------------------------
 * | Created On-14-04-2023
 * | Created By-Mrinal Kumar 
 * | Created For-Water Related Reports
 */

class WaterReportController extends Controller
{
    use WaterTrait;
    /**
     * | Water count of online payment
        | Serial No : 01
        | Not Tested
     */
    public function onlinePaymentCount(Request $req)
    {
        try {
            $mWaterTran = new WaterTran();
            $year = Carbon::now()->year;

            if (isset($req->fyear))
                $year = substr($req->fyear, 0, 4);

            $financialYearStart = $year;
            if (Carbon::now()->month < 4) {
                $financialYearStart--;
            }

            $fromDate =  $financialYearStart . '-04-01';
            $toDate   =  $financialYearStart + 1 . '-03-31';

            if ($req->financialYear) {
                $fy = explode('-', $req->financialYear);
                $strtYr = collect($fy)->first();
                $endYr = collect($fy)->last();
                $fromDate =  $strtYr . '-04-01';
                $toDate   =  $endYr . '-03-31';;
            }

            $waterTran = $mWaterTran->getOnlineTrans($fromDate, $toDate)->get();
            $returnData = [
                'waterCount' => $waterTran->count(),
                'totaAmount' => collect($waterTran)->sum('amount')
            ];

            return responseMsgs(true, "Online Payment Count", remove_null($returnData), "", '', '01', '.ms', 'Post', $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "", "01", ".ms", "POST", $req->deviceId);
        }
    }

    /**
     * | Water DCB
        | Serial No : 02
        | Not Working
     */
    public function waterDcb(Request $request)
    {
        $metaData = collect($request->metaData)->all();
        list($apiId, $version, $queryRunTime, $action, $deviceId) = $metaData;
        try {
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $ulbId          = $refUser->ulb_id;
            $wardId = null;
            $fiYear = getFY();
            if ($request->fiYear) {
                $fiYear = $request->fiYear;
            }
            list($fromYear, $toYear) = explode("-", $fiYear);
            if ($toYear - $fromYear != 1) {
                throw new Exception("Enter Valide Financial Year");
            }
            $fromDate = $fromYear . "-04-01";
            $uptoDate = $toYear . "-03-31";
            if ($request->ulbId) {
                $ulbId = $request->ulbId;
            }
            if ($request->wardId) {
                $wardId = $request->wardId;
            }
            $perPage = $request->perPage ? $request->perPage : 10;
            $page = $request->page && $request->page > 0 ? $request->page : 1;
            $limit = $perPage;
            $offset =  $request->page && $request->page > 0 ? ($request->page * $perPage) : 0;


            $from = "
                FROM (
                    SELECT *
                    FROM water_consumers
                    WHERE water_consumers.ulb_id = $ulbId
                    ORDER BY id
                    limit $limit offset $offset
                  )water_consumers
                LEFT JOIN (
                    SELECT STRING_AGG(applicant_name, ', ') AS owner_name,
                        STRING_AGG(mobile_no::TEXT, ', ') AS mobile_no, 
                        water_consumers.id AS water_id
                    FROM water_approval_applicants 
                    JOIN (
                        SELECT * 
                        FROM water_consumers
                        WHERE water_consumers.ulb_id = $ulbId
                        ORDER BY id
                        limit $limit offset $offset
                      )water_consumers ON water_consumers.apply_connection_id = water_approval_applicants.application_id
                        AND water_consumers.ulb_id = $ulbId
                    WHERE water_approval_applicants.status = true
                        " . ($wardId ? " AND water_consumers.ward_mstr_id = $wardId" : "") . "
                    GROUP BY water_consumers.id
                )water_owner_detail ON water_owner_detail.application_id = water_consumers.apply_connection_id
                LEFT JOIN (
                    SELECT water_consumer_demands.consumer_id,
                        SUM(
                                CASE WHEN water_consumer_demands.demand_from >= '$fromDate' 
                                AND water_consumer_demands.demand_upto <='$uptoDate' then water_consumer_demands.amount
                                    ELSE 0
                                    END
                        ) AS current_demand,
                        SUM(
                            CASE WHEN water_consumer_demands.demand_from <'$fromDate' then water_consumer_demands.amount
                                ELSE 0
                                END
                            ) AS arrear_demand,
                    SUM(water_consumer_demands.amount) AS total_demand
                    FROM water_consumer_demands
                    JOIN (
                        SELECT * 
                        FROM water_consumers
                        WHERE water_consumers.ulb_id = $ulbId
                        ORDER BY id
                        limit $limit offset $offset
                      )water_consumers ON water_consumers.id = water_consumer_demands.consumer_id
                    WHERE water_consumer_demands.status = true 
                        AND water_consumer_demands.ulb_id = $ulbId
                        " . ($wardId ? " AND water_consumers.ward_mstr_id = $wardId" : "") . "
                        AND water_consumer_demands.demand_upto <= '$uptoDate'
                    GROUP BY water_consumer_demands.consumer_id    
                )demands ON demands.consumer_id = water_consumers.id
                LEFT JOIN (
                    SELECT water_consumer_demands.consumer_id,
                        SUM(
                                CASE WHEN water_consumer_demands.demand_from >= '$fromDate' 
                                AND water_consumer_demands.demand_upto <='$uptoDate' then water_consumer_demands.amount
                                    ELSE 0
                                    END
                        ) AS current_collection,
                        SUM(
                            CASE when water_consumer_demands.demand_from < '$fromDate' then water_consumer_demands.amount
                                ELSE 0
                                END
                            ) AS arrear_collection,
                    SUM(water_consumer_demands.amount) AS total_collection
                    FROM water_consumer_demands
                    JOIN (
                        SELECT * 
                        FROM water_consumers
                        WHERE water_consumers.ulb_id = $ulbId
                        ORDER BY id
                        limit $limit offset $offset
                      )water_consumers ON water_consumers.id = water_consumer_demands.consumer_id
                    JOIN water_tran_details ON water_tran_details.related_id = water_consumer_demands.id 
                        AND water_tran_details.related_id is not null 
                    JOIN water_trans ON water_trans.id = water_tran_details.tran_id 
                        AND water_trans.status in (1,2) 
                        AND water_trans.related_id is not null
                        AND water_trans.tran_type = 'Demand Collection'
                    WHERE water_consumer_demands.status = true 
                        AND water_consumer_demands.ulb_id =$ulbId
                        " . ($wardId ? " AND water_consumers.ward_mstr_id = $wardId" : "") . "
                        AND water_trans.tran_date  BETWEEN '$fromDate' AND '$uptoDate'
                        AND water_consumer_demands.demand_upto <='$uptoDate'
                    GROUP BY water_consumer_demands.consumer_id
                )collection ON collection.consumer_id = water_consumers.id
                LEFT JOIN ( 
                    SELECT water_consumer_demands.consumer_id,
                    SUM(water_consumer_demands.amount) AS total_prev_collection
                    FROM water_consumer_demands
                    JOIN (
                        SELECT * 
                        FROM water_consumers
                        WHERE water_consumers.ulb_id = $ulbId
                        ORDER BY id
                        limit $limit offset $offset
                      )water_consumers ON water_consumers.id = water_consumer_demands.consumer_id
                    JOIN water_tran_details ON water_tran_details.application_id = water_consumer_demands.id 
                        AND water_tran_details.application_id is not null 
                    JOIN water_trans ON water_trans.id = water_tran_details.tran_id 
                        AND water_trans.status in (1,2) 
                        AND water_trans.related_id is not null
                        AND water_trans.tran_type = 'Demand Collection'
                    WHERE water_consumer_demands.status = true 
                        AND water_consumer_demands.ulb_id = $ulbId
                        " . ($wardId ? " AND water_consumers.ward_mstr_id = $wardId" : "") . "
                        AND water_trans.tran_date < '$fromDate'
                    GROUP BY water_consumer_demands.consumer_id
                )prev_collection ON prev_collection.consumer_id = water_consumers.id 
                JOIN ulb_ward_masters ON ulb_ward_masters.id = water_consumers.ward_mstr_id
                WHERE  water_consumers.ulb_id = $ulbId  
                    " . ($wardId ? " AND water_consumers.ward_mstr_id = $wardId" : "") . "           
            ";
            $footerfrom = "
                FROM water_consumers
                LEFT JOIN (
                    SELECT STRING_AGG(applicant_name, ', ') AS owner_name,
                        STRING_AGG(mobile_no::TEXT, ', ') AS mobile_no, 
                        water_consumers.id AS water_id
                    FROM water_approval_applicants 
                    JOIN water_consumers ON water_consumers.apply_connection_id = water_approval_applicants.application_id
                        AND water_consumers.ulb_id = $ulbId
                    WHERE water_approval_applicants.status = true
                        " . ($wardId ? " AND water_consumers.ward_mstr_id = $wardId" : "") . "
                    GROUP BY water_consumers.id
                )water_owner_detail ON water_owner_detail.application_id = water_consumers.apply_connection_id
                LEFT JOIN (
                    SELECT water_consumer_demands.consumer_id,
                        SUM(
                                CASE WHEN water_consumer_demands.demand_from >= '$fromDate' 
                                AND water_consumer_demands.demand_upto <='$uptoDate' then water_consumer_demands.amount
                                    ELSE 0
                                    END
                        ) AS current_demand,
                        SUM(
                            CASE WHEN water_consumer_demands.demand_from < '$fromDate' then water_consumer_demands.amount
                                ELSE 0
                                END
                            ) AS arrear_demand,
                    SUM(water_consumer_demands.amount) AS total_demand
                    FROM water_consumer_demands
                    JOIN water_consumers ON water_consumers.id = water_consumer_demands.consumer_id
                    WHERE water_consumer_demands.status = true 
                        AND water_consumer_demands.ulb_id =$ulbId
                        " . ($wardId ? " AND water_consumers.ward_mstr_id = $wardId" : "") . "
                        AND water_consumer_demands.demand_upto <= '$uptoDate'
                    GROUP BY water_consumer_demands.consumer_id    
                )demands ON demands.consumer_id = water_consumers.id
                LEFT JOIN (
                    SELECT water_consumer_demands.consumer_id,
                        SUM(
                                CASE WHEN water_consumer_demands.demand_from >= '$fromDate' 
                                AND water_consumer_demands.demand_upto <='$uptoDate' then water_consumer_demands.amount
                                ELSE 0
                                END
                        ) AS current_collection,
                        SUM(
                            CASE WHEN water_consumer_demands.demand_from < '$fromDate' then water_consumer_demands.amount
                                ELSE 0
                                END
                            ) AS arrear_collection,
                    SUM(water_consumer_demands.amount) AS total_collection
                    FROM water_consumer_demands
                    JOIN water_consumers ON water_consumers.id = water_consumer_demands.consumer_id

                                #####################------------#################

                    JOIN prop_tran_dtls ON prop_tran_dtls.prop_demand_id = prop_demands.id 
                        AND prop_tran_dtls.prop_demand_id is not null 
                    JOIN prop_transactions ON prop_transactions.id = prop_tran_dtls.tran_id 
                        AND prop_transactions.status in (1,2) AND prop_transactions.property_id is not null
                    WHERE prop_demands.status =1 
                        AND prop_demands.ulb_id =$ulbId
                        " . ($wardId ? " AND prop_properties.ward_mstr_id = $wardId" : "") . "
                        AND prop_transactions.tran_date  BETWEEN '$fromDate' AND '$uptoDate'
                        AND prop_demands.due_date<='$uptoDate'
                    GROUP BY prop_demands.property_id
                )collection ON collection.property_id = prop_properties.id
                LEFT JOIN ( 
                    SELECT prop_demands.property_id,
                    SUM(prop_demands.amount) AS total_prev_collection
                    FROM prop_demands
                    JOIN prop_properties ON prop_properties.id = prop_demands.property_id
                    JOIN prop_tran_dtls ON prop_tran_dtls.prop_demand_id = prop_demands.id 
                        AND prop_tran_dtls.prop_demand_id is not null 
                    JOIN prop_transactions ON prop_transactions.id = prop_tran_dtls.tran_id 
                        AND prop_transactions.status in (1,2) AND prop_transactions.property_id is not null
                    WHERE prop_demands.status =1 
                        AND prop_demands.ulb_id =$ulbId
                        " . ($wardId ? " AND prop_properties.ward_mstr_id = $wardId" : "") . "
                        AND prop_transactions.tran_date<'$fromDate'
                    GROUP BY prop_demands.property_id
                )prev_collection ON prev_collection.property_id = prop_properties.id 
                JOIN ulb_ward_masters ON ulb_ward_masters.id = prop_properties.ward_mstr_id
                WHERE  prop_properties.ulb_id = $ulbId  
                    " . ($wardId ? " AND prop_properties.ward_mstr_id = $wardId" : "") . "           
            ";
            $select = "SELECT  prop_properties.id,
                            ulb_ward_masters.ward_name AS ward_no,
                            CONCAT('', prop_properties.holding_no, '') AS holding_no,
                            (
                                CASE WHEN prop_properties.new_holding_no='' OR prop_properties.new_holding_no IS NULL THEN 'N/A' 
                                ELSE prop_properties.new_holding_no END
                            ) AS new_holding_no,
                            prop_owner_detail.owner_name,
                            prop_owner_detail.mobile_no,
                    
                            COALESCE(
                                COALESCE(demands.arrear_demand, 0::numeric) 
                                - COALESCE(prev_collection.total_prev_collection, 0::numeric), 0::numeric
                            ) AS arrear_demand,
                            COALESCE(demands.current_demand, 0::numeric) AS current_demand,   
                            COALESCE(prev_collection.total_prev_collection, 0::numeric) AS previous_collection,
                            
                            COALESCE(collection.arrear_collection, 0::numeric) AS arrear_collection,
                            COALESCE(collection.current_collection, 0::numeric) AS current_collection,
                    
                            (COALESCE(
                                    COALESCE(demands.arrear_demand, 0::numeric) 
                                    - COALESCE(prev_collection.total_prev_collection, 0::numeric), 0::numeric
                                ) 
                                - COALESCE(collection.arrear_collection, 0::numeric) )AS old_due,
                    
                            (COALESCE(demands.current_demand, 0::numeric) - COALESCE(collection.current_collection, 0::numeric)) AS current_due,
                    
                            (
                                COALESCE(
                                    COALESCE(demands.current_demand, 0::numeric) 
                                    + (
                                        COALESCE(demands.arrear_demand, 0::numeric) 
                                        - COALESCE(prev_collection.total_prev_collection, 0::numeric)
                                    ), 0::numeric
                                ) 
                                - COALESCE(
                                    COALESCE(collection.current_collection, 0::numeric) 
                                    + COALESCE(collection.arrear_collection, 0::numeric), 0::numeric
                                )
                            ) AS outstanding                                 
            ";
            $footerselect = "SELECT
                        COUNT(prop_properties.id)AS total_prop,
                        COUNT(DISTINCT(ulb_ward_masters.ward_name)) AS total_ward,
                        SUM(
                            COALESCE(
                                COALESCE(demands.arrear_demand, 0::numeric) 
                                - COALESCE(prev_collection.total_prev_collection, 0::numeric), 0::numeric
                            )
                        ) AS outstanding_at_begin,
                        SUM(COALESCE(demands.currend_demand, 0::numeric)) AS current_demand,
                
                        SUM(COALESCE(prev_collection.total_prev_collection, 0::numeric)) AS prev_coll,
                        SUM(COALESCE(collection.arrear_collection, 0::numeric)) AS arrear_ollection,
                        SUM(COALESCE(collection.current_collection, 0::numeric)) AS current_collection,
                
                        SUM(
                            (
                                COALESCE(
                                    COALESCE(demands.arrear_demand, 0::numeric) 
                                    - COALESCE(prev_collection.total_prev_collection, 0::numeric), 0::numeric
                                ) 
                                - COALESCE(collection.arrear_collection, 0::numeric) 
                            )
                        )AS old_due,
                
                        SUM((COALESCE(demands.current_demand, 0::numeric) - COALESCE(collection.current_collection, 0::numeric))) AS current_due,
                
                        SUM(
                            (
                                COALESCE(
                                    COALESCE(demands.current_demand, 0::numeric) 
                                    + (
                                        COALESCE(demands.arrear_demand, 0::numeric) 
                                        - COALESCE(prev_collection.total_prev_collection, 0::numeric)
                                    ), 0::numeric
                                ) 
                                - COALESCE(
                                    COALESCE(collection.currend_collection, 0::numeric) 
                                    + COALESCE(collection.arrear_collection, 0::numeric), 0::numeric
                                )
                            )
                        ) AS outstanding               
            ";
            $data = DB::TABLE(DB::RAW("($select $from)AS prop"))->get();
            // $footer = DB::TABLE(DB::RAW("($footerselect $footerfrom)AS prop"))->get();
            $items = $data;
            $total = (collect(DB::SELECT("SELECT COUNT(*) AS total $footerfrom"))->first())->total ?? 0;
            $lastPage = ceil($total / $perPage);
            $list = [
                // "perPage" => $perPage,
                // "page" => $page,
                // "items" => $items,
                // "footer" => $footer,
                // "total" => $total,
                // "numberOfPages" => $numberOfPages,
                "current_page" => $page,
                "data" => $data,
                "total" => $total,
                "per_page" => $perPage,
                "last_page" => $lastPage - 1
            ];
            $queryRunTime = (collect(DB::getQueryLog())->sum("time"));
            return responseMsgs(true, "", $list, $apiId, $version, $queryRunTime, $action, $deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $request->all(), $apiId, $version, $queryRunTime, $action, $deviceId);
        }
    }

    /**
     * | Ward Wise Dcb 
        | Serial No : 03
        | Working
        | Not Verified
     */
    public function wardWiseDCB(Request $request)
    {
        $request->validate(
            [
                "fiYear" => "nullable|regex:/^\d{4}-\d{4}$/",
                "ulbId" => "nullable|digits_between:1,9223372036854775807",
                "wardId" => "nullable|digits_between:1,9223372036854775807",
                "connectionType" => "nullable|in:1,2",
                "propType" => "nullable|in:1,2,3"
                // "page" => "nullable|digits_between:1,9223372036854775807",
                // "perPage" => "nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->request->add(["metaData" => ["", 1.1, "", $request->getMethod(), $request->deviceId,]]);
        $metaData = collect($request->metaData)->all();
        list($apiId, $version, $queryRunTime, $action, $deviceId) = $metaData;

        try {
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $ulbId          = $refUser->ulb_id;
            $wardId = null;
            $connectionType = array();
            $refPropType = Config::get('waterConstaint.PROPERTY_TYPE');

            $fiYear = getFY();
            if ($request->fiYear) {
                $fiYear = $request->fiYear;
            }
            list($fromYear, $toYear) = explode("-", $fiYear);
            if ($toYear - $fromYear != 1) {
                throw new Exception("Enter Valide Financial Year");
            }
            $fromDate = $fromYear . "-04-01";
            $uptoDate = $toYear . "-03-31";
            if ($request->ulbId) {
                $ulbId = $request->ulbId;
            }
            if ($request->wardId) {
                $wardId = $request->wardId;
            }
            if ($request->connectionType) {
                switch ($request->connectionType) {
                    case ('1'):
                        $connectionType = ['Meter', 'Metered'];
                        break;

                    case ('2'):
                        $connectionType = ['Fixed'];
                        break;
                }
            }
            if ($request->connectionType) {
                switch ($request->connectionType) {
                    case ('1'):
                        $propType = [
                            $refPropType['Residential']
                        ];
                        break;
                    case ('2'):
                        $propType = [
                            $refPropType['Commercial']
                        ];
                        break;
                    case ('3'):
                        $propType = [
                            $refPropType['Government']
                        ];
                        break;
                }
            }

            # From Querry
            $from = "
                FROM ulb_ward_masters 
                LEFT JOIN(
                        SELECT water_consumers.ward_mstr_id,
                        COUNT
                        (DISTINCT (
                            CASE WHEN water_consumer_demands.demand_from >= '$fromDate' 
                                AND water_consumer_demands.demand_upto <= '$uptoDate'  then water_consumer_demands.consumer_id
                            END)
                        ) as current_demand_consumer,
                        SUM(
                                CASE WHEN  water_consumer_demands.demand_from >= '$fromDate' 
                                        AND water_consumer_demands.demand_upto <= '$uptoDate'  then water_consumer_demands.amount
                                ELSE 0
                                    END
                        ) AS current_demand,
                        COUNT
                        (DISTINCT (
                            CASE WHEN water_consumer_demands.demand_from < '$fromDate'  then water_consumer_demands.consumer_id
                            END)
                        ) as arrear_demand_consumer,
                        SUM(
                            CASE WHEN water_consumer_demands.demand_from < '$fromDate'  then water_consumer_demands.amount
                                ELSE 0
                                END
                            ) AS arrear_demand,
                        SUM(water_consumer_demands.amount) AS total_demand
                FROM water_consumer_demands
                JOIN water_consumers ON water_consumers.id = water_consumer_demands.consumer_id
                WHERE water_consumer_demands.status = true
                    AND water_consumer_demands.ulb_id = $ulbId
                    " . ($wardId ? " AND water_consumers.ward_mstr_id = $wardId" : "") . "
                    " . ($connectionType ? " AND water_consumer_demands.connection_type IN ($connectionType)" : "") . "
                    " . ($propType ? " AND water_consumers.property_type_id = $propType" : "") . "
                    AND water_consumer_demands.demand_upto <='$uptoDate'
                GROUP BY water_consumers.ward_mstr_id
                )demands ON demands.ward_mstr_id = ulb_ward_masters.id
                LEFT JOIN (
                    SELECT water_consumers.ward_mstr_id,
                    COUNT
                        (DISTINCT (
                            CASE WHEN water_consumer_demands.demand_from >= '$fromDate' 
                                AND water_consumer_demands.demand_upto <= '$uptoDate'  then water_consumer_demands.consumer_id
                            END)
                        ) as current_collection_consumer,

                        COUNT(DISTINCT(water_consumers.id)) AS collection_from_no_of_consumer,
                        SUM(
                                CASE WHEN water_consumer_demands.demand_from >= '$fromDate' 
                                AND water_consumer_demands.demand_upto <= '$uptoDate'  then water_consumer_demands.amount
                                    ELSE 0
                                    END
                        ) AS current_collection,

                        COUNT
                            (DISTINCT (
                                CASE WHEN water_consumer_demands.demand_from < '$fromDate' then water_consumer_demands.consumer_id
                                END)
                            ) as arrear_collection_consumer,

                        SUM(
                            CASE when water_consumer_demands.demand_from < '$fromDate' then water_consumer_demands.amount
                                ELSE 0
                                END
                            ) AS arrear_collection,
                            
                    SUM(water_consumer_demands.amount) AS total_collection
                    FROM water_consumer_demands
                    JOIN water_consumers ON water_consumers.id = water_consumer_demands.consumer_id
                    JOIN water_tran_details ON water_tran_details.demand_id = water_consumer_demands.id 
                        AND water_tran_details.demand_id is not null 
                    JOIN water_trans ON water_trans.id = water_tran_details.tran_id 
                        AND water_trans.status in (1,2) 
								AND water_trans.related_id is not null
								AND water_trans.tran_type = 'Demand Collection'
                    WHERE water_consumer_demands.status = true 
                        AND water_consumer_demands.ulb_id = $ulbId
                        " . ($wardId ? " AND water_consumers.ward_mstr_id = $wardId" : "") . "
                        " . ($connectionType ? " AND water_consumer_demands.connection_type IN ($connectionType)" : "") . "
                        " . ($propType ? " AND water_consumers.property_type_id = $propType" : "") . "
                        AND water_trans.tran_date  BETWEEN '$fromDate' AND '$uptoDate'
                        AND water_consumer_demands.demand_from <='$fromDate'
                    GROUP BY (water_consumers.ward_mstr_id)
                )collection ON collection.ward_mstr_id = ulb_ward_masters.id
                LEFT JOIN ( 
                    SELECT water_consumers.ward_mstr_id, 
                        SUM(water_consumer_demands.amount) 
                                AS total_prev_collection
                                FROM water_consumer_demands
                        JOIN water_consumers ON water_consumers.id = water_consumer_demands.consumer_id
                        JOIN water_tran_details ON water_tran_details.demand_id = water_consumer_demands.id
                            AND water_tran_details.demand_id IS NOT NULL
                        JOIN water_trans ON water_trans.id = water_tran_details.tran_id 
                            AND water_trans.status in (1,2) 
                            AND water_trans.related_id IS NOT NULL
                            AND water_trans.tran_type = 'Demand Collection'
                    WHERE water_consumer_demands.status = true 
                        AND water_consumer_demands.ulb_id = $ulbId
                        " . ($wardId ? " AND ulb_ward_masters.id = $wardId" : "") . "
                        " . ($connectionType ? " AND water_consumer_demands.connection_type IN ($connectionType)" : "") . "
                        " . ($propType ? " AND water_consumers.property_type_id = $propType" : "") . "
                       AND water_trans.tran_date <'$fromDate'
                    GROUP BY water_consumers.ward_mstr_id  
                )prev_collection ON prev_collection.ward_mstr_id = ulb_ward_masters.id                 
                WHERE  ulb_ward_masters.ulb_id = $ulbId  
                    " . ($wardId ? " AND ulb_ward_masters.id = $wardId" : "") . "
                    " . ($connectionType ? " AND water_consumer_demands.connection_type IN ($connectionType)" : "") . "
                    " . ($propType ? " AND water_consumers.property_type_id = $propType" : "") . "
                GROUP BY ulb_ward_masters.ward_name           
            ";

            # Select Querry
            $select = "SELECT ulb_ward_masters.ward_name AS ward_no, 
                            SUM(COALESCE(demands.current_demand_consumer, 0::numeric)) AS current_demand_consumer,   
                            SUM(COALESCE(demands.arrear_demand_consumer, 0::numeric)) AS arrear_demand_consumer,
                            SUM(COALESCE(collection.current_collection_consumer, 0::numeric)) AS current_collection_consumer,   
                            SUM(COALESCE(collection.arrear_collection_consumer, 0::numeric)) AS arrear_collection_consumer,
                            SUM(COALESCE(collection.collection_from_no_of_consumer, 0::numeric)) AS collection_from_consumer,
                            
                            round(SUM(((collection.arrear_collection_consumer ::numeric) / (case when demands.arrear_demand_consumer > 0 then demands.arrear_demand_consumer else 1 end))*100)) AS arrear_consumer_eff,
                            round(SUM(((collection.current_collection_consumer ::numeric) / (case when demands.current_demand_consumer > 0 then demands.current_demand_consumer else 1 end))*100)) AS current_consumer_eff,

                            round(SUM(COALESCE(
                                COALESCE(demands.current_demand_consumer, 0::numeric) 
                                - COALESCE(collection.collection_from_no_of_consumer, 0::numeric), 0::numeric
                            ))) AS balance_consumer,                       
                            round(SUM(COALESCE(
                                COALESCE(demands.arrear_demand, 0::numeric) 
                                - COALESCE(prev_collection.total_prev_collection, 0::numeric), 0::numeric
                            ))) AS arrear_demand,
                    
                            round(SUM(COALESCE(prev_collection.total_prev_collection, 0::numeric))) AS previous_collection,
                            round(SUM(COALESCE(demands.current_demand, 0::numeric))) AS current_demand,
                            round(SUM(COALESCE(collection.arrear_collection, 0::numeric))) AS arrear_collection,
                            round(SUM(COALESCE(collection.current_collection, 0::numeric))) AS current_collection,
                    
                            round(SUM((COALESCE(
                                    COALESCE(demands.arrear_demand, 0::numeric) 
                                    - COALESCE(prev_collection.total_prev_collection, 0::numeric), 0::numeric
                                ) 
                                - COALESCE(collection.arrear_collection, 0::numeric) 
                                )))AS old_due,
                    
                            round(SUM((COALESCE(demands.current_demand, 0::numeric) - COALESCE(collection.current_collection, 0::numeric)))) AS current_due,

                            round(SUM((COALESCE(demands.current_demand_consumer, 0::numeric) - COALESCE(collection.current_collection_consumer, 0::numeric)))) AS current_balance_consumer,
                            round(SUM((COALESCE(demands.arrear_demand_consumer, 0::numeric) - COALESCE(collection.arrear_collection_consumer, 0::numeric)))) AS arrear_balance_consumer,

                            round(SUM(((collection.arrear_collection ::numeric) / (case when demands.arrear_demand > 0 then demands.arrear_demand else 1 end))*100)) AS arrear_eff,
                            round(SUM(((collection.current_collection ::numeric) / (case when demands.current_demand > 0 then demands.current_demand else 1 end))*100)) AS current_eff,

                            round(SUM((
                                COALESCE(
                                    COALESCE(demands.current_demand, 0::numeric) 
                                    + (
                                        COALESCE(demands.arrear_demand, 0::numeric) 
                                        - COALESCE(prev_collection.total_prev_collection, 0::numeric)
                                    ), 0::numeric
                                ) 
                                - COALESCE(
                                    COALESCE(collection.current_collection, 0::numeric) 
                                    + COALESCE(collection.arrear_collection, 0::numeric), 0::numeric
                                )
                            ))) AS outstanding                                 
            ";

            # Data Structuring
            $dcb = DB::select($select . $from);
            $data['total_arrear_demand']                = round(collect($dcb)->sum('arrear_demand'), 0);
            $data['total_current_demand']               = round(collect($dcb)->sum('current_demand'), 0);
            $data['total_arrear_collection']            = round(collect($dcb)->sum('arrear_collection'), 0);
            $data['total_current_collection']           = round(collect($dcb)->sum('current_collection'), 0);
            $data['total_old_due']                      = round(collect($dcb)->sum('old_due'), 0);
            $data['total_current_due']                  = round(collect($dcb)->sum('current_due'), 0);
            $data['total_arrear_demand_consumer']       = round(collect($dcb)->sum('arrear_demand_consumer'), 0);
            $data['total_current_demand_consumer']      = round(collect($dcb)->sum('current_demand_consumer'), 0);
            $data['total_arrear_collection_consumer']   = round(collect($dcb)->sum('arrear_collection_consumer'), 0);
            $data['total_current_collection_consumer']  = round(collect($dcb)->sum('current_collection_consumer'), 0);
            $data['total_arrear_balance_consumer']      = round(collect($dcb)->sum('arrear_balance_consumer'));
            $data['total_current_balance_consumer']     = round(collect($dcb)->sum('current_balance_consumer'));
            $data['total_current_eff']                  = ($data['total_current_collection_consumer'] == 0) ? 0 : round(($data['total_current_collection_consumer'] / $data['total_current_demand']) * 100);
            $data['total_arrear_consumer_eff']          = ($data['total_arrear_demand_consumer'] == 0) ? 0 : round(($data['total_arrear_collection_consumer'] /  $data['total_arrear_demand_consumer']) * 100);
            $data['total_current_consumer_eff']         = ($data['total_current_demand_consumer'] == 0) ? 0 : round(($data['total_current_collection_consumer']) / ($data['total_current_demand_consumer']) * 100);
            $data['total_arrear_eff']                   = ($data['total_arrear_collection'] == 0) ? 0 : round(($data['total_arrear_collection']) / ($data['total_arrear_demand']) * 100);
            $data['total_eff']                          = round((($data['total_arrear_collection'] + $data['total_current_collection']) / ($data['total_arrear_demand'] + $data['total_current_demand'])) * 100);
            $data['dcb']                                = $dcb;

            $queryRunTime = (collect(DB::getQueryLog())->sum("time"));
            return responseMsgs(true, "", $data, $apiId, $version, $queryRunTime, $action, $deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $request->all(), $apiId, $version, $queryRunTime, $action, $deviceId);
        }
    }


    /**
     * | DCB Pie Chart
        | Serial No : 04
        | Working
     */
    public function dcbPieChart(Request $request)
    {
        try {
            $ulbId = $request->ulbId ?? authUser()->ulb_id;
            $currentDate = Carbon::now()->format('Y-m-d');
            $currentYear = Carbon::now()->year;
            $currentFyear = getFinancialYear($currentDate);
            $startOfCurrentYear = Carbon::createFromDate($currentYear, 4, 1);   // Start date of current financial year
            $startOfPreviousYear = $startOfCurrentYear->copy()->subYear();      // Start date of previous financial year
            $previousFinancialYear = getFinancialYear($startOfPreviousYear);
            $startOfprePreviousYear = $startOfCurrentYear->copy()->subYear()->subYear();
            $prePreviousFinancialYear = getFinancialYear($startOfprePreviousYear);


            # common function
            $refDate = $this->getFyearDate($currentFyear);
            $fromDate = $refDate['fromDate'];
            $uptoDate = $refDate['uptoDate'];

            # common function
            $refDate = $this->getFyearDate($previousFinancialYear);
            $previousFromDate = $refDate['fromDate'];
            $previousUptoDate = $refDate['uptoDate'];

            # common function
            $refDate = $this->getFyearDate($prePreviousFinancialYear);
            $prePreviousFromDate = $refDate['fromDate'];
            $prePreviousUptoDate = $refDate['uptoDate'];


            $sql1 = $this->demandByFyear($currentFyear, $fromDate, $uptoDate, $ulbId);
            $sql2 = $this->demandByFyear($previousFinancialYear, $previousFromDate, $previousUptoDate, $ulbId);
            $sql3 = $this->demandByFyear($prePreviousFinancialYear, $prePreviousFromDate, $prePreviousUptoDate, $ulbId);

            $currentYearDcb     = DB::select($sql1);
            $previousYearDcb    = DB::select($sql2);
            $prePreviousYearDcb = DB::select($sql3);

            $data = [
                collect($currentYearDcb)->first(),
                collect($previousYearDcb)->first(),
                collect($prePreviousYearDcb)->first()
            ];
            return responseMsgs(true, "", remove_null($data), "", "", "", 'POST', "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "", "01", ".ms", "POST", $request->deviceId);
        }
    }

    /**
     * | for collecting finantial year's starting date and end date
     * | common functon
     * | @param fyear
        | Serial No : 04.01
        | Working
     */
    public function getFyearDate($fyear)
    {
        list($fromYear, $toYear) = explode("-", $fyear);
        if ($toYear - $fromYear != 1) {
            throw new Exception("Enter Valide Financial Year");
        }
        $fromDate = $fromYear . "-04-01";
        $uptoDate = $toYear . "-03-31";
        return [
            "fromDate" => $fromDate,
            "uptoDate" => $uptoDate
        ];
    }


    
    /**
     * | Water collection Report
     */
    public function WaterCollection(Request $request)
    {
        $consumerCollection = null;
        $applicationCollection = null;
        $consumerData = 0;
        $consumerTotal = 0;
        $applicationTotal = 0;
        $applicationData = 0;
        $collectionTypes = $request->collectionType;
        $perPage = $request->perPage ?? 5;

        if ($request->user == 'tc') {
            $userId = authUser()->id;
            $request->merge(["userId" => $userId]);
        }

        foreach ($collectionTypes as $collectionType) {
            if ($collectionType == 'connection') {
                $consumerCollection = $this->collectionReport($request);
                $consumerTotal = $consumerCollection->original['data']['totalAmount'];
                $consumerData = $consumerCollection->original['data']['total'];
                $consumerCollection = $consumerCollection->original['data']['data'];
            }

            if ($collectionType == 'consumer') {
                $applicationCollection = $this->applicationCollection($request);
                $applicationTotal = $applicationCollection->original['data']['totalAmount'];
                $applicationData = $applicationCollection->original['data']['total'];
                $applicationCollection = $applicationCollection->original['data']['data'];
            }

            // if ($collectionType == 'gbsaf') {
            //     $Dont__USE = $this->Dont__USE($request);
            //     $NOT__USE = $Dont__USE->toarray()['total'];
            //     $Dont__USE = $Dont__USE->toarray()['data'];
            //     $gbapplicationTotal = collect($Dont__USE)->sum('amount');
            // }
        }
        $currentPage = $request->page ?? 1;
        $details = collect($consumerCollection)->merge($applicationCollection);

        $a = round($consumerData / $perPage);
        $b = round($applicationData / $perPage);
        $data['current_page'] = $currentPage;
        $data['total'] = $consumerData + $applicationData;
        $data['totalAmt'] = round($consumerTotal + $applicationTotal);
        $data['last_page'] = max($a, $b);
        $data['data'] = $details;

        return responseMsgs(true, "", $data, "", "", "", "post", $request->deviceId);
    }



        /**
     * | Property Collection
     */
    public function collectionReport(Request $request)
    {
        $request->validate(
            [
                "fromDate"      => "required|date|date_format:Y-m-d",
                "uptoDate"      => "required|date|date_format:Y-m-d",
                "wardId"        => "nullable|digits_between:1,9223372036854775807",
                "userId"        => "nullable|digits_between:1,9223372036854775807",
                "paymentMode"   => "nullable",
                "page"          => "nullable|digits_between:1,9223372036854775807",
                "perPage"       => "nullable|digits_between:1,9223372036854775807",
            ]
        );
        $metaData = collect($request->metaData)->all();

        list($apiId, $version, $queryRunTime, $action, $deviceId) = $metaData;
        try {
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $ulbId          = $refUser->ulb_id;
            $wardId = null;
            $userId = null;
            $paymentMode = null;
            $fromDate = $uptoDate = Carbon::now()->format("Y-m-d");

            if ($request->fromDate) {
                $fromDate = $request->fromDate;
            }
            if ($request->uptoDate) {
                $uptoDate = $request->uptoDate;
            }
            if ($request->wardId) {
                $wardId = $request->wardId;
            }
            if ($request->userId) {
                $userId = $request->userId;
            }
            if ($request->paymentMode) {
                $paymentMode = $request->paymentMode;
            }
            if ($request->ulbId) {
                $ulbId = $request->ulbId;
            }

            // DB::enableQueryLog();
            $data = WaterTran::SELECT(
                DB::raw("
                            ulb_ward_masters.ward_name AS ward_no,
                            water_consumers.id,
                            water_consumers.consumer_no,
                            water_trans.id AS tran_id,
                            CONCAT('', water_consumers.holding_no, '') AS holding_no,
                            water_consumer_owners.applicant_name AS owner_name,
                            water_consumer_owners.mobile_no,
                                                // CONCAT(
                                                //     water_trans.from_fyear, '(', prop_transactions.from_qtr, ')', ' / ', 
                                                //     water_trans.to_fyear, '(', prop_transactions.to_qtr, ')'
                                                // ) AS from_upto_fy_qtr,
                            water_trans.tran_date,
                            water_trans.payment_mode AS transaction_mode,
                            water_trans.amount,
                            users.user_name as emp_name,
                            users.id as user_id,
                            water_trans.tran_no,
                            water_cheque_dtls.cheque_no,
                            water_cheque_dtls.bank_name,
                            water_cheque_dtls.branch_name
                "),
            )
                ->JOIN("water_consumers", "water_consumers.id", "water_trans.related_id")
                ->JOIN(
                    DB::RAW("(
                        SELECT STRING_AGG(owner_name, ', ') AS owner_name, 
                        STRING_AGG(mobile_no::TEXT, ', ') AS mobile_no, 
                        water_consumer_owner.property_id 
                        FROM prop_owners 
                        JOIN prop_transactions on prop_transactions.property_id = prop_owners.property_id 
                        WHERE prop_transactions.property_id IS NOT NULL AND prop_transactions.status in (1, 2) 
                        AND prop_transactions.tran_date BETWEEN '$fromDate' AND '$uptoDate'
                        " .
                        ($userId ? " AND prop_transactions.user_id = $userId " : "")
                        . ($paymentMode ? " AND upper(prop_transactions.payment_mode) = upper('$paymentMode') " : "")
                        . ($ulbId ? " AND prop_transactions.ulb_id = $ulbId" : "")
                        . "
                        GROUP BY prop_owners.property_id
                        ) AS prop_owner_detail
                        "),
                    function ($join) {
                        $join->on("prop_owner_detail.property_id", "=", "prop_transactions.property_id");
                    }
                )
                ->JOIN("ulb_ward_masters", "ulb_ward_masters.id", "prop_properties.ward_mstr_id")
                ->LEFTJOIN("users", "users.id", "prop_transactions.user_id")
                ->LEFTJOIN("prop_cheque_dtls", "prop_cheque_dtls.transaction_id", "prop_transactions.id")
                ->WHERENOTNULL("prop_transactions.property_id")
                ->WHEREIN("prop_transactions.status", [1, 2])
                ->WHEREBETWEEN("prop_transactions.tran_date", [$fromDate, $uptoDate]);
            if ($wardId) {
                $data = $data->where("ulb_ward_masters.id", $wardId);
            }
            if ($userId) {
                $data = $data->where("prop_transactions.user_id", $userId);
            }
            if ($paymentMode) {
                $data = $data->where(DB::raw("upper(prop_transactions.payment_mode)"), $paymentMode);
            }
            if ($ulbId) {
                $data = $data->where("prop_transactions.ulb_id", $ulbId);
            }
            $paginator = collect();

            $data2 = $data;
            $totalHolding = $data2->count("prop_properties.id");
            $totalAmount = $data2->sum("prop_transactions.amount");
            $perPage = $request->perPage ? $request->perPage : 5;
            $page = $request->page && $request->page > 0 ? $request->page : 1;

            $paginator = $data->paginate($perPage);

            // $items = $paginator->items();
            // $total = $paginator->total();
            // $numberOfPages = ceil($total / $perPage);
            $list = [
                "current_page" => $paginator->currentPage(),
                "last_page" => $paginator->lastPage(),
                "totalHolding" => $totalHolding,
                "totalAmount" => $totalAmount,
                "data" => $paginator->items(),
                "total" => $paginator->total(),
                // "numberOfPages" => $numberOfPages
            ];
            $queryRunTime = (collect(DB::getQueryLog())->sum("time"));
            return responseMsgs(true, "", $list, $apiId, $version, $queryRunTime, $action, $deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $request->all(), $apiId, $version, $queryRunTime, $action, $deviceId);
        }
    }
}
