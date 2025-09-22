<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RekognitionService;
use Illuminate\Support\Facades\Log;

class VerifyLogging extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:verify-logging';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify that enhanced logging is working';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(RekognitionService $rekognitionService)
    {
        $this->info('Verifying enhanced logging...');
        
        try {
            // Test s3ObjectExists with a known object
            $this->info("Testing s3ObjectExists with enhanced logging...");
            
            // Get a sample key from the bucket
            $s3Client = $rekognitionService->getS3Client();
            $bucket = $rekognitionService->getBucket();
            
            $result = $s3Client->listObjectsV2([
                'Bucket' => $bucket,
                'MaxKeys' => 1
            ]);
            
            if (!empty($result['Contents'])) {
                $key = $result['Contents'][0]['Key'];
                $this->info("Testing with key: " . $key);
                
                // Call s3ObjectExists to trigger enhanced logging
                $exists = $rekognitionService->s3ObjectExists($key);
                
                $this->info("Object exists: " . ($exists ? 'Yes' : 'No'));
                
                // Also test the detectFaces method to see if it shows enhanced logging
                $this->info("Testing detectFaces method...");
                $faceDetection = $rekognitionService->detectFaces($key);
                
                $this->info("detectFaces result: " . ($faceDetection['success'] ? 'Success' : 'Failed'));
            } else {
                $this->error("No objects found in bucket");
            }
            
            $this->info("✅ Enhanced logging verification completed!");
            $this->info("Check the logs to see if detailed logging messages appear.");
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error during logging verification: " . $e->getMessage());
            Log::error('VerifyLogging error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}