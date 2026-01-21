@extends('layouts.app')

@section('content')
    <div id="users-page" data-index-url="{{ route('users.index') }}" data-base-url="{{ url('users') }}">
        <div id="users-alerts" class="mb-3"></div>
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <div class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <input
                            type="text"
                            id="search-users"
                            class="form-control"
                            placeholder="Search by first name, last name, email, or phone"
                            value="{{ $search }}"
                        >
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="status-filter">
                            <option value="">All statuses</option>
                            <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <div class="form-check">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                id="with-trashed"
                                {{ $withTrashed ? 'checked' : '' }}
                            >
                            <label class="form-check-label" for="with-trashed">
                                Show deleted users
                            </label>
                        </div>
                    </div>
                    <div class="col-md-3 text-md-end">
                        <button type="button" class="btn btn-primary" id="add-user-btn">
                            Add User
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div id="users-table">
                    @include('users.partials._table', ['users' => $users])
                </div>
                <div id="users-pagination" class="mt-3">
                    @include('users.partials._pagination', ['users' => $users])
                </div>
            </div>
        </div>

        @include('users.partials._form')
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('js/users.js') }}"></script>
@endsection
