@extends('layouts.app')

@section('title', 'Register Person - Face Recognition App')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="fas fa-user-plus me-2"></i>Register New Person
                </h5>
            </div>
            <div class="card-body">
                <form action="{{ route('face.register') }}" method="POST" enctype="multipart/form-data" id="registerForm">
                    @csrf
                    
                    <!-- Name Input -->
                    <div class="mb-4">
                        <label for="name" class="form-label">
                            <i class="fas fa-user me-2"></i>Person's Name
                        </label>
                        <input type="text" 
                               class="form-control @error('name') is-invalid @enderror" 
                               id="name" 
                               name="name" 
                               value="{{ old('name') }}" 
                               placeholder="Enter the person's full name"
                               required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Photo Upload -->
                    <div class="mb-4">
                        <label for="photo" class="form-label">
                            <i class="fas fa-camera me-2"></i>Photo Upload
                        </label>
                        <div class="upload-zone" id="uploadZone">
                            <div class="upload-content">
                                <i class="fas fa-cloud-upload-alt upload-icon"></i>
                                <h5>Drag & Drop Photo Here</h5>
                                <p class="text-muted mb-3">or click to browse files</p>
                                <input type="file" 
                                       class="form-control d-none @error('photo') is-invalid @enderror" 
                                       id="photo" 
                                       name="photo" 
                                       accept="image/jpeg,image/png,image/jpg"
                                       required>
                                <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('photo').click()">
                                    <i class="fas fa-folder-open me-2"></i>Choose File
                                </button>
                            </div>
                        </div>
                        @error('photo')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted mt-2">
                            <i class="fas fa-info-circle me-1"></i>
                            Upload a clear, front-facing photo (JPEG/PNG, max 5MB). Ensure only one face is visible.
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

                    <!-- Guidelines -->
                    <div class="alert alert-info">
                        <h6><i class="fas fa-lightbulb me-2"></i>Photo Guidelines</h6>
                        <ul class="mb-0">
                            <li>Use a clear, high-quality photo</li>
                            <li>Ensure the face is well-lit and front-facing</li>
                            <li>Only one person should be visible in the photo</li>
                            <li>Avoid sunglasses, hats, or face coverings</li>
                            <li>Supported formats: JPEG, PNG (max 5MB)</li>
                        </ul>
                    </div>

                    <!-- Submit Button -->
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="{{ route('face.index') }}" class="btn btn-outline-secondary me-md-2">
                            <i class="fas fa-arrow-left me-2"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <span class="btn-text">
                                <i class="fas fa-save me-2"></i>Register Person
                            </span>
                            <span class="loading-spinner">
                                <i class="fas fa-spinner fa-spin me-2"></i>Processing...
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
    document.getElementById('registerForm').addEventListener('submit', function(e) {
        const name = document.getElementById('name').value.trim();
        const photo = document.getElementById('photo').files[0];

        if (!name) {
            e.preventDefault();
            alert('Please enter the person\'s name.');
            document.getElementById('name').focus();
            return;
        }

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
