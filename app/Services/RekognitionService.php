<?php

namespace App\Services;

use Aws\Rekognition\RekognitionClient;
use Aws\S3\S3Client;
use Illuminate\Support\Facades\Log;
use Exception;

class RekognitionService
{
    private $rekognitionClient;
    private $s3Client;
    private $bucket;
    private $region;

    public function __construct()
    {
        // Get configuration values with fallbacks
        $this->bucket = config('filesystems.disks.s3.bucket') ?? env('AWS_BUCKET');
        $this->region = config('filesystems.disks.s3.region') ?? env('AWS_DEFAULT_REGION') ?? 'us-east-1';
        
        $awsKeyId = config('filesystems.disks.s3.key') ?? env('AWS_ACCESS_KEY_ID');
        $awsSecret = config('filesystems.disks.s3.secret') ?? env('AWS_SECRET_ACCESS_KEY');
        
        // Validate required configuration
        if (empty($awsKeyId) || empty($awsSecret)) {
            throw new Exception('AWS credentials not configured properly');
        }
        
        if (empty($this->bucket)) {
            throw new Exception('AWS S3 bucket not configured');
        }
        
        if (empty($this->region)) {
            throw new Exception('AWS region not configured');
        }
        
        // Log configuration for debugging
        Log::info('AWS Rekognition Service Configuration', [
            'region' => $this->region,
            'bucket' => $this->bucket
        ]);
        
        // Initialize AWS Rekognition Client
        $this->rekognitionClient = new RekognitionClient([
            'version' => 'latest',
            'region' => $this->region,
            'credentials' => [
                'key' => $awsKeyId,
                'secret' => $awsSecret,
            ],
        ]);
        
        // Initialize AWS S3 Client
        $this->s3Client = new S3Client([
            'version' => 'latest',
            'region' => $this->region,
            'credentials' => [
                'key' => $awsKeyId,
                'secret' => $awsSecret,
            ],
        ]);
    }

    /**
     * Compare two faces using AWS Rekognition
     *
     * @param string $sourceKey S3 key of the source image
     * @param string $targetKey S3 key of the target image
     * @param float $similarityThreshold
     * @return array
     */
    public function compareFaces(string $sourceKey, string $targetKey, float $similarityThreshold = 85.0): array
    {
        try {
            // Validate input parameters
            if (empty($sourceKey) || empty($targetKey)) {
                throw new Exception('Source or target S3 key is empty');
            }
            
            // Verify that S3 objects exist before calling Rekognition
            Log::info("Verifying source S3 object exists before compareFaces call", [
                'bucket' => $this->bucket,
                'key'    => $sourceKey,
            ]);
            
            if (!$this->s3ObjectExists($sourceKey)) {
                // Wait a moment and try again
                Log::warning("Source S3 object not found, waiting 1 second and retrying", [
                    'bucket' => $this->bucket,
                    'key'    => $sourceKey,
                ]);
                sleep(1);
                if (!$this->s3ObjectExists($sourceKey)) {
                    throw new Exception("Source S3 object does not exist: {$sourceKey}");
                }
                Log::info("Source S3 object found on retry", [
                    'bucket' => $this->bucket,
                    'key'    => $sourceKey,
                ]);
            }
            
            Log::info("Verifying target S3 object exists before compareFaces call", [
                'bucket' => $this->bucket,
                'key'    => $targetKey,
            ]);
            
            if (!$this->s3ObjectExists($targetKey)) {
                // Wait a moment and try again
                Log::warning("Target S3 object not found, waiting 1 second and retrying", [
                    'bucket' => $this->bucket,
                    'key'    => $targetKey,
                ]);
                sleep(1);
                if (!$this->s3ObjectExists($targetKey)) {
                    throw new Exception("Target S3 object does not exist: {$targetKey}");
                }
                Log::info("Target S3 object found on retry", [
                    'bucket' => $this->bucket,
                    'key'    => $targetKey,
                ]);
            }
            
            // Download both images and use bytes approach since S3Object approach has permissions issues
            Log::info("Downloading source image for bytes-based comparison", [
                'bucket' => $this->bucket,
                'key'    => $sourceKey,
            ]);
            
            $sourceObject = $this->s3Client->getObject([
                'Bucket' => $this->bucket,
                'Key' => $sourceKey,
            ]);
            
            $sourceBytes = $sourceObject['Body']->getContents();
            
            Log::info("Downloading target image for bytes-based comparison", [
                'bucket' => $this->bucket,
                'key'    => $targetKey,
            ]);
            
            $targetObject = $this->s3Client->getObject([
                'Bucket' => $this->bucket,
                'Key' => $targetKey,
            ]);
            
            $targetBytes = $targetObject['Body']->getContents();
            
            Log::info("Rekognition compareFaces called with bytes approach", [
                'source_size' => strlen($sourceBytes),
                'target_size' => strlen($targetBytes),
            ]);

            $result = $this->rekognitionClient->compareFaces([
                'SourceImage' => [
                    'Bytes' => $sourceBytes,
                ],
                'TargetImage' => [
                    'Bytes' => $targetBytes,
                ],
                'SimilarityThreshold' => $similarityThreshold,
            ]);

            $matches = $result['FaceMatches'] ?? [];

            Log::info("Rekognition compareFaces success", [
                'match_count' => count($matches),
            ]);

            // Process matches to return a more structured response
            $processedMatches = [];
            $highestSimilarity = 0;
            
            foreach ($matches as $match) {
                $similarity = $match['Similarity'];
                if ($similarity > $highestSimilarity) {
                    $highestSimilarity = $similarity;
                }
                
                $processedMatches[] = [
                    'similarity' => $similarity,
                    'confidence' => $match['Face']['Confidence'] ?? 0,
                ];
            }

            return [
                'success' => true,
                'matches' => $processedMatches,
                'highest_similarity' => $highestSimilarity,
                'is_match' => $highestSimilarity >= $similarityThreshold,
            ];
        } catch (\Aws\Exception\AwsException $e) {
            Log::error("AWS Rekognition compareFaces AWS error", [
                'error'        => $e->getAwsErrorMessage(),
                'error_code'   => $e->getAwsErrorCode(),
                'source_key'   => $sourceKey,
                'target_key'   => $targetKey,
                'bucket'       => $this->bucket,
            ]);
            return [
                'success' => false,
                'matches' => [],
                'highest_similarity' => 0,
                'is_match' => false,
                'error' => $e->getAwsErrorMessage(),
            ];
        } catch (\Exception $e) {
            Log::error("Rekognition compareFaces general error", [
                'error'        => $e->getMessage(),
                'source_key'   => $sourceKey,
                'target_key'   => $targetKey,
                'bucket'       => $this->bucket,
            ]);
            return [
                'success' => false,
                'matches' => [],
                'highest_similarity' => 0,
                'is_match' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Detect faces in an image
     *
     * @param string $s3Key S3 key of the image
     * @return array
     */
    public function detectFaces(string $s3Key): array
    {
        try {
            // Validate input parameter
            if (empty($s3Key)) {
                throw new Exception('Image S3 key is empty');
            }
            
            // Verify that S3 object exists before calling Rekognition
            Log::info("Verifying S3 object exists before detectFaces call", [
                'bucket' => $this->bucket,
                'key'    => $s3Key,
            ]);
            
            if (!$this->s3ObjectExists($s3Key)) {
                // Wait a moment and try again
                Log::warning("S3 object not found, waiting 1 second and retrying", [
                    'bucket' => $this->bucket,
                    'key'    => $s3Key,
                ]);
                sleep(1);
                if (!$this->s3ObjectExists($s3Key)) {
                    throw new Exception("S3 object does not exist: {$s3Key}");
                }
                Log::info("S3 object found on retry", [
                    'bucket' => $this->bucket,
                    'key'    => $s3Key,
                ]);
            }
            
            // Download the image and use bytes approach since S3Object approach has permissions issues
            Log::info("Downloading image for bytes-based face detection", [
                'bucket' => $this->bucket,
                'key'    => $s3Key,
            ]);
            
            $object = $this->s3Client->getObject([
                'Bucket' => $this->bucket,
                'Key' => $s3Key,
            ]);
            
            $imageBytes = $object['Body']->getContents();
            
            Log::info("Rekognition detectFaces called with bytes approach", [
                'image_size' => strlen($imageBytes),
            ]);

            $result = $this->rekognitionClient->detectFaces([
                'Image' => [
                    'Bytes' => $imageBytes,
                ],
                'Attributes' => ['ALL'],
            ]);

            $faces = $result['FaceDetails'] ?? [];

            Log::info("Rekognition detectFaces success", [
                'face_count' => count($faces),
            ]);

            return [
                'success' => true,
                'faces' => $faces,
                'face_count' => count($faces),
            ];
        } catch (\Aws\Exception\AwsException $e) {
            Log::error("AWS Rekognition detectFaces AWS error", [
                'error'  => $e->getAwsErrorMessage(),
                'error_code' => $e->getAwsErrorCode(),
                'bucket' => $this->bucket,
                'key'    => $s3Key,
            ]);
            return [
                'success' => false,
                'faces' => [],
                'face_count' => 0,
                'error' => $e->getAwsErrorMessage(),
            ];
        } catch (\Exception $e) {
            Log::error("Rekognition detectFaces general error", [
                'error'  => $e->getMessage(),
                'bucket' => $this->bucket,
                'key'    => $s3Key,
            ]);
            return [
                'success' => false,
                'faces' => [],
                'face_count' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Upload file to S3 with safe filename handling
     *
     * @param mixed $file
     * @param string $directory
     * @return array
     */
    public function uploadToS3($file, string $directory = 'uploads'): array
    {
        try {
            // Create a safe filename by removing special characters and spaces
            $originalName = $file->getClientOriginalName();
            $safeFileName = $this->sanitizeFileName($originalName);
            $fileName = time() . '_' . $safeFileName;
            $filePath = $directory . '/' . $fileName;

            Log::info("Uploading file to S3", [
                'original_name' => $originalName,
                'safe_name' => $safeFileName,
                'file_path' => $filePath,
                'bucket' => $this->bucket,
            ]);

            // Upload to S3
            $result = $this->s3Client->putObject([
                'Bucket' => $this->bucket,
                'Key' => $filePath,
                'Body' => fopen($file->getRealPath(), 'r'),
                'ACL' => 'private'
            ]);
            
            // Wait a moment to ensure the object is available
            sleep(1);
            
            // Verify the object was uploaded successfully
            Log::info("Verifying uploaded S3 object exists", [
                'bucket' => $this->bucket,
                'key'    => $filePath,
            ]);
            
            if (!$this->s3ObjectExists($filePath)) {
                Log::error("Failed to verify S3 object upload", [
                    'bucket' => $this->bucket,
                    'key'    => $filePath,
                ]);
                throw new Exception("Failed to verify S3 object upload: {$filePath}");
            }

            Log::info("File uploaded to S3 successfully", [
                'file_path' => $filePath,
                'bucket' => $this->bucket,
            ]);

            return [
                'success' => true,
                's3_key' => $filePath,
                'file_name' => $fileName
            ];

        } catch (Exception $e) {
            Log::error('S3 upload error: ' . $e->getMessage());
            
            return [
                'success' => false,
                's3_key' => null,
                'file_name' => null,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check if an S3 object exists
     *
     * @param string $key
     * @return bool
     */
    public function s3ObjectExists(string $key): bool
    {
        try {
            Log::info("Checking S3 object existence with headObject", [
                'bucket' => $this->bucket,
                'key'    => $key,
            ]);
            
            $result = $this->s3Client->headObject([
                'Bucket' => $this->bucket,
                'Key' => $key
            ]);
            
            Log::info("S3 object exists", [
                'bucket' => $this->bucket,
                'key'    => $key,
                'content_length' => $result['ContentLength'] ?? 'unknown',
            ]);
            
            return true;
        } catch (\Aws\Exception\AwsException $e) {
            Log::warning('S3 object does not exist (AWS Exception)', [
                'key' => $key,
                'bucket' => $this->bucket,
                'error_code' => $e->getAwsErrorCode(),
                'error_message' => $e->getAwsErrorMessage(),
            ]);
            return false;
        } catch (Exception $e) {
            Log::warning('S3 object does not exist (General Exception)', [
                'key' => $key,
                'bucket' => $this->bucket,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Sanitize filename to remove special characters and spaces
     *
     * @param string $fileName
     * @return string
     */
    private function sanitizeFileName(string $fileName): string
    {
        // Log the original filename
        Log::info("Sanitizing filename", ['original' => $fileName]);
        
        // Remove special characters and replace spaces with underscores
        $fileName = preg_replace('/[^a-zA-Z0-9._-]/', '', $fileName);
        // Replace spaces with underscores
        $fileName = str_replace(' ', '_', $fileName);
        // Replace multiple underscores with single underscore
        $fileName = preg_replace('/_+/', '_', $fileName);
        // Remove leading/trailing underscores
        $fileName = trim($fileName, '_');
        
        Log::info("Sanitized filename", ['sanitized' => $fileName]);
        
        return $fileName;
    }

    /**
     * Get presigned URL for S3 file
     *
     * @param string $filePath
     * @param int $expiresIn
     * @return string
     */
    public function getPresignedUrl(string $filePath, int $expiresIn = 3600): string
    {
        try {
            // Validate input parameter
            if (empty($filePath)) {
                Log::warning('Empty file path provided to getPresignedUrl');
                return '';
            }
            
            $cmd = $this->s3Client->getCommand('GetObject', [
                'Bucket' => $this->bucket,
                'Key' => $filePath
            ]);
            
            $request = $this->s3Client->createPresignedRequest($cmd, '+' . $expiresIn . ' seconds');
            return (string) $request->getUri();

        } catch (Exception $e) {
            Log::error('S3 presigned URL error: ' . $e->getMessage(), [
                'file_path' => $filePath ?? 'null',
                'bucket' => $this->bucket ?? 'null'
            ]);
            return '';
        }
    }
    
    /**
     * Get S3 client for debugging purposes
     *
     * @return S3Client
     */
    public function getS3Client(): S3Client
    {
        return $this->s3Client;
    }
    
    /**
     * Get Rekognition client for debugging purposes
     *
     * @return RekognitionClient
     */
    public function getRekognitionClient(): RekognitionClient
    {
        return $this->rekognitionClient;
    }
    
    /**
     * Get bucket name
     *
     * @return string
     */
    public function getBucket(): string
    {
        return $this->bucket;
    }
    
    /**
     * Get region
     *
     * @return string
     */
    public function getRegion(): string
    {
        return $this->region;
    }
}