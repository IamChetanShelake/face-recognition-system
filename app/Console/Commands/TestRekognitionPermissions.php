<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RekognitionService;
use Aws\Rekognition\RekognitionClient;
use Illuminate\Support\Facades\Log;

class TestRekognitionPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:rekognition-permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Rekognition permissions to access S3 objects';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(RekognitionService $rekognitionService)
    {
        $this->info('Testing Rekognition permissions to access S3 objects...');
        
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
            
            // Test 1: Verify we can access the object directly
            $this->info("\n1. Testing direct S3 access...");
            try {
                $s3Result = $s3Client->headObject([
                    'Bucket' => $bucket,
                    'Key' => $key
                ]);
                $this->line("✅ Direct S3 access successful");
                $this->line("   Content-Length: " . ($s3Result['ContentLength'] ?? 'unknown'));
            } catch (\Exception $e) {
                $this->error("❌ Direct S3 access failed: " . $e->getMessage());
                return Command::FAILURE;
            }
            
            // Test 2: Test Rekognition detectFaces with different approaches
            $this->info("\n2. Testing Rekognition detectFaces...");
            
            // Get the Rekognition client from the service
            $reflection = new \ReflectionClass($rekognitionService);
            $rekognitionClientProperty = $reflection->getProperty('rekognitionClient');
            $rekognitionClientProperty->setAccessible(true);
            $rekognitionClient = $rekognitionClientProperty->getValue($rekognitionService);
            
            // Test with the standard approach
            $this->info("   a. Testing standard S3Object approach...");
            try {
                $rekognitionResult = $rekognitionClient->detectFaces([
                    'Image' => [
                        'S3Object' => [
                            'Bucket' => $bucket,
                            'Key' => $key,
                        ],
                    ],
                    'Attributes' => ['ALL'],
                ]);
                $this->line("✅ Standard S3Object approach successful");
                $this->line("   Face count: " . count($rekognitionResult['FaceDetails'] ?? []));
            } catch (\Aws\Exception\AwsException $e) {
                $this->error("❌ Standard S3Object approach failed:");
                $this->line("   Error Code: " . $e->getAwsErrorCode());
                $this->line("   Error Message: " . $e->getAwsErrorMessage());
            } catch (\Exception $e) {
                $this->error("❌ Standard S3Object approach failed with general exception: " . $e->getMessage());
            }
            
            // Test with bytes approach (download and send bytes)
            $this->info("\n   b. Testing bytes approach...");
            try {
                // Download the image
                $getObjectResult = $s3Client->getObject([
                    'Bucket' => $bucket,
                    'Key' => $key,
                ]);
                
                $imageBytes = $getObjectResult['Body']->getContents();
                $this->line("   Downloaded image bytes: " . strlen($imageBytes) . " bytes");
                
                // Test Rekognition with bytes
                $rekognitionResult = $rekognitionClient->detectFaces([
                    'Image' => [
                        'Bytes' => $imageBytes,
                    ],
                    'Attributes' => ['ALL'],
                ]);
                $this->line("✅ Bytes approach successful");
                $this->line("   Face count: " . count($rekognitionResult['FaceDetails'] ?? []));
            } catch (\Aws\Exception\AwsException $e) {
                $this->error("❌ Bytes approach failed:");
                $this->line("   Error Code: " . $e->getAwsErrorCode());
                $this->line("   Error Message: " . $e->getAwsErrorMessage());
            } catch (\Exception $e) {
                $this->error("❌ Bytes approach failed with general exception: " . $e->getMessage());
            }
            
            $this->info("\n📋 Next steps:");
            $this->line("1. If bytes approach works but S3Object doesn't, it's likely an IAM permissions issue");
            $this->line("2. Check that your AWS IAM user/role has these permissions:");
            $this->line("   - rekognition:DetectFaces");
            $this->line("   - rekognition:CompareFaces");
            $this->line("   - s3:GetObject (on the specific bucket and objects)");
            $this->line("   - s3:ListBucket (on the specific bucket)");
            $this->line("");
            $this->line("3. You can test IAM permissions with AWS CLI:");
            $this->line("   aws sts get-caller-identity");
            $this->line("   aws iam simulate-principal-policy --policy-source-arn YOUR_ROLE_ARN --action-names rekognition:DetectFaces s3:GetObject --resource-arns arn:aws:s3:::" . $bucket . "/*");
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error during permissions test: " . $e->getMessage());
            Log::error('TestRekognitionPermissions error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}