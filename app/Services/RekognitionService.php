<?php

namespace App\Services;

use Aws\Rekognition\RekognitionClient;
use Aws\S3\S3Client;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class RekognitionService
{
    private $rekognitionClient;
    private $s3Client;
    private $useLocalStorage;
    private $collectionId;
    private $bucket;

    public function __construct()
    {
        // Always use local storage - no AWS services needed
        $this->useLocalStorage = true;
        $this->collectionId = env('AWS_REKOGNITION_COLLECTION_ID', 'face-recognition-collection');
    }

    /**
     * Create a collection if it doesn't exist
     */
    public function createCollectionIfNotExists(): bool
    {
        if ($this->useLocalStorage) {
            // For local storage, we'll just ensure the directories exist
            Storage::disk('public')->makeDirectory('faces');
            Storage::disk('public')->makeDirectory('matches');
            Log::info("Local storage directories ensured");
            return true;
        }
        
        try {
            $this->rekognitionClient->describeCollection([
                'CollectionId' => $this->collectionId
            ]);
            return true;
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'ResourceNotFoundException') !== false) {
                try {
                    $this->rekognitionClient->createCollection([
                        'CollectionId' => $this->collectionId
                    ]);
                    Log::info("Created Rekognition collection: {$this->collectionId}");
                    return true;
                } catch (Exception $createException) {
                    Log::error("Failed to create collection: " . $createException->getMessage());
                    return false;
                }
            }
            Log::error("Error checking collection: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Index a face in the collection
     */
    public function indexFace(string $s3Key, string $externalImageId = null): array
    {
        if ($this->useLocalStorage) {
            // Generate a mock face ID for local storage
            $faceId = 'local_' . Str::random(40);
            Log::info("Mock face indexed with ID: {$faceId} for file: {$s3Key}");
            return [
                'success' => true,
                'face_id' => $faceId,
                'message' => 'Face indexed successfully'
            ];
        }
        
        try {
            $this->createCollectionIfNotExists();

            $result = $this->rekognitionClient->indexFaces([
                'CollectionId' => $this->collectionId,
                'Image' => [
                    'S3Object' => [
                        'Bucket' => $this->bucket,
                        'Name' => $s3Key,
                    ],
                ],
                'ExternalImageId' => $externalImageId,
                'MaxFaces' => 1,
                'QualityFilter' => 'AUTO',
                'DetectionAttributes' => ['ALL']
            ]);

            if (!empty($result['FaceRecords'])) {
                $faceId = $result['FaceRecords'][0]['Face']['FaceId'];
                Log::info("Successfully indexed face with ID: {$faceId}");
                return $faceId;
            }

            Log::warning("No faces found in the image for indexing");
            return null;

        } catch (Exception $e) {
            Log::error("Error indexing face: " . $e->getMessage());
            throw new Exception("Failed to index face: " . $e->getMessage());
        }
    }

    /**
     * Search for faces in the collection using an image
     */
    public function searchFacesByImage(string $s3Key): array
    {
        if ($this->useLocalStorage) {
            // Mock face matching - simulate finding matches with random similarity
            $matches = [];
            
            // Get all registered people to simulate matching
            $people = \App\Models\Person::where('is_active', true)->get();
            
            if ($people->count() > 0) {
                // Simulate finding a match with the first person (for demo purposes)
                $randomPerson = $people->random();
                $similarity = rand(75, 95); // Random similarity between 75-95%
                
                $matches[] = [
                    'face_id' => $randomPerson->rekognition_face_id,
                    'similarity' => $similarity,
                    'external_image_id' => $randomPerson->name . '_' . $randomPerson->id
                ];
            }
            
            Log::info("Mock face search found " . count($matches) . " matches");
            return $matches;
        }
        
        try {
            $this->createCollectionIfNotExists();

            $result = $this->rekognitionClient->searchFacesByImage([
                'CollectionId' => $this->collectionId,
                'Image' => [
                    'S3Object' => [
                        'Bucket' => $this->bucket,
                        'Name' => $s3Key,
                    ],
                ],
                'FaceMatchThreshold' => (float) env('AWS_REKOGNITION_FACE_MATCH_THRESHOLD', 80),
                'MaxFaces' => 10
            ]);

            $matches = [];
            if (!empty($result['FaceMatches'])) {
                foreach ($result['FaceMatches'] as $match) {
                    $matches[] = [
                        'face_id' => $match['Face']['FaceId'],
                        'similarity' => $match['Similarity'],
                        'external_image_id' => $match['Face']['ExternalImageId'] ?? null
                    ];
                }
            }

            Log::info("Found " . count($matches) . " face matches");
            return $matches;

        } catch (Exception $e) {
            Log::error("Error searching faces: " . $e->getMessage());
            throw new Exception("Failed to search faces: " . $e->getMessage());
        }
    }

    /**
     * Detect faces in an image
     */
    public function detectFaces(string $s3Key): array
    {
        if ($this->useLocalStorage) {
            // Mock face detection - assume there's always one face for demo
            $faceDetails = [
                [
                    'BoundingBox' => [
                        'Width' => 0.5,
                        'Height' => 0.7,
                        'Left' => 0.25,
                        'Top' => 0.15
                    ],
                    'Confidence' => 99.5,
                    'Landmarks' => [],
                    'Pose' => [
                        'Roll' => 0.1,
                        'Yaw' => 2.3,
                        'Pitch' => 4.5
                    ],
                    'Quality' => [
                        'Brightness' => 80.5,
                        'Sharpness' => 92.1
                    ]
                ]
            ];
            
            Log::info("Mock face detection found 1 face in: {$s3Key}");
            return [
                'success' => true,
                'face_count' => 1,
                'faces' => $faceDetails
            ];
        }
        
        try {
            $result = $this->rekognitionClient->detectFaces([
                'Image' => [
                    'S3Object' => [
                        'Bucket' => $this->bucket,
                        'Name' => $s3Key,
                    ],
                ],
                'Attributes' => ['ALL']
            ]);

            return $result['FaceDetails'] ?? [];

        } catch (Exception $e) {
            Log::error("Error detecting faces: " . $e->getMessage());
            throw new Exception("Failed to detect faces: " . $e->getMessage());
        }
    }

    /**
     * Delete a face from the collection
     */
    public function deleteFace(string $faceId): bool
    {
        if ($this->useLocalStorage) {
            Log::info("Mock face deletion for ID: {$faceId}");
            return true;
        }
        
        try {
            $this->rekognitionClient->deleteFaces([
                'CollectionId' => $this->collectionId,
                'FaceIds' => [$faceId]
            ]);

            Log::info("Successfully deleted face: {$faceId}");
            return true;

        } catch (Exception $e) {
            Log::error("Error deleting face: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Upload file to local storage
     */
    public function uploadToS3($file, string $directory = 'faces'): array
    {
        try {
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $directory . '/' . $fileName;

            // Store file using Laravel's Storage facade
            $storedPath = Storage::disk('public')->putFileAs($directory, $file, $fileName);
            
            // Generate public URL
            $publicUrl = Storage::disk('public')->url($storedPath);

            Log::info("File uploaded to local storage: {$storedPath}");

            return [
                's3_key' => $storedPath,
                's3_url' => $publicUrl,
                'file_name' => $fileName
            ];

        } catch (Exception $e) {
            Log::error("Error uploading to local storage: " . $e->getMessage());
            throw new Exception("Failed to upload file: " . $e->getMessage());
        }
    }

    /**
     * Get public URL for local file
     */
    public function getPresignedUrl(string $filePath, int $expiresIn = 3600): string
    {
        try {
            // For local storage, return the public URL
            return Storage::disk('public')->url($filePath);

        } catch (Exception $e) {
            Log::error("Error generating local file URL: " . $e->getMessage());
            throw new Exception("Failed to generate file URL: " . $e->getMessage());
        }
    }
}
