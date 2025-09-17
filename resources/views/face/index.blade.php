@extends('layouts.app')

@section('title', 'Dashboard - Face Recognition App')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1 class="text-white mb-4">
            <i class="fas fa-tachometer-alt me-3"></i>Dashboard
        </h1>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-5">
    <div class="col-md-6 mb-4">
        <div class="stats-card">
            <i class="fas fa-users mb-3" style="font-size: 2rem;"></i>
            <div class="stats-number">{{ $totalPeople }}</div>
            <div class="stats-label">Registered People</div>
        </div>
    </div>
    <div class="col-md-6 mb-4">
        <div class="stats-card">
            <i class="fas fa-search mb-3" style="font-size: 2rem;"></i>
            <div class="stats-number">{{ $totalMatches }}</div>
            <div class="stats-label">Total Matches</div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mb-5">
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="fas fa-user-plus me-2"></i>Register New Person
                </h5>
            </div>
            <div class="card-body text-center">
                <i class="fas fa-user-plus mb-3" style="font-size: 3rem; color: var(--primary-color);"></i>
                <p class="text-muted mb-4">Add a new person to the face recognition system by uploading their photo and providing their name.</p>
                <a href="{{ route('face.register.form') }}" class="btn btn-primary btn-lg">
                    <i class="fas fa-plus me-2"></i>Register Person
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="fas fa-search me-2"></i>Match Face
                </h5>
            </div>
            <div class="card-body text-center">
                <i class="fas fa-search mb-3" style="font-size: 3rem; color: var(--accent-color);"></i>
                <p class="text-muted mb-4">Upload a photo to find matching faces in the registered database and see similarity scores.</p>
                <a href="{{ route('face.match.form') }}" class="btn btn-success btn-lg">
                    <i class="fas fa-search me-2"></i>Match Face
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="fas fa-clock me-2"></i>Recently Registered
                </h5>
            </div>
            <div class="card-body">
                @if($recentPeople->count() > 0)
                    <div class="list-group list-group-flush">
                        @foreach($recentPeople as $person)
                            <div class="list-group-item d-flex align-items-center border-0 px-0">
                                <div class="me-3">
                                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="fas fa-user text-white"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">{{ $person->name }}</h6>
                                    <small class="text-muted">{{ $person->created_at->diffForHumans() }}</small>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="text-center mt-3">
                        <a href="{{ route('face.people') }}" class="btn btn-outline-primary">
                            <i class="fas fa-users me-2"></i>View All People
                        </a>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-user-slash mb-3" style="font-size: 2rem; color: var(--text-secondary);"></i>
                        <p class="text-muted">No people registered yet.</p>
                        <a href="{{ route('face.register.form') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Register First Person
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="fas fa-history me-2"></i>Recent Matches
                </h5>
            </div>
            <div class="card-body">
                @if($recentMatches->count() > 0)
                    <div class="list-group list-group-flush">
                        @foreach($recentMatches as $match)
                            <div class="list-group-item d-flex align-items-center border-0 px-0">
                                <div class="me-3">
                                    @if($match->is_match)
                                        <div class="bg-success rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="fas fa-check text-white"></i>
                                        </div>
                                    @else
                                        <div class="bg-danger rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="fas fa-times text-white"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">
                                        @if($match->matchedPerson)
                                            Match: {{ $match->matchedPerson->name }}
                                        @else
                                            No Match Found
                                        @endif
                                    </h6>
                                    <small class="text-muted">
                                        {{ $match->similarity_score ? number_format($match->similarity_score, 1) . '% similarity' : 'No similarity' }} • 
                                        {{ $match->created_at->diffForHumans() }}
                                    </small>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="text-center mt-3">
                        <a href="{{ route('face.history') }}" class="btn btn-outline-primary">
                            <i class="fas fa-history me-2"></i>View Full History
                        </a>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-search-minus mb-3" style="font-size: 2rem; color: var(--text-secondary);"></i>
                        <p class="text-muted">No matches performed yet.</p>
                        <a href="{{ route('face.match.form') }}" class="btn btn-success">
                            <i class="fas fa-search me-2"></i>Start Matching
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Getting Started Guide -->
@if($totalPeople == 0)
<div class="row mt-5">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="fas fa-rocket me-2"></i>Getting Started
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 text-center mb-4">
                        <div class="mb-3">
                            <i class="fas fa-user-plus" style="font-size: 3rem; color: var(--primary-color);"></i>
                        </div>
                        <h6>1. Register People</h6>
                        <p class="text-muted small">Start by registering people with their photos and names.</p>
                    </div>
                    <div class="col-md-4 text-center mb-4">
                        <div class="mb-3">
                            <i class="fas fa-search" style="font-size: 3rem; color: var(--accent-color);"></i>
                        </div>
                        <h6>2. Match Faces</h6>
                        <p class="text-muted small">Upload photos to find matching faces in your database.</p>
                    </div>
                    <div class="col-md-4 text-center mb-4">
                        <div class="mb-3">
                            <i class="fas fa-chart-line" style="font-size: 3rem; color: var(--warning-color);"></i>
                        </div>
                        <h6>3. View Results</h6>
                        <p class="text-muted small">See similarity scores and manage your face database.</p>
                    </div>
                </div>
                <div class="text-center">
                    <a href="{{ route('face.register.form') }}" class="btn btn-primary btn-lg me-3">
                        <i class="fas fa-play me-2"></i>Get Started
                    </a>
                    <a href="#" class="btn btn-outline-primary btn-lg" data-bs-toggle="modal" data-bs-target="#helpModal">
                        <i class="fas fa-question-circle me-2"></i>Learn More
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Help Modal -->
<div class="modal fade" id="helpModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-info-circle me-2"></i>How to Use Face Recognition App
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12">
                        <h6><i class="fas fa-user-plus me-2 text-primary"></i>Registering People</h6>
                        <p class="mb-3">Upload clear, front-facing photos with good lighting. Each photo should contain only one person's face.</p>
                        
                        <h6><i class="fas fa-search me-2 text-success"></i>Matching Faces</h6>
                        <p class="mb-3">Upload any photo containing a face to search for matches. The system will show similarity percentages.</p>
                        
                        <h6><i class="fas fa-chart-bar me-2 text-warning"></i>Understanding Results</h6>
                        <ul class="mb-3">
                            <li><strong>80%+ similarity:</strong> High confidence match</li>
                            <li><strong>60-79% similarity:</strong> Possible match</li>
                            <li><strong>Below 60%:</strong> Likely different person</li>
                        </ul>
                        
                        <h6><i class="fas fa-tips me-2 text-info"></i>Best Practices</h6>
                        <ul>
                            <li>Use high-quality, well-lit photos</li>
                            <li>Ensure faces are clearly visible and front-facing</li>
                            <li>Avoid photos with multiple people for registration</li>
                            <li>JPEG and PNG formats work best</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Got it!</button>
            </div>
        </div>
    </div>
</div>
@endsection
