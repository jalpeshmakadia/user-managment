<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userModalLabel">Add User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="user-form" enctype="multipart/form-data" novalidate>
                <div class="modal-body">
                    <input type="hidden" id="user-id" name="user_id" value="">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="first_name">First Name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="last_name">Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="email">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="password">
                                Password
                                <small class="text-muted">(leave blank to keep current)</small>
                            </label>
                            <input type="password" class="form-control" id="password" name="password">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="status">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="phone">Phone</label>
                            <input type="text" class="form-control" id="phone" name="phone">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="avatar">Avatar</label>
                            <input type="file" class="form-control" id="avatar" name="avatar" accept="image/*">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" id="save-user-btn">
                        Save User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
