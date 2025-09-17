@extends('layouts.app')

@section('title', 'Registered People - Face Recognition App')

@section('content')
<div class="row mb-4">
    <div class="col-md-8">
        <h1 class="text-white mb-0">
            <i class="fas fa-users me-3"></i>Registered People
        </h1>
        <p class="text-white-50 mb-0">Manage your face recognition database</p>
    </div>
    <div class="col-md-4 text-end">
        <a href="{{ route('face.register.form') }}" class="btn btn-light">
            <i class="fas fa-user-plus me-2"></i>Register New Person
        </a>
    </div>
</div>

@if($people->count() > 0)
    <div class="row">
        @foreach($people as $person)
            <div class="col-md-4 col-lg-3 mb-4">
                <div class="card person-card h-100">
                    <img src="{{ $person->photo_url }}" 
                         class="person-photo" 
                         alt="{{ $person->name }}"
                         loading="lazy">
                    <div class="card-body text-center">
                        <h6 class="card-title mb-2">{{ $person->name }}</h6>
                        <p class="text-muted small mb-3">
                            <i class="fas fa-calendar me-1"></i>
                            Registered {{ $person->created_at->diffForHumans() }}
                        </p>
                        <div class="d-flex justify-content-center gap-2">
                            <button class="btn btn-sm btn-outline-info" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#personModal{{ $person->id }}">
                                <i class="fas fa-eye"></i>
                            </button>
                            <form action="{{ route('face.people.delete', $person) }}" 
                                  method="POST" 
                                  class="d-inline"
                                  onsubmit="return confirm('Are you sure you want to remove {{ $person->name }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Person Detail Modal -->
            <div class="modal fade" id="personModal{{ $person->id }}" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-user me-2"></i>{{ $person->name }}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6 text-center">
                                    <img src="{{ $person->photo_url }}" 
                                         class="img-fluid rounded shadow" 
                                         style="max-height: 400px;"
                                         alt="{{ $person->name }}">
                                </div>
                                <div class="col-md-6">
                                    <h6>Person Details</h6>
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>Name:</strong></td>
                                            <td>{{ $person->name }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Registered:</strong></td>
                                            <td>{{ $person->created_at->format('M d, Y g:i A') }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Status:</strong></td>
                                            <td>
                                                @if($person->is_active)
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-secondary">Inactive</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Face ID:</strong></td>
                                            <td><code class="small">{{ $person->rekognition_face_id }}</code></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Matches:</strong></td>
                                            <td>{{ $person->faceMatches->count() }} times</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <form action="{{ route('face.people.delete', $person) }}" 
                                  method="POST" 
                                  class="d-inline"
                                  onsubmit="return confirm('Are you sure you want to remove {{ $person->name }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-trash me-2"></i>Remove Person
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Pagination -->
    @if($people->hasPages())
        <div class="row mt-4">
            <div class="col-12 d-flex justify-content-center">
                <div class="card">
                    <div class="card-body">
                        {{ $people->links() }}
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
                    <i class="fas fa-user-slash mb-4" style="font-size: 4rem; color: var(--text-secondary);"></i>
                    <h4 class="mb-3">No People Registered</h4>
                    <p class="text-muted mb-4">Start building your face recognition database by registering the first person.</p>
                    <a href="{{ route('face.register.form') }}" class="btn btn-primary btn-lg">
                        <i class="fas fa-user-plus me-2"></i>Register First Person
                    </a>
                </div>
            </div>
        </div>
    </div>
@endif
@endsection
