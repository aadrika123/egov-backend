<?php

namespace App\Console\Commands;

use App\Models\AgencyHoarding;
use App\Models\Water\AgencyHoarding as WaterAgencyHoarding;
use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Models\Water\WaterApplication;
use Illuminate\Support\Facades\Log;

class TestCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            Log::info('TestCron command is running.');
            WaterApplication::where('id', 100)
                ->update(['status' => false]);
            Log::info('TestCron command completed successfully.');
        } catch (\Exception $e) {
            Log::error('An error occurred: ' . $e->getMessage());
        }
    }
}
