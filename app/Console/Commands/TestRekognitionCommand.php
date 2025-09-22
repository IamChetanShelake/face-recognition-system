<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Aws\Rekognition\RekognitionClient;

class TestRekognitionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rekognition:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test AWS Rekognition integration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('AWS Rekognition Test');
        $this->line('====================');
        
        // Force load environment variables
        $envPath = base_path('.env');
        if (file_exists($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);
                    // Remove quotes if present
                    $value = trim($value, '"\'');
                    putenv("$key=$value");
                }
            }
        }
        
        // Get AWS credentials from environment
        $awsKeyId = getenv('AWS_ACCESS_KEY_ID');
        $awsSecret = getenv('AWS_SECRET_ACCESS_KEY');
        $awsRegion = getenv('AWS_DEFAULT_REGION');
        $bucket = getenv('AWS_BUCKET');
        
        $this->line("AWS Access Key ID: " . ($awsKeyId ? 'Set' : 'Not set'));
        $this->line("AWS Secret Access Key: " . ($awsSecret ? 'Set' : 'Not set'));
        $this->line("AWS Region: " . ($awsRegion ?: 'Not set'));
        $this->line("S3 Bucket: " . ($bucket ?: 'Not set'));
        
        if (!$awsKeyId || !$awsSecret || !$awsRegion || !$bucket) {
            $this->error('ERROR: AWS credentials are not properly configured.');
            return 1;
        }
        
        try {
            // Create Rekognition client
            $rekognitionClient = new RekognitionClient([
                'version' => 'latest',
                'region' => $awsRegion,
                'credentials' => [
                    'key' => $awsKeyId,
                    'secret' => $awsSecret,
                ],
            ]);
            
            $this->info('SUCCESS: AWS Rekognition client created.');
            
            // Test by calling listCollections (simple test that doesn't require a collection)
            $result = $rekognitionClient->listCollections();
            
            $this->info('SUCCESS: AWS Rekognition connection established.');
            $this->line('Found ' . count($result['CollectionIds']) . ' collections.');
            
            $this->info('SUCCESS: AWS Rekognition integration is ready!');
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('ERROR: Failed to create AWS Rekognition client.');
            $this->error('Message: ' . $e->getMessage());
            return 1;
        }
    }
}