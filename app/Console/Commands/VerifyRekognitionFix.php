<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RekognitionService;
use Illuminate\Support\Facades\Log;

class VerifyRekognitionFix extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:verify-rekognition-fix';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify that the Rekognition fix using bytes approach works';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(RekognitionService $rekognitionService)
    {
        $this->info('Verifying Rekognition fix using bytes approach...');
        
        try {
            // Get service configuration
            $bucket = $rekognitionService->getBucket();
            $region = $rekognitionService->getRegion();
            
            $this->info("Service Configuration:");
            $this->line("  Bucket: " . $bucket);
            $this->line("  Region: " . $region);
            
            // Get a sample key from the bucket
            $s3Client = $rekognitionService->getS3Client();
            
            $result = $s3Client->listObjectsV2([
                'Bucket' => $bucket,
                'MaxKeys' => 1
            ]);
            
            if (empty($result['Contents'])) {
                $this->error("No objects found in bucket");
                return Command::FAILURE;
            }
            
            $key = $result['Contents'][0]['Key'];
            $this->info("Testing with key: " . $key);
            
            // Test the fixed detectFaces method
            $this->info("\nTesting fixed detectFaces method...");
            $faceDetection = $rekognitionService->detectFaces($key);
            
            if ($faceDetection['success']) {
                $this->info("✅ detectFaces method works correctly with bytes approach");
                $this->line("   Face count: " . $faceDetection['face_count']);
            } else {
                $this->error("❌ detectFaces method failed: " . $faceDetection['error']);
                return Command::FAILURE;
            }
            
            // Test the fixed compareFaces method
            $this->info("\nTesting fixed compareFaces method...");
            $faceComparison = $rekognitionService->compareFaces($key, $key); // Compare with itself
            
            if ($faceComparison['success']) {
                $this->info("✅ compareFaces method works correctly with bytes approach");
                $this->line("   Match count: " . count($faceComparison['matches']));
                $this->line("   Highest similarity: " . $faceComparison['highest_similarity']);
            } else {
                $this->error("❌ compareFaces method failed: " . $faceComparison['error']);
                return Command::FAILURE;
            }
            
            $this->info("\n🎉 Rekognition fix verification completed successfully!");
            $this->info("The service now uses the bytes approach which works around the IAM permissions issue.");
            $this->info("You can now try registering a person again through the web interface.");
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error during fix verification: " . $e->getMessage());
            Log::error('VerifyRekognitionFix error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}