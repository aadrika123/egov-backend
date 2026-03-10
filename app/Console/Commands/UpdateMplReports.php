<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\Property\ReportController;
use Illuminate\Http\Request;

class UpdateMplReports extends Command
{
    protected $signature = 'mpl:update-reports';
    protected $description = 'Update MPL yearly reports data';

    public function handle()
    {
        $this->info('Starting MPL reports update...');
        $this->info('This may take several minutes due to complex calculations...');
        
        try {
            $startTime = microtime(true);
            
            $controller = new ReportController(app()->make('App\Repository\Property\Interfaces\IReport'));
            $request = new Request();
            
            $this->info('Processing data calculations...');
            $result = $controller->liveDashboardUpdate($request);
            
            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 2);
            
            $this->info("MPL reports updated successfully in {$executionTime} seconds!");
            return 0;
        } catch (\Exception $e) {
            $this->error('Error updating MPL reports: ' . $e->getMessage());
            $this->error('File: ' . $e->getFile() . ' Line: ' . $e->getLine());
            return 1;
        }
    }
}
