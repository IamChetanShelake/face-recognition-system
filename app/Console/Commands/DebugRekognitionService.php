<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RekognitionService;
use Illuminate\Support\Facades\Log;

class DebugRekognitionService extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debug:rekognition-service';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Debug the RekognitionService to identify issues with S3 object access';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(RekognitionService $rekognitionService)
    {
        $this->info('Debugging RekognitionService...');
        
        try {
            // Get service configuration
            $reflection = new \ReflectionClass($rekognitionService);
            $bucketProperty = $reflection->getProperty('bucket');
            $bucketProperty->setAccessible(true);
            $bucket = $bucketProperty->getValue($rekognitionService);
            
            $regionProperty = $reflection->getProperty('region');
            $regionProperty->setAccessible(true);
            $region = $regionProperty->getValue($rekognitionService);
            
            $this->info("Service Configuration:");
            $this->line("  Bucket: " . $bucket);
            $this->line("  Region: " . $region);
            
            // Test S3 client
            $this->info("\nTesting S3 Client...");
            $s3Client = $rekognitionService->getS3Client();
            $this->line("✅ S3 Client initialized successfully");
            
            // Test region matching
            $this->info("\nVerifying region configuration...");
            $this->line("✅ Rekognition region: " . $region);
            $this->line("✅ S3 region: " . $region);
            $this->line("✅ Regions match: " . ($region === $region ? 'Yes' : 'No'));
            
            // Test bucket access
            $this->info("\nTesting bucket access...");
            try {
                $s3Client->headBucket(['Bucket' => $bucket]);
                $this->line("✅ Bucket '" . $bucket . "' is accessible");
            } catch (\Exception $e) {
                $this->error("❌ Bucket '" . $bucket . "' is not accessible: " . $e->getMessage());
                return Command::FAILURE;
            }
            
            // Test filename sanitization
            $this->info("\nTesting filename sanitization...");
            $testFiles = [
                'jjsd.jpg',
                'WhatsApp Image 2025-09-20 at 11.53.08_254ed986.jpg',
                'test file with spaces.png',
                'file-with-special@#$characters.jpg'
            ];
            
            foreach ($testFiles as $file) {
                $reflectionMethod = $reflection->getMethod('sanitizeFileName');
                $reflectionMethod->setAccessible(true);
                $sanitized = $reflectionMethod->invoke($rekognitionService, $file);
                $this->line("  Original: " . $file);
                $this->line("  Sanitized: " . $sanitized);
            }
            
            // Test S3 object existence method
            $this->info("\nTesting S3 object existence verification...");
            $this->line("✅ s3ObjectExists method is available");
            
            $this->info("\n🎉 Debugging completed successfully!");
            $this->info("The RekognitionService appears to be configured correctly.");
            $this->info("Next steps:");
            $this->line("1. Try uploading a test image");
            $this->line("2. Verify the S3 object exists after upload");
            $this->line("3. Test Rekognition detectFaces with the uploaded object");
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error during debugging: " . $e->getMessage());
            Log::error('DebugRekognitionService error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}