@extends('layouts.app')

@section('title', 'Match History - Face Recognition App')

@section('content')
<div class="row mb-4">
    <div class="col-md-8">
        <h1 class="text-white mb-0">
            <i class="fas fa-history me-3"></i>Match History
        </h1>
        <p class="text-white-50 mb-0">View all face matching attempts and results</p>
    </div>
    <div class="col-md-4 text-end">
        <a href="{{ route('face.match.form') }}" class="btn btn-light">
            <i class="fas fa-search me-2"></i>New Match
        </a>
    </div>
</div>

@if($matches->count() > 0)
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-list me-2"></i>All Matches ({{ $matches->total() }} total)
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Uploaded Photo</th>
                            <th>Match Result</th>
                            <th>Similarity</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($matches as $match)
                            <tr>
                                <td>
                                    <img src="{{ $match->uploaded_photo_url }}" 
                                         class="rounded" 
                                         style="width: 60px; height: 60px; object-fit: cover;"
                                         alt="Uploaded photo">
                                </td>
                                <td>
                                    @if($match->matchedPerson)
                                        <div class="d-flex align-items-center">
                                            <img src="{{ $match->matchedPerson->photo_url }}" 
                                                 class="rounded me-2" 
                                                 style="width: 40px; height: 40px; object-fit: cover;"
                                                 alt="{{ $match->matchedPerson->name }}">
                                            <div>
                                                <strong>{{ $match->matchedPerson->name }}</strong>
                                                <br>
                                                <small class="text-muted">Registered person</small>
                                            </div>
                                        </div>
                                    @else
                                        <div class="text-center">
                                            <i class="fas fa-user-slash text-muted"></i>
                                            <br>
                                            <small class="text-muted">No match</small>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @if($match->similarity_score > 0)
                                        <div class="d-flex align-items-center">
                                            <div class="progress me-2" style="width: 60px; height: 8px;">
                                                <div class="progress-bar 
                                                    @if($match->similarity_score >= 80) bg-success 
                                                    @elseif($match->similarity_score >= 60) bg-warning 
                                                    @else bg-danger 
                                                    @endif" 
                                                     style="width: {{ $match->similarity_score }}%">
                                                </div>
                                            </div>
                                            <span class="
                                                @if($match->similarity_score >= 80) text-success 
                                                @elseif($match->similarity_score >= 60) text-warning 
                                                @else text-danger 
                                                @endif">
                                                {{ number_format($match->similarity_score, 1) }}%
                                            </span>
                                        </div>
                                    @else
                                        <span class="text-muted">0%</span>
                                    @endif
                                </td>
                                <td>
                                    @if($match->is_match)
                                        <span class="badge bg-success">
                                            <i class="fas fa-check me-1"></i>Match
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-times me-1"></i>No Match
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <div>{{ $match->created_at->format('M d, Y') }}</div>
                                    <small class="text-muted">{{ $match->created_at->format('g:i A') }}</small>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#matchModal{{ $match->id }}">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>

                            <!-- Match Detail Modal -->
                            <div class="modal fade" id="matchModal{{ $match->id }}" tabindex="-1">
                                <div class="modal-dialog modal-xl">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">
                                                <i class="fas fa-search me-2"></i>Match Details #{{ $match->id }}
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row">
                                                <!-- Uploaded Photo -->
                                                <div class="col-md-6 mb-4">
                                                    <h6 class="mb-3">
                                                        <i class="fas fa-upload me-2"></i>Uploaded Photo
                                                    </h6>
                                                    <div class="text-center">
                                                        <img src="{{ $match->uploaded_photo_url }}" 
                                                             class="img-fluid rounded shadow" 
                                                             style="max-height: 300px;"
                                                             alt="Uploaded photo">
                                                    </div>
                                                </div>

                                                <!-- Match Result -->
                                                <div class="col-md-6 mb-4">
                                                    @if($match->matchedPerson)
                                                        <h6 class="mb-3">
                                                            <i class="fas fa-user-check me-2 text-success"></i>Matched Person
                                                        </h6>
                                                        <div class="text-center">
                                                            <img src="{{ $match->matchedPerson->photo_url }}" 
                                                                 class="img-fluid rounded shadow mb-3" 
                                                                 style="max-height: 300px;"
                                                                 alt="{{ $match->matchedPerson->name }}">
                                                            <h5>{{ $match->matchedPerson->name }}</h5>
                                                        </div>
                                                    @else
                                                        <h6 class="mb-3">
                                                            <i class="fas fa-user-slash me-2 text-muted"></i>No Match Found
                                                        </h6>
                                                        <div class="text-center d-flex align-items-center justify-content-center" style="height: 300px;">
                                                            <div>
                                                                <i class="fas fa-user-slash mb-3" style="font-size: 4rem; color: var(--text-secondary);"></i>
                                                                <p class="text-muted">No matching person found</p>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- Match Statistics -->
                                            <div class="row mt-4">
                                                <div class="col-12">
                                                    <h6>Match Statistics</h6>
                                                    <div class="row">
                                                        <div class="col-md-3">
                                                            <div class="text-center p-3 border rounded">
                                                                <div class="h4 mb-1 
                                                                    @if($match->similarity_score >= 80) text-success 
                                                                    @elseif($match->similarity_score >= 60) text-warning 
                                                                    @else text-danger 
                                                                    @endif">
                                                                    {{ $match->similarity_score ? number_format($match->similarity_score, 1) . '%' : '0%' }}
                                                                </div>
                                                                <small class="text-muted">Similarity</small>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="text-center p-3 border rounded">
                                                                <div class="h4 mb-1">
                                                                    @if($match->is_match)
                                                                        <i class="fas fa-check text-success"></i>
                                                                    @else
                                                                        <i class="fas fa-times text-danger"></i>
                                                                    @endif
                                                                </div>
                                                                <small class="text-muted">Match Status</small>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="text-center p-3 border rounded">
                                                                <div class="h6 mb-1">{{ $match->created_at->format('M d, Y') }}</div>
                                                                <small class="text-muted">Date</small>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="text-center p-3 border rounded">
                                                                <div class="h6 mb-1">{{ $match->created_at->format('g:i A') }}</div>
                                                                <small class="text-muted">Time</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    @if($matches->hasPages())
        <div class="row mt-4">
            <div class="col-12 d-flex justify-content-center">
                <div class="card">
                    <div class="card-body">
                        {{ $matches->links() }}
                    </div>
                </div>
            </div>
        </div>
    @endif
@else
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-search-minus mb-4" style="font-size: 4rem; color: var(--text-secondary);"></i>
                    <h4 class="mb-3">No Match History</h4>
                    <p class="text-muted mb-4">No face matching attempts have been made yet. Start by uploading a photo to match.</p>
                    <a href="{{ route('face.match.form') }}" class="btn btn-success btn-lg">
                        <i class="fas fa-search me-2"></i>Start Matching
                    </a>
                </div>
            </div>
        </div>
    </div>
@endif
@endsection
