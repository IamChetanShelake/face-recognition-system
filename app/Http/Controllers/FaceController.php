<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Person;
use App\Models\FaceMatch;
use App\Services\RekognitionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class FaceController extends Controller
{
    private $rekognitionService;

    public function __construct(RekognitionService $rekognitionService)
    {
        $this->rekognitionService = $rekognitionService;
    }

    /**
     * Show the main dashboard
     */
    public function index()
    {
        $totalPeople = Person::where('is_active', true)->count();
        $totalMatches = FaceMatch::count();
        $recentPeople = Person::where('is_active', true)
            ->latest()
            ->take(5)
            ->get();
        $recentMatches = FaceMatch::with('matchedPerson')
            ->latest()
            ->take(5)
            ->get();

        return view('face.index', compact('totalPeople', 'totalMatches', 'recentPeople', 'recentMatches'));
    }

    /**
     * Show the registration form
     */
    public function showRegisterForm()
    {
        return view('face.register');
    }

    /**
     * Register a new person with their face
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'photo' => 'required|image|mimes:jpeg,png,jpg|max:5120', // 5MB max
        ]);

        try {
            Log::info('Person registration started', [
                'name' => $request->name,
                'photo_name' => $request->file('photo')->getClientOriginalName(),
                'photo_size' => $request->file('photo')->getSize()
            ]);
            
            // Upload photo to S3
            $uploadResult = $this->rekognitionService->uploadToS3($request->file('photo'), 'photos');
            
            if (!$uploadResult['success']) {
                Log::error('Failed to upload photo to S3', ['error' => $uploadResult['error']]);
                return back()->with('error', 'Failed to upload photo: ' . $uploadResult['error']);
            }
            
            Log::info('Photo uploaded for registration', [
                'file_path' => $uploadResult['s3_key']
            ]);
            
            // Detect faces in the uploaded image
            $faceDetection = $this->rekognitionService->detectFaces($uploadResult['s3_key']);
            
            Log::info('Face detection completed', [
                'success' => $faceDetection['success'],
                'face_count' => $faceDetection['face_count']
            ]);
            
            if (!$faceDetection['success']) {
                Log::error('Failed to detect faces in uploaded image', ['error' => $faceDetection['error']]);
                return back()->with('error', 'Failed to detect faces in the uploaded image: ' . $faceDetection['error']);
            }
            
            if ($faceDetection['face_count'] === 0) {
                Log::warning('No faces detected in uploaded image');
                return back()->with('error', 'No faces detected in the uploaded image. Please upload a clear photo with a visible face.');
            }
            
            if ($faceDetection['face_count'] > 1) {
                Log::warning('Multiple faces detected in uploaded image', ['count' => $faceDetection['face_count']]);
                return back()->with('error', 'Multiple faces detected. Please upload a photo with only one person.');
            }
            
            // Store person in database
            $person = Person::create([
                'name' => $request->name,
                'photo_s3_key' => $uploadResult['s3_key'],
                'is_active' => true
            ]);
            
            Log::info('Person registered successfully', [
                'person_id' => $person->id,
                'person_name' => $person->name
            ]);
            
            return redirect()->route('face.index')->with('success', 'Person registered successfully!');
            
        } catch (Exception $e) {
            Log::error('Person registration failed', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'name' => $request->name ?? 'not_provided'
            ]);
            return back()->with('error', 'Registration failed: ' . $e->getMessage());
        }
    }

    /**
     * Show the matching form
     */
    public function showMatchForm()
    {
        // Get all active people for the dropdown
        $people = Person::where('is_active', true)->get();
        return view('face.match', compact('people'));
    }

    /**
     * Match a face against registered people using AWS Rekognition
     */
    public function match(Request $request)
    {
        $request->validate([
            'person_id' => 'required|exists:people,id',
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
        ]);

        try {
            Log::info('Starting face matching process', [
                'person_id' => $request->person_id,
                'photo_name' => $request->file('photo')->getClientOriginalName()
            ]);
            
            // Get the selected person
            $selectedPerson = Person::where('id', $request->person_id)
                                  ->where('is_active', true)
                                  ->firstOrFail();
            
            Log::info('Selected person found', ['person_name' => $selectedPerson->name]);
            
            // Upload the new photo for matching
            $uploadResult = $this->rekognitionService->uploadToS3($request->file('photo'), 'uploads');
            
            if (!$uploadResult['success']) {
                Log::error('Failed to upload photo to S3', ['error' => $uploadResult['error']]);
                return back()->with('error', 'Failed to upload photo: ' . $uploadResult['error']);
            }
            
            Log::info('Photo uploaded successfully', [
                'file_path' => $uploadResult['s3_key']
            ]);
            
            // Compare faces using AWS Rekognition
            $comparisonResult = $this->rekognitionService->compareFaces(
                $selectedPerson->photo_s3_key,  // Registered photo
                $uploadResult['s3_key'],        // New uploaded photo
                85.0                            // Similarity threshold
            );
            
            Log::info('Face comparison completed', [
                'success' => $comparisonResult['success'],
                'highest_similarity' => $comparisonResult['highest_similarity'],
                'is_match' => $comparisonResult['is_match']
            ]);
            
            if (!$comparisonResult['success']) {
                Log::error('Face comparison failed', ['error' => $comparisonResult['error']]);
                return back()->with('error', 'Face comparison failed: ' . $comparisonResult['error']);
            }
            
            $similarity = $comparisonResult['highest_similarity'];
            $isMatch = $comparisonResult['is_match'];
            
            // Generate the S3 URL for the uploaded photo
            $s3Url = 'https://s3.' . $this->rekognitionService->getRegion() . '.amazonaws.com/' . 
                     $this->rekognitionService->getBucket() . '/' . $uploadResult['s3_key'];
            
            // Store the match result
            $faceMatch = FaceMatch::create([
                'uploaded_photo_s3_url' => $s3Url,
                'uploaded_photo_s3_key' => $uploadResult['s3_key'],
                'matched_person_id' => $selectedPerson->id,
                'similarity_score' => $similarity,
                'is_match' => $isMatch,
                'rekognition_response' => json_encode($comparisonResult)
            ]);
            
            Log::info('Match result saved to database', ['match_id' => $faceMatch->id]);
            
            // Generate presigned URL for the uploaded photo
            $uploadedPhotoUrl = $this->rekognitionService->getPresignedUrl($uploadResult['s3_key']);
            
            // Generate presigned URL for the selected person's photo
            if ($selectedPerson && $selectedPerson->photo_s3_key) {
                $selectedPerson->photo_url = $this->rekognitionService->getPresignedUrl($selectedPerson->photo_s3_key);
            }
            
            // Return JSON response for AJAX requests or redirect with data for regular requests
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'similarity' => $similarity,
                    'is_match' => $isMatch,
                    'message' => $isMatch ? 'Match found!' : 'No match found',
                    'face_match_id' => $faceMatch->id
                ]);
            }
            
            return view('face.match-result', compact('faceMatch', 'selectedPerson', 'similarity', 'isMatch', 'uploadedPhotoUrl'));

        } catch (Exception $e) {
            Log::error('Face matching error occurred', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'person_id' => $request->person_id ?? 'not_provided'
            ]);
            
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to process face matching: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to process face matching: ' . $e->getMessage());
        }
    }

    /**
     * Show all registered people
     */
    public function people()
    {
        $people = Person::where('is_active', true)
            ->latest()
            ->paginate(12);

        // Generate presigned URLs for photos
        foreach ($people as $person) {
            if ($person->photo_s3_key) {
                $person->photo_url = $this->rekognitionService->getPresignedUrl($person->photo_s3_key);
            } else {
                $person->photo_url = null;
            }
        }

        return view('face.people', compact('people'));
    }

    /**
     * Show match history
     */
    public function history()
    {
        $matches = FaceMatch::with('matchedPerson')
            ->latest()
            ->paginate(10);

        // Generate presigned URLs for photos
        foreach ($matches as $match) {
            if ($match->uploaded_photo_s3_key) {
                $match->uploaded_photo_url = $this->rekognitionService->getPresignedUrl($match->uploaded_photo_s3_key);
            } else {
                $match->uploaded_photo_url = null;
            }
            
            if ($match->matchedPerson && $match->matchedPerson->photo_s3_key) {
                $match->matchedPerson->photo_url = $this->rekognitionService->getPresignedUrl($match->matchedPerson->photo_s3_key);
            } else {
                $match->matchedPerson->photo_url = null;
            }
        }

        return view('face.history', compact('matches'));
    }

    /**
     * Delete a person
     */
    public function deletePerson(Person $person)
    {
        try {
            // Soft delete by marking as inactive
            $person->update(['is_active' => false]);

            return redirect()->route('face.people')
                ->with('success', "Successfully removed {$person->name}!");

        } catch (Exception $e) {
            Log::error('Failed to delete person: ' . $e->getMessage());
            
            return back()
                ->withErrors(['error' => 'Failed to delete person. Please try again.']);
        }
    }
}