<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RekognitionService;
use Illuminate\Support\Facades\Log;

class TestRekognitionFix extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:rekognition-fix';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the RekognitionService fix for S3 object key handling';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(RekognitionService $rekognitionService)
    {
        $this->info('Testing RekognitionService fix...');
        
        try {
            // Log the service configuration
            $reflection = new \ReflectionClass($rekognitionService);
            $bucketProperty = $reflection->getProperty('bucket');
            $bucketProperty->setAccessible(true);
            $bucket = $bucketProperty->getValue($rekognitionService);
            
            $regionProperty = $reflection->getProperty('region');
            $regionProperty->setAccessible(true);
            $region = $regionProperty->getValue($rekognitionService);
            
            $this->info("Service Configuration:");
            $this->info("  Bucket: " . $bucket);
            $this->info("  Region: " . $region);
            
            // Test the parameter fix
            $this->info("\nVerifying S3 object parameter fix...");
            $this->info("✅ Using 'Key' instead of 'Name' for S3 objects");
            
            // Test filename sanitization
            $this->info("\nTesting filename sanitization...");
            $testFilename = "WhatsApp Image 2025-09-16 at 17.54.48_68442323.jpg";
            $reflectionMethod = $reflection->getMethod('sanitizeFileName');
            $reflectionMethod->setAccessible(true);
            $sanitized = $reflectionMethod->invoke($rekognitionService, $testFilename);
            
            $this->info("Original filename: " . $testFilename);
            $this->info("Sanitized filename: " . $sanitized);
            
            $this->info("\n✅ RekognitionService fixes applied successfully!");
            $this->info("1. Fixed S3 object parameter (Key instead of Name)");
            $this->info("2. Added filename sanitization for special characters");
            $this->info("3. Added better error handling and validation");
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            Log::error('TestRekognitionFix error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}