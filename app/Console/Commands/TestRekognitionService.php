<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RekognitionService;

class TestRekognitionService extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rekognition:test-service';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test RekognitionService instantiation and configuration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing RekognitionService');
        $this->line('========================');
        
        try {
            // Clear configuration cache to ensure we get fresh values
            $this->call('config:clear');
            
            // Test instantiation
            $service = new RekognitionService();
            
            $this->info('SUCCESS: RekognitionService instantiated without errors');
            $this->line('The service is properly configured and ready to use.');
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('ERROR: ' . $e->getMessage());
            
            // Provide debugging information
            $this->line("\nDebugging Information:");
            $this->line("- AWS_ACCESS_KEY_ID: " . (env('AWS_ACCESS_KEY_ID') ? 'SET' : 'NOT SET'));
            $this->line("- AWS_SECRET_ACCESS_KEY: " . (env('AWS_SECRET_ACCESS_KEY') ? 'SET' : 'NOT SET'));
            $this->line("- AWS_DEFAULT_REGION: " . (env('AWS_DEFAULT_REGION') ?: 'NOT SET'));
            $this->line("- AWS_BUCKET: " . (env('AWS_BUCKET') ?: 'NOT SET'));
            
            return 1;
        }
    }
}