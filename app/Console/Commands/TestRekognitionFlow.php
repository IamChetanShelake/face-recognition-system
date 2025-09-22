<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RekognitionService;
use Illuminate\Support\Facades\Log;

class TestRekognitionFlow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:rekognition-flow';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the complete Rekognition flow to identify issues';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(RekognitionService $rekognitionService)
    {
        $this->info('Testing complete Rekognition flow...');
        
        try {
            // Get service configuration
            $bucket = $rekognitionService->getBucket();
            $region = $rekognitionService->getRegion();
            
            $this->info("Service Configuration:");
            $this->line("  Bucket: " . $bucket);
            $this->line("  Region: " . $region);
            
            // Explain the debugging process
            $this->info("\n🔧 Debugging Process:");
            $this->line("1. Upload a test image to S3");
            $this->line("2. Verify the S3 object exists");
            $this->line("3. Call Rekognition detectFaces with the S3 object");
            $this->line("4. Analyze results");
            
            $this->info("\n⚠️  Note: This test requires a valid image file to upload.");
            $this->info("To run a complete test:");
            $this->line("1. Upload an image through your web interface");
            $this->line("2. Check the logs for detailed information");
            $this->line("3. Verify the S3 object exists in the bucket");
            $this->line("4. Confirm the bucket region matches Rekognition region");
            
            $this->info("\n✅ Key Points to Check:");
            $this->line("• Ensure the S3 object exists before calling Rekognition");
            $this->line("• Verify the bucket region matches Rekognition region (ap-south-1)");
            $this->line("• Confirm filename sanitization is working correctly");
            $this->line("• Check that the S3Object structure is correct in API calls");
            
            $this->info("\n📋 Recommended Actions:");
            $this->line("1. Add more detailed logging in the upload process");
            $this->line("2. Verify S3 object existence before Rekognition calls");
            $this->line("3. Add retry mechanism for S3 object verification");
            $this->line("4. Check AWS IAM permissions for Rekognition and S3");
            
            $this->info("\n🔧 To manually verify S3 object:");
            $this->line("aws s3api head-object --bucket " . $bucket . " --key PATH_TO_YOUR_IMAGE");
            
            $this->info("\n🔧 To check bucket region:");
            $this->line("aws s3api get-bucket-location --bucket " . $bucket);
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error during flow test: " . $e->getMessage());
            Log::error('TestRekognitionFlow error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}