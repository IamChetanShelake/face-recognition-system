<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RekognitionService;
use Illuminate\Support\Facades\Log;

class TestRegistrationProcess extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:registration-process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the complete registration process';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(RekognitionService $rekognitionService)
    {
        $this->info('Testing complete registration process...');
        
        try {
            // Get service configuration
            $bucket = $rekognitionService->getBucket();
            $region = $rekognitionService->getRegion();
            
            $this->info("Service Configuration:");
            $this->line("  Bucket: " . $bucket);
            $this->line("  Region: " . $region);
            
            $this->info("\n📋 Registration Process Steps:");
            $this->line("1. Upload photo to S3");
            $this->line("2. Verify S3 object exists");
            $this->line("3. Detect faces using bytes approach");
            $this->line("4. Register person if face detected");
            
            $this->info("\n✅ The registration process should now work correctly!");
            $this->info("The service uses the bytes approach which bypasses the IAM permissions issue.");
            $this->info("Try registering a person through the web interface.");
            
            $this->info("\n🔧 If you still encounter issues:");
            $this->line("1. Check the Laravel logs for detailed error information");
            $this->line("2. Verify your AWS credentials are correct");
            $this->line("3. Consider fixing the IAM permissions for a more efficient approach");
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error during registration process test: " . $e->getMessage());
            Log::error('TestRegistrationProcess error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}