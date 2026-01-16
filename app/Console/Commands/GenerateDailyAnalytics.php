<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GenerateDailyAnalytics extends Command
{
    protected $signature = 'dashboard:daily-analytics {--from=}';
    protected $description = 'Generate analytical dashboard data per ULB (daily)';

    /* =========================================================
       HANDLE
    ========================================================= */
    public function handle()
    {
        $fromInput = $this->option('from');

        /* -----------------------------
           Decide start date
        ----------------------------- */
        if ($fromInput) {

            // MANUAL BACKFILL MODE
            $startDate = Carbon::parse($fromInput)->startOfDay();
            $this->info("Manual backfill from: " . $startDate->toDateString());

        } else {

            // AUTO DAILY MODE
            $lastDate = DB::connection('pgsql_reports')
                ->table('tbl_analytical_dhashboards')
                ->max('report_date');

            if ($lastDate) {
                $startDate = Carbon::parse($lastDate)->addDay()->startOfDay();
                $this->info("Auto mode from last processed date: " . $startDate->toDateString());
            } else {
                $earliest = $this->getEarliestTransactionDate();
                $startDate = Carbon::parse($earliest)->startOfDay();
                $this->info("First run from earliest data: " . $startDate->toDateString());
            }
        }

        // Never include today
        $endDate = Carbon::yesterday()->endOfDay();

        if ($startDate->gt($endDate)) {
            $this->info("No pending dates to process.");
            return;
        }

        /* -----------------------------
           Process each date
        ----------------------------- */
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {

            $this->info("Processing date: " . $date->toDateString());

            try {
                $this->processSingleDate($date);
            } catch (\Exception $e) {
                $this->error("Failed on {$date->toDateString()}: " . $e->getMessage());
                throw $e;
            }
        }

        $this->info("Analytics generation completed.");
    }

    /* =========================================================
       PROCESS SINGLE DATE
    ========================================================= */
    private function processSingleDate(Carbon $date)
    {
        $from = $date->copy()->startOfDay();
        $to   = $date->copy()->endOfDay();
        $financialYear = $this->getFYfromDate($date);

        $activeUlbs = $this->getActiveUlbs($from, $to);

        if ($activeUlbs->isEmpty()) return;

        DB::connection('pgsql_reports')->beginTransaction();

        try {

            foreach ($activeUlbs as $ulb) {

                $ulbId = $ulb->ulb_id;

                $property = $this->propertyTotals($ulbId, $from, $to);
                $water = $this->waterTotals($ulbId, $from, $to);
                $swm = $this->swmTotals($ulbId, $from, $to);
                $fines = $this->finesTotals($ulbId, $from, $to);
                $rig = $this->rigTotals($ulbId, $from, $to);
                $waterTanker = $this->waterTankerTotals($ulbId, $from, $to);
                $septicTanker = $this->septicTankerTotals($ulbId, $from, $to);
                $pet = $this->petTotals($ulbId, $from, $to);
                $advertisement = $this->advertisementTotals($ulbId, $from, $to);
                $trade = $this->tradeTotals($ulbId, $from, $to);
                $municipalRental = $this->municipalRentalTotals($ulbId, $from, $to);
                $lodge = $this->lodgeTotals($ulbId, $from, $to);

                // Skip pure zero rows
                $hasData =
                    array_sum([
                        $property['collection'], $property['count'],
                        $water['collection'], $water['count'],
                        $swm['collection'], $swm['count'],
                        $fines['collection'], $fines['count'],
                        $rig['collection'], $rig['count'],
                        $waterTanker['collection'], $waterTanker['count'],
                        $septicTanker['collection'], $septicTanker['count'],
                        $pet['collection'], $pet['count'],
                        $advertisement['collection'], $advertisement['count'],
                        $trade['collection'], $trade['count'],
                        $municipalRental['collection'], $municipalRental['count'],
                        $lodge['collection'], $lodge['count'],
                    ]) > 0;

                if (!$hasData) continue;

                $row = [
                    'financial_year' => $financialYear,
                    'report_date' => $date->toDateString(),
                    'ulb_id' => $ulbId,
                    'ulb_name' => $ulb->ulb_name,

                    'property_total_collection' => $property['collection'],
                    'property_total_application' => $property['count'],

                    'water_total_collection' => $water['collection'],
                    'water_total_registration' => $water['count'],

                    'swm_total_collection' => $swm['collection'],
                    'swm_total_registration' => $swm['count'],

                    'fines_total_collection' => $fines['collection'],
                    'fines_total_challan_generated' => $fines['count'],

                    'rig_total_collection' => $rig['collection'],
                    'rig_total_registration' => $rig['count'],

                    'water_tanker_total_collection' => $waterTanker['collection'],
                    'water_tanker_total_booking' => $waterTanker['count'],

                    'septic_tanker_total_collection' => $septicTanker['collection'],
                    'septic_tanker_total_booking' => $septicTanker['count'],

                    'pet_total_collection' => $pet['collection'],
                    'pet_total_registration' => $pet['count'],

                    'advertisement_total_collection' => $advertisement['collection'],
                    'advertisement_total_registration' => $advertisement['count'],

                    'trade_total_collection' => $trade['collection'],
                    'trade_total_registration' => $trade['count'],

                    'municipal_rental_total_collection' => $municipalRental['collection'],
                    'municipal_rental_total_shops' => $municipalRental['count'],

                    'lodge_total_collection' => $lodge['collection'],
                    'lodge_total_registration' => $lodge['count'],

                    'updated_at' => now(),
                    'created_at' => now(),
                ];

                DB::connection('pgsql_reports')
                    ->table('tbl_analytical_dhashboards')
                    ->upsert(
                        [$row],
                        ['financial_year', 'ulb_id', 'report_date'],
                        array_keys($row)
                    );
            }

            DB::connection('pgsql_reports')->commit();

        } catch (\Exception $e) {

            DB::connection('pgsql_reports')->rollBack();
            throw $e;
        }
    }

    /* =========================================================
       HELPERS
    ========================================================= */

    private function getFYfromDate($date)
    {
        $year = $date->year;
        return $date->month < 4 ? ($year - 1) . '-' . $year : $year . '-' . ($year + 1);
    }

    private function getEarliestTransactionDate()
    {
        $dates = collect([
            DB::table('prop_transactions')->min('created_at'),
            DB::connection('pgsql_water')->table('water_trans')->min('created_at'),
            DB::connection('pgsql_tanker')->table('wt_transactions')->min('created_at'),
            DB::connection('pgsql_tanker')->table('st_transactions')->min('created_at'),
            DB::connection('pgsql_fines')->table('penalty_transactions')->min('created_at'),
            DB::connection('pgsql_fines')->table('rig_trans')->min('created_at'),
            DB::connection('pgsql_trade')->table('trade_transactions')->min('created_at'),
            DB::connection('pgsql_advertisements')->table('adv_mar_transactions')->min('created_at'),
            DB::connection('pgsql_advertisements')->table('pet_trans')->min('created_at'),
            DB::connection('pgsql_advertisements')->table('marriage_transactions')->min('created_at'),
            DB::connection('pgsql_advertisements')->table('mar_toll_payments')->min('created_at'),
            DB::connection('pgsql_advertisements')->table('mar_shop_payments')->min('created_at'),
            DB::connection('pgsql_swm')->table('swm_transactions')->min('stampdate'),
        ]);

        return $dates->filter()->min();
    }

    /* =========================================================
       ACTIVE ULBs
    ========================================================= */
    private function getActiveUlbs($from, $to)
    {
        $ulbIds = collect()

            ->merge(DB::table('prop_transactions')->where('status', 1)->whereBetween('created_at', [$from, $to])->pluck('ulb_id'))
            ->merge(DB::connection('pgsql_water')->table('water_trans')->where('status', 1)->whereBetween('created_at', [$from, $to])->pluck('ulb_id'))
            ->merge(DB::connection('pgsql_tanker')->table('wt_transactions')->where('status', 1)->whereBetween('created_at', [$from, $to])->pluck('ulb_id'))
            ->merge(DB::connection('pgsql_tanker')->table('st_transactions')->where('status', 1)->whereBetween('created_at', [$from, $to])->pluck('ulb_id'))
            ->merge(DB::connection('pgsql_fines')->table('penalty_transactions')->where('status', 1)->whereBetween('created_at', [$from, $to])->pluck('ulb_id'))
            ->merge(DB::connection('pgsql_fines')->table('rig_trans')->where('status', 1)->whereBetween('created_at', [$from, $to])->pluck('ulb_id'))
            ->merge(DB::connection('pgsql_trade')->table('trade_transactions')->where('status', 1)->whereBetween('created_at', [$from, $to])->pluck('ulb_id'))
            ->merge(DB::connection('pgsql_advertisements')->table('adv_mar_transactions')->where('status', 1)->whereBetween('created_at', [$from, $to])->pluck('ulb_id'))
            ->merge(DB::connection('pgsql_advertisements')->table('pet_trans')->where('status', 1)->whereBetween('created_at', [$from, $to])->pluck('ulb_id'))
            ->merge(DB::connection('pgsql_advertisements')->table('marriage_transactions')->where('status', 1)->whereBetween('created_at', [$from, $to])->pluck('ulb_id'))
            ->merge(DB::connection('pgsql_advertisements')->table('mar_toll_payments')->where('is_active', true)->whereBetween('created_at', [$from, $to])->pluck('ulb_id'))
            ->merge(DB::connection('pgsql_advertisements')->table('mar_shop_payments')->where('is_active', true)->whereBetween('created_at', [$from, $to])->pluck('ulb_id'))
            ->merge(DB::connection('pgsql_swm')->table('swm_transactions')->where('paid_status', 1)->where('total_remaining_amt', 0)->whereBetween('stampdate', [$from, $to])->pluck('ulb_id'))

            ->unique()
            ->values();

        return DB::table('ulb_masters')
            ->whereIn('id', $ulbIds)
            ->select('id as ulb_id', 'ulb_name')
            ->get();
    }

    /* =========================================================
       MODULE TOTAL FUNCTIONS
       (your existing ones — unchanged)
    ========================================================= */
    // KEEP ALL YOUR moduleTotals() FUNCTIONS BELOW





    private function propertyTotals($ulbId, $from, $to)
    {
        $count =
            DB::table('prop_active_safs')->where('ulb_id', $ulbId)->whereBetween('created_at', [$from, $to])->count()
            + DB::table('prop_active_concessions')->where('ulb_id', $ulbId)->whereBetween('created_at', [$from, $to])->count()
            + DB::table('prop_active_objections')->where('ulb_id', $ulbId)->whereBetween('created_at', [$from, $to])->count()
            + DB::table('prop_active_harvestings')->where('ulb_id', $ulbId)->whereBetween('created_at', [$from, $to])->count();

        $collection = DB::table('prop_transactions')
            ->where('status', 1)
            ->where('ulb_id', $ulbId)
            ->whereBetween('created_at', [$from, $to])
            ->sum('amount');

        return ['collection' => (float)$collection, 'count' => $count];
    }

    private function waterTotals($ulbId, $from, $to)
    {
        $db = DB::connection('pgsql_water');

        $count = $db->table('water_applications')
            ->where('ulb_id', $ulbId)
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $collection = $db->table('water_trans')
            ->where('status', 1)
            ->where('ulb_id', $ulbId)
            ->whereBetween('created_at', [$from, $to])
            ->sum('amount');

        return ['collection' => (float)$collection, 'count' => $count];
    }

    private function swmTotals($ulbId, $from, $to)
    {
        $db = DB::connection('pgsql_swm');

        $count = $db->table('swm_consumers')
            ->where('is_deactivate', 0)
            ->whereBetween('stampdate', [$from, $to])
            ->count();

        $collection = $db->table('swm_transactions')
            ->where('paid_status', 1)
            ->where('total_remaining_amt', 0)
            ->where('ulb_id', $ulbId)
            ->whereBetween('stampdate', [$from, $to])
            ->sum('total_payable_amt');

        return ['collection' => (float)$collection, 'count' => $count];
    }

    private function finesTotals($ulbId, $from, $to)
    {
        $db = DB::connection('pgsql_fines');

        $count = $db->table('penalty_challans')
            ->where('status', 1)
            ->whereBetween('created_at', values: [$from, $to])
            ->count();

        $collection = $db->table('penalty_transactions')
            ->where('status', 1)
            ->where('ulb_id', $ulbId)
            ->whereBetween('created_at', [$from, $to])
            ->sum('amount');

        return ['collection' => (float)$collection, 'count' => $count];
    }

    private function rigTotals($ulbId, $from, $to)
    {
        $db = DB::connection('pgsql_fines');

        $count = $db->table('rig_active_registrations')
            ->where('ulb_id', $ulbId)
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $collection = $db->table('rig_trans')
            ->where('status', 1)
            ->where('ulb_id', $ulbId)
            ->whereBetween('created_at', [$from, $to])
            ->sum('amount');

        return ['collection' => (float)$collection, 'count' => $count];
    }

    private function waterTankerTotals($ulbId, $from, $to)
    {
        $db = DB::connection('pgsql_tanker');

        $collection = $db->table('wt_transactions')
            ->where('status', 1)
            ->where('ulb_id', $ulbId)
            ->whereBetween('created_at', [$from, $to])
            ->sum('paid_amount');

        $count = $db->table('wt_transactions')
            ->where('status', 1)
            ->where('ulb_id', $ulbId)
            ->whereBetween('created_at', [$from, $to])
            ->distinct('booking_id')
            ->count('booking_id');

        return ['collection' => (float)$collection, 'count' => $count];
    }

    private function septicTankerTotals($ulbId, $from, $to)
    {
        $db = DB::connection('pgsql_tanker');

        $collection = $db->table('st_transactions')
            ->where('status', 1)
            ->where('ulb_id', $ulbId)
            ->whereBetween('created_at', [$from, $to])
            ->sum('paid_amount');

        $count = $db->table('st_transactions')
            ->where('status', 1)
            ->where('ulb_id', $ulbId)
            ->whereBetween('created_at', [$from, $to])
            ->distinct('booking_id')
            ->count('booking_id');

        return ['collection' => (float)$collection, 'count' => $count];
    }

    private function petTotals($ulbId, $from, $to)
    {
        $db = DB::connection('pgsql_advertisements');

        $collection = $db->table('pet_trans')
            ->where('status', 1)
            ->where('ulb_id', $ulbId)
            ->whereBetween('created_at', [$from, $to])
            ->sum('amount');

        $count = $db->table('pet_trans')
            ->where('status', 1)
            ->where('ulb_id', $ulbId)
            ->whereBetween('created_at', [$from, $to])
            ->distinct('id')
            ->count('id');

        return ['collection' => (float)$collection, 'count' => $count];
    }

    private function advertisementTotals($ulbId, $from, $to)
    {
        $db = DB::connection('pgsql_advertisements');

        $collection = $db->table('adv_mar_transactions')
            ->where('status', 1)
            ->where('module_type', 'Advertisement')
            ->where('ulb_id', $ulbId)
            ->whereBetween('created_at', [$from, $to])
            ->sum('amount');

        $count = $db->table('adv_mar_transactions')
            ->where('status', 1)
            ->where('module_type', 'Advertisement')
            ->where('ulb_id', $ulbId)
            ->whereBetween('created_at', [$from, $to])
            ->distinct('id')
            ->count('id');

        return ['collection' => (float)$collection, 'count' => $count];
    }

    private function tradeTotals($ulbId, $from, $to)
    {
        $db = DB::connection('pgsql_trade');

        $collection = $db->table('trade_transactions')
            ->where('status', 1)
            ->where('ulb_id', $ulbId)
            ->whereBetween('created_at', [$from, $to])
            ->sum('paid_amount');

        $count = $db->table('trade_transactions')
            ->where('status', 1)
            ->where('ulb_id', $ulbId)
            ->whereBetween('created_at', [$from, $to])
            ->distinct('id')
            ->count('id');

        return ['collection' => (float)$collection, 'count' => $count];
    }

    private function municipalRentalTotals($ulbId, $from, $to)
    {
        $db = DB::connection('pgsql_advertisements');

        $tollCollection = $db->table('mar_toll_payments')
            ->where('is_active', true)
            ->where('ulb_id', $ulbId)
            ->whereBetween('created_at', [$from, $to])
            ->sum('amount');

        $shopCollection = $db->table('mar_shop_payments')
            ->where('is_active', true)
            ->where('ulb_id', $ulbId)
            ->whereBetween('created_at', [$from, $to])
            ->sum('amount');

        $collection = $tollCollection + $shopCollection;

        $tollCount = $db->table('mar_toll_payments')
            ->where('is_active', true)
            ->where('ulb_id', $ulbId)
            ->whereBetween('created_at', [$from, $to])
            ->distinct('toll_id')
            ->count('toll_id');

        $shopCount = $db->table('mar_shop_payments')
            ->where('is_active', true)
            ->where('ulb_id', $ulbId)
            ->whereBetween('created_at', [$from, $to])
            ->distinct('shop_id')
            ->count('shop_id');

        return [
            'collection' => (float)$collection,
            'count' => $tollCount + $shopCount
        ];
    }
    private function lodgeTotals($ulbId, $from, $to)
    {
        $db = DB::connection('pgsql_advertisements');

        $collection = $db->table('adv_mar_transactions')
            ->where('status', 1)
            ->where('module_type', 'Market')
            ->where('ulb_id', $ulbId)
            ->whereBetween('created_at', [$from, $to])
            ->sum('amount');

        $count = $db->table('adv_mar_transactions')
            ->where('status', 1)
            ->where('module_type', 'Market')
            ->where('ulb_id', $ulbId)
            ->whereBetween('created_at', [$from, $to])
            ->distinct('id')
            ->count('id');

        return ['collection' => (float)$collection, 'count' => $count];
    }
}
