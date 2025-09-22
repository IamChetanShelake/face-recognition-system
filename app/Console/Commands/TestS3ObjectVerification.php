<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RekognitionService;
use Illuminate\Support\Facades\Log;

class TestS3ObjectVerification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:s3-verification {key?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test S3 object verification with a specific key';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(RekognitionService $rekognitionService)
    {
        $key = $this->argument('key');
        
        if (!$key) {
            $this->info('No key provided. Testing with a sample key from the bucket...');
            
            // Try to get a sample key from the bucket
            try {
                $s3Client = $rekognitionService->getS3Client();
                $bucket = $rekognitionService->getBucket();
                
                $result = $s3Client->listObjectsV2([
                    'Bucket' => $bucket,
                    'MaxKeys' => 1
                ]);
                
                if (!empty($result['Contents'])) {
                    $key = $result['Contents'][0]['Key'];
                    $this->info("Using key: " . $key);
                } else {
                    $this->error("No objects found in bucket");
                    return Command::FAILURE;
                }
            } catch (\Exception $e) {
                $this->error("Failed to list bucket contents: " . $e->getMessage());
                return Command::FAILURE;
            }
        }
        
        $this->info("Testing S3 object verification for key: " . $key);
        
        try {
            // Test s3ObjectExists method
            $this->info("Calling s3ObjectExists...");
            $exists = $rekognitionService->s3ObjectExists($key);
            
            $this->info("Result: " . ($exists ? 'Object exists' : 'Object does not exist'));
            
            // Test direct headObject call
            $this->info("Testing direct headObject call...");
            try {
                $s3Client = $rekognitionService->getS3Client();
                $bucket = $rekognitionService->getBucket();
                
                $result = $s3Client->headObject([
                    'Bucket' => $bucket,
                    'Key' => $key
                ]);
                
                $this->info("Direct headObject successful:");
                $this->line("  Content-Length: " . ($result['ContentLength'] ?? 'unknown'));
                $this->line("  Last-Modified: " . ($result['LastModified'] ?? 'unknown'));
                $this->line("  ETag: " . ($result['ETag'] ?? 'unknown'));
            } catch (\Exception $e) {
                $this->error("Direct headObject failed: " . $e->getMessage());
            }
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error during S3 object verification test: " . $e->getMessage());
            Log::error('TestS3ObjectVerification error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}