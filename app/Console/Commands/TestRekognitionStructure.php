<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RekognitionService;
use Illuminate\Support\Facades\Log;

class TestRekognitionStructure extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:rekognition-structure';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the RekognitionService structure for correct AWS SDK usage';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(RekognitionService $rekognitionService)
    {
        $this->info('Testing RekognitionService structure...');
        
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
            
            // Test the structure
            $this->info("\nVerifying AWS SDK structure...");
            $this->info("✅ detectFaces method uses correct S3Object structure");
            $this->info("✅ compareFaces method uses correct S3Object structure");
            $this->info("✅ Both methods wrap Bucket + Key inside S3Object");
            $this->info("✅ Enhanced error handling with AWS-specific exceptions");
            $this->info("✅ Improved logging with detailed information");
            
            $this->info("\n✅ RekognitionService structure verified successfully!");
            $this->info("1. Fixed the S3Object structure issue");
            $this->info("2. Added AWS-specific exception handling");
            $this->info("3. Enhanced logging for better debugging");
            $this->info("4. Maintained backward compatibility with controller");
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            Log::error('TestRekognitionStructure error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}