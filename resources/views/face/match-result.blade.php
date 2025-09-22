@extends('layouts.app')

@section('title', 'Match Results - Face Recognition App')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="fas fa-search-plus me-2"></i>Face Match Results
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Uploaded Photo -->
                    <div class="col-md-6 mb-4">
                        <div class="text-center">
                            <h6 class="mb-3">
                                <i class="fas fa-upload me-2"></i>Uploaded Photo
                            </h6>
                            <img src="{{ $uploadedPhotoUrl }}" 
                                 class="img-fluid rounded shadow" 
                                 style="max-height: 300px; object-fit: cover;"
                                 alt="Uploaded photo">
                        </div>
                    </div>

                    @if($selectedPerson)
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="card-title mb-0">
                                        <i class="fas fa-user-check me-2"></i>Selected Person for Comparison
                                    </h6>
                                </div>
                                <div class="card-body text-center">
                                    @if(isset($selectedPerson->photo_url) && $selectedPerson->photo_url)
                                        <img src="{{ $selectedPerson->photo_url }}" 
                                             alt="{{ $selectedPerson->name }}" 
                                             class="img-fluid rounded mb-3" 
                                             style="max-height: 200px; object-fit: cover;">
                                    @else
                                        <div class="bg-light rounded mb-3 d-flex align-items-center justify-content-center" 
                                             style="height: 200px;">
                                            <i class="fas fa-user fa-3x text-muted"></i>
                                        </div>
                                    @endif
                                    <h5 class="card-title">{{ $selectedPerson->name }}</h5>
                                    <p class="text-muted">Registered: {{ $selectedPerson->created_at->format('M d, Y') }}</p>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header bg-secondary text-white">
                                    <h6 class="card-title mb-0">
                                        <i class="fas fa-user-times me-2"></i>No Person Selected
                                    </h6>
                                </div>
                                <div class="card-body text-center">
                                    <i class="fas fa-question-circle fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No person was selected for comparison</p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Similarity Score -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="match-result">
                            @if($similarity > 0)
                                <div class="similarity-score 
                                    @if($similarity >= 80) similarity-high 
                                    @elseif($similarity >= 60) similarity-medium 
                                    @else similarity-low 
                                    @endif">
                                    {{ number_format($similarity, 1) }}%
                                </div>
                                <h5 class="mb-3">Similarity Score</h5>
                                
                                <!-- Progress Bar -->
                                <div class="progress mb-4" style="height: 20px;">
                                    <div class="progress-bar 
                                        @if($similarity >= 80) bg-success 
                                        @elseif($similarity >= 60) bg-warning 
                                        @else bg-danger 
                                        @endif" 
                                         role="progressbar" 
                                         style="width: {{ $similarity }}%">
                                    </div>
                                </div>

                                <!-- Match Status -->
                                @if($isMatch)
                                    <div class="alert alert-success">
                                        <i class="fas fa-check-circle me-2"></i>
                                        <strong>Match Found!</strong> High confidence match with {{ $selectedPerson->name }}.
                                    </div>
                                @elseif($similarity >= 60)
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <strong>Possible Match</strong> with {{ $selectedPerson ? $selectedPerson->name : 'registered person' }}, but confidence is moderate.
                                    </div>
                                @else
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Low Similarity</strong> - This appears to be a different person.
                                    </div>
                                @endif
                            @else
                                <div class="similarity-score similarity-low">0%</div>
                                <h5 class="mb-3">No Similarity Found</h5>
                                <div class="alert alert-info">
                                    <i class="fas fa-user-times me-2"></i>
                                    <strong>No Match</strong> - This face was not found in the registered database.
                                </div>
                            @endif

                            <!-- Confidence Explanation -->
                            <div class="row mt-4">
                                <div class="col-md-4 text-center">
                                    <div class="p-3 border rounded">
                                        <i class="fas fa-check-circle text-success mb-2" style="font-size: 2rem;"></i>
                                        <h6>High Confidence</h6>
                                        <small class="text-muted">80%+ similarity</small>
                                    </div>
                                </div>
                                <div class="col-md-4 text-center">
                                    <div class="p-3 border rounded">
                                        <i class="fas fa-exclamation-triangle text-warning mb-2" style="font-size: 2rem;"></i>
                                        <h6>Moderate</h6>
                                        <small class="text-muted">60-79% similarity</small>
                                    </div>
                                </div>
                                <div class="col-md-4 text-center">
                                    <div class="p-3 border rounded">
                                        <i class="fas fa-times-circle text-danger mb-2" style="font-size: 2rem;"></i>
                                        <h6>Low/No Match</h6>
                                        <small class="text-muted">Below 60% similarity</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Match Details -->
                @if($faceMatch)
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <i class="fas fa-info-circle me-2"></i>Match Details
                                </h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Match ID:</strong> #{{ $faceMatch->id }}</p>
                                        <p><strong>Timestamp:</strong> {{ $faceMatch->created_at->format('M d, Y g:i A') }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Status:</strong> 
                                            @if($isMatch)
                                                <span class="badge bg-success">Match Found</span>
                                            @else
                                                <span class="badge bg-secondary">No Match</span>
                                            @endif
                                        </p>
                                        @if($selectedPerson)
                                            <p><strong>Matched Person:</strong> {{ $selectedPerson->name }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Action Buttons -->
                <div class="row mt-4">
                    <div class="col-12 text-center">
                        <a href="{{ route('face.match.form') }}" class="btn btn-success me-3">
                            <i class="fas fa-search me-2"></i>Match Another Photo
                        </a>
                        <a href="{{ route('face.history') }}" class="btn btn-outline-primary me-3">
                            <i class="fas fa-history me-2"></i>View Match History
                        </a>
                        @if(!$isMatch)
                            <a href="{{ route('face.register.form') }}" class="btn btn-primary">
                                <i class="fas fa-user-plus me-2"></i>Register This Person
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
