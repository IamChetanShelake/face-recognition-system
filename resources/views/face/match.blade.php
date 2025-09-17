@extends('layouts.app')

@section('title', 'Match Face - Face Recognition App')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="fas fa-search me-2"></i>Match Face
                </h5>
            </div>
            <div class="card-body">
                <form action="{{ route('face.match') }}" method="POST" enctype="multipart/form-data" id="matchForm">
                    @csrf
                    
                    <div class="mb-4">
                        <label for="person_id" class="form-label fw-bold">Select Person to Match Against</label>
                        <select name="person_id" id="person_id" class="form-select" required>
                            <option value="">Choose a registered person...</option>
                            @foreach(\App\Models\Person::where('is_active', true)->orderBy('name')->get() as $person)
                                <option value="{{ $person->id }}" {{ old('person_id') == $person->id ? 'selected' : '' }}>
                                    {{ $person->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('person_id')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="photo" class="form-label fw-bold">Upload Photo to Match</label>
                        <div class="upload-zone" id="uploadZone">
                            <div class="upload-content">
                                <i class="fas fa-cloud-upload-alt upload-icon"></i>
                                <p class="upload-text">Drag & drop your photo here or click to browse</p>
                                <p class="upload-subtext">Supports: JPG, PNG, GIF (Max: 5MB)</p>
                            </div>
                            <input type="file" name="photo" id="photo" class="form-control d-none" accept="image/*" required>
                        </div>
                        @error('photo')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted mt-2">
                            <i class="fas fa-info-circle me-1"></i>
                            Upload any photo containing a face (JPEG/PNG, max 5MB). The system will search for matches.
                        </small>
                    </div>

                    <!-- Photo Preview -->
                    <div class="mb-4 d-none" id="photoPreview">
                        <label class="form-label">Photo Preview</label>
                        <div class="text-center">
                            <img id="previewImage" class="photo-preview" alt="Photo preview">
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removePhoto()">
                                    <i class="fas fa-trash me-1"></i>Remove Photo
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Information -->
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle me-2"></i>How Face Matching Works</h6>
                        <ul class="mb-0">
                            <li>Upload any photo containing one or more faces</li>
                            <li>The system will analyze the faces and search for matches</li>
                            <li>You'll see similarity percentages for any matches found</li>
                            <li>Higher percentages indicate stronger matches</li>
                            <li>The system works best with clear, front-facing photos</li>
                        </ul>
                    </div>

                    <!-- Submit Button -->
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="{{ route('face.index') }}" class="btn btn-outline-secondary me-md-2">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                        <button type="submit" class="btn btn-success">
                            <span class="btn-text">
                                <i class="fas fa-search me-2"></i>Find Matches
                            </span>
                            <span class="loading-spinner">
                                <i class="fas fa-spinner fa-spin me-2"></i>Searching...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const uploadZone = document.getElementById('uploadZone');
    const photoInput = document.getElementById('photo');
    const photoPreview = document.getElementById('photoPreview');
    const previewImage = document.getElementById('previewImage');

    // Click to upload
    uploadZone.addEventListener('click', function() {
        photoInput.click();
    });

    // Drag and drop functionality
    uploadZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        uploadZone.classList.add('dragover');
    });

    uploadZone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        uploadZone.classList.remove('dragover');
    });

    uploadZone.addEventListener('drop', function(e) {
        e.preventDefault();
        uploadZone.classList.remove('dragover');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            photoInput.files = files;
            handleFileSelect(files[0]);
        }
    });

    // File input change
    photoInput.addEventListener('change', function(e) {
        if (e.target.files.length > 0) {
            handleFileSelect(e.target.files[0]);
        }
    });

    function handleFileSelect(file) {
        // Validate file type
        if (!file.type.match('image.*')) {
            alert('Please select a valid image file (JPEG, PNG).');
            return;
        }

        // Validate file size (5MB)
        if (file.size > 5 * 1024 * 1024) {
            alert('File size must be less than 5MB.');
            return;
        }

        // Show preview
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImage.src = e.target.result;
            photoPreview.classList.remove('d-none');
            uploadZone.style.display = 'none';
        };
        reader.readAsDataURL(file);
    }

    // Remove photo function
    window.removePhoto = function() {
        photoInput.value = '';
        photoPreview.classList.add('d-none');
        uploadZone.style.display = 'block';
        previewImage.src = '';
    };

    // Form validation
    document.getElementById('matchForm').addEventListener('submit', function(e) {
        const photo = document.getElementById('photo').files[0];

        if (!photo) {
            e.preventDefault();
            alert('Please select a photo to upload.');
            return;
        }

        // Show loading state
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.classList.add('loading');
        submitBtn.disabled = true;
    });
});
</script>
@endpush
@endsection
