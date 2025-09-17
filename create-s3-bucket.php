<?php

require_once 'vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$s3Client = new S3Client([
    'version' => 'latest',
    'region' => $_ENV['AWS_DEFAULT_REGION'],
    'credentials' => [
        'key' => $_ENV['AWS_ACCESS_KEY_ID'],
        'secret' => $_ENV['AWS_SECRET_ACCESS_KEY'],
    ],
]);

$bucketName = $_ENV['AWS_BUCKET'];

try {
    // Check if bucket already exists
    if ($s3Client->doesBucketExist($bucketName)) {
        echo "✅ Bucket '{$bucketName}' already exists!\n";
    } else {
        // Create the bucket
        $result = $s3Client->createBucket([
            'Bucket' => $bucketName,
            'CreateBucketConfiguration' => [
                'LocationConstraint' => $_ENV['AWS_DEFAULT_REGION'],
            ],
        ]);
        
        echo "✅ Successfully created S3 bucket: {$bucketName}\n";
        echo "📍 Region: {$_ENV['AWS_DEFAULT_REGION']}\n";
        echo "🔗 Bucket URL: " . $result['Location'] . "\n";
    }
    
    // Set bucket policy for Rekognition access
    $policy = [
        'Version' => '2012-10-17',
        'Statement' => [
            [
                'Sid' => 'RekognitionAccess',
                'Effect' => 'Allow',
                'Principal' => [
                    'Service' => 'rekognition.amazonaws.com'
                ],
                'Action' => [
                    's3:GetObject',
                    's3:GetObjectVersion'
                ],
                'Resource' => "arn:aws:s3:::{$bucketName}/*"
            ]
        ]
    ];
    
    $s3Client->putBucketPolicy([
        'Bucket' => $bucketName,
        'Policy' => json_encode($policy)
    ]);
    
    echo "✅ Bucket policy configured for Rekognition access\n";
    
} catch (AwsException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    
    if ($e->getAwsErrorCode() === 'BucketAlreadyOwnedByYou') {
        echo "✅ Bucket already exists and is owned by you!\n";
    } elseif ($e->getAwsErrorCode() === 'BucketAlreadyExists') {
        echo "❌ Bucket name is already taken. Please choose a different name.\n";
    }
}

echo "\n🚀 Ready to test face recognition with AWS Rekognition!\n";
