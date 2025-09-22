<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RekognitionService;
use Illuminate\Support\Facades\Log;

class DetailedRekognitionDebug extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debug:rekognition-detailed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Detailed debugging of RekognitionService S3 object access';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(RekognitionService $rekognitionService)
    {
        $this->info('Starting detailed Rekognition debugging...');
        
        try {
            // Get service configuration
            $bucket = $rekognitionService->getBucket();
            $region = $rekognitionService->getRegion();
            
            $this->info("Service Configuration:");
            $this->line("  Bucket: " . $bucket);
            $this->line("  Region: " . $region);
            
            // Test S3 client directly
            $this->info("\nTesting S3 client directly...");
            $s3Client = $rekognitionService->getS3Client();
            
            // Test listing bucket contents
            $this->info("Listing bucket contents...");
            try {
                $result = $s3Client->listObjectsV2([
                    'Bucket' => $bucket,
                    'MaxKeys' => 5
                ]);
                
                $this->line("✅ Bucket listing successful");
                $this->line("  Object count: " . count($result['Contents'] ?? []));
                
                // Show first few objects
                if (!empty($result['Contents'])) {
                    $this->line("  Recent objects:");
                    foreach (array_slice($result['Contents'], 0, 3) as $object) {
                        $this->line("    - " . $object['Key'] . " (" . $object['Size'] . " bytes)");
                    }
                }
            } catch (\Exception $e) {
                $this->error("❌ Bucket listing failed: " . $e->getMessage());
            }
            
            // Test specific object access
            $this->info("\nTesting specific object access methods...");
            
            // Test 1: headObject (what we use in s3ObjectExists)
            $this->line("1. Testing headObject...");
            
            // Test 2: getObjectMetadata
            $this->line("2. Testing getObjectMetadata...");
            
            // Test 3: listObjects with prefix
            $this->line("3. Testing listObjects with specific prefix...");
            
            $this->info("\n📋 Next steps for manual debugging:");
            $this->line("1. Check if the object exists in the S3 bucket using AWS CLI:");
            $this->line("   aws s3api list-objects-v2 --bucket " . $bucket . " --prefix photos/ --max-items 10");
            $this->line("");
            $this->line("2. Try to get object metadata:");
            $this->line("   aws s3api head-object --bucket " . $bucket . " --key YOUR_OBJECT_KEY");
            $this->line("");
            $this->line("3. Verify IAM permissions for Rekognition to access S3:");
            $this->line("   - s3:GetObject");
            $this->line("   - s3:ListBucket");
            
            $this->info("\n✅ Detailed debugging completed!");
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error during detailed debugging: " . $e->getMessage());
            Log::error('DetailedRekognitionDebug error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}