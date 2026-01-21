@if ($users->hasPages())
    <div class="d-flex justify-content-end">
        {{ $users->links() }}
    </div>
@endif
