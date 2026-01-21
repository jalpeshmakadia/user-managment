<div class="table-responsive">
    <table class="table table-striped align-middle">
        <thead>
            <tr>
                <th>Avatar</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Status</th>
                <th>Phone</th>
                <th>Email Verified</th>
                <th>Last Login</th>
                <th>Last IP</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($users as $user)
                <tr @if ($user->deleted_at) class="table-warning" @endif>
                    <td>
                        @if ($user->avatar_url)
                            <img
                                src="{{ $user->avatar_url }}"
                                alt="Avatar"
                                class="rounded-circle"
                                width="40"
                                height="40"
                            >
                        @else
                            <div
                                class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center"
                                style="width: 40px; height: 40px;"
                            >
                                {{ strtoupper(substr($user->full_name ?: $user->email, 0, 1)) }}
                            </div>
                        @endif
                    </td>
                    <td>{{ $user->full_name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>
                        <span class="badge {{ $user->status === \App\Enums\UserStatus::Active ? 'bg-success' : 'bg-secondary' }}">
                            {{ $user->status->label() }}
                        </span>
                    </td>
                    <td>{{ $user->phone ?? '—' }}</td>
                    <td>{{ $user->email_verified_at?->format('Y-m-d H:i') ?? '—' }}</td>
                    <td>{{ $user->last_login_at?->format('Y-m-d H:i') ?? '—' }}</td>
                    <td>{{ $user->last_login_ip ?? '—' }}</td>
                    <td class="text-end">
                        @if ($user->deleted_at)
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-success restore-user"
                                data-id="{{ $user->id }}"
                            >
                                Restore
                            </button>
                        @else
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-primary me-1 edit-user"
                                data-id="{{ $user->id }}"
                            >
                                Edit
                            </button>
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-danger delete-user"
                                data-id="{{ $user->id }}"
                            >
                                Delete
                            </button>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center text-muted">
                        No users found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
