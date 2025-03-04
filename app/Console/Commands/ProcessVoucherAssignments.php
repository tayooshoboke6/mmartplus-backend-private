<?php

namespace App\Console\Commands;

use App\Models\Voucher;
use App\Services\VoucherService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessVoucherAssignments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vouchers:process-assignments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process targeted voucher assignments based on criteria';

    /**
     * The voucher service instance.
     *
     * @var VoucherService
     */
    protected $voucherService;

    /**
     * Create a new command instance.
     *
     * @param VoucherService $voucherService
     * @return void
     */
    public function __construct(VoucherService $voucherService)
    {
        parent::__construct();
        $this->voucherService = $voucherService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting voucher assignment processing...');
        
        try {
            // Get all targeted vouchers that are active
            $vouchers = Voucher::where('qualification_type', 'targeted')
                ->where('is_active', true)
                ->whereNotNull('criteria_json')
                ->get();
                
            $this->info("Found {$vouchers->count()} targeted vouchers to process.");
            
            $totalAssigned = 0;
            
            foreach ($vouchers as $voucher) {
                $this->info("Processing voucher: {$voucher->code}");
                $assignedCount = $this->voucherService->assignVouchersToQualifyingUsers($voucher);
                $totalAssigned += $assignedCount;
                $this->info("Assigned to {$assignedCount} users.");
            }
            
            $this->info("Voucher assignment process completed. Total vouchers assigned: {$totalAssigned}");
            Log::info("Voucher assignment process completed. Total vouchers assigned: {$totalAssigned}");
            
            return 0;
        } catch (\Exception $e) {
            $this->error("Error processing voucher assignments: {$e->getMessage()}");
            Log::error("Error processing voucher assignments: {$e->getMessage()}");
            return 1;
        }
    }
}
