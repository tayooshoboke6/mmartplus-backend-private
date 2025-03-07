<?php

namespace App\Console\Commands;

use App\Services\RatingService;
use Illuminate\Console\Command;

class RecalculateProductRatings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:recalculate-ratings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate all product Bayesian ratings';

    /**
     * The rating service instance.
     *
     * @var \App\Services\RatingService
     */
    protected $ratingService;

    /**
     * Create a new command instance.
     *
     * @param  \App\Services\RatingService  $ratingService
     * @return void
     */
    public function __construct(RatingService $ratingService)
    {
        parent::__construct();
        $this->ratingService = $ratingService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Recalculating all product Bayesian ratings...');
        
        $this->ratingService->recalculateAllProductRatings();
        
        $this->info('All product ratings have been recalculated successfully!');
        
        return 0;
    }
}
