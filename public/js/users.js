$(function () {
    const $page = $('#users-page');
    if (!$page.length) {
        return;
    }

    const indexUrl = $page.data('index-url');
    const baseUrl = $page.data('base-url');
    const modalEl = document.getElementById('userModal');
    const userModal = modalEl ? new bootstrap.Modal(modalEl) : null;
    const $form = $('#user-form');
    const $alerts = $('#users-alerts');
    const $passwordWrapper = $('#password-wrapper');
    const $saveBtn = $('#save-user-btn');
    let isLoading = false;

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        },
    });

    const resetErrors = () => {
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').text('');
    };

    const resetForm = () => {
        $form[0].reset();
        $('#user-id').val('');
        resetErrors();
        $passwordWrapper.show();
        $('#avatar-preview').hide();
        $('#avatar-preview-img').attr('src', '');
    };

    // Avatar preview
    $('#avatar').on('change', function (e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                $('#avatar-preview').show();
                $('#avatar-preview-img').attr('src', e.target.result);
            };
            reader.readAsDataURL(file);
        } else {
            $('#avatar-preview').hide();
        }
    });

    const showAlert = (type, message) => {
        if (!$alerts.length) {
            return;
        }

        const alert = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        $alerts.html(alert);
    };

    const setLoadingState = (loading) => {
        isLoading = loading;
        $saveBtn.prop('disabled', loading);
        if (loading) {
            $saveBtn.html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...');
        } else {
            $saveBtn.html('Save User');
        }
    };

    const loadUsers = (url) => {
        const query = {
            search: $('#search-users').val(),
            withTrashed: $('#with-trashed').is(':checked') ? 1 : 0,
            status: $('#status-filter').val(),
        };

        $('#users-table').addClass('opacity-50');
        $.get(url || indexUrl, query, (response) => {
            $('#users-table').html(response.table);
            $('#users-pagination').html(response.pagination);
        }).always(() => {
            $('#users-table').removeClass('opacity-50');
        });
    };

    let searchTimer = null;
    $('#search-users').on('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => loadUsers(), 400);
    });

    $('#status-filter').on('change', function () {
        loadUsers();
    });

    $('#with-trashed').on('change', function () {
        loadUsers();
    });

    $(document).on('click', '#users-pagination a', function (event) {
        event.preventDefault();
        loadUsers($(this).attr('href'));
    });

    $('#add-user-btn').on('click', function () {
        resetForm();
        $('#userModalLabel').text('Add User');
        userModal?.show();
    });

    $(document).on('click', '.edit-user', function () {
        resetForm();
        const userId = $(this).data('id');
        const $btn = $(this);
        $btn.prop('disabled', true);

        $.get(`${baseUrl}/${userId}`, (response) => {
            $('#user-id').val(userId);
            $('#first_name').val(response.user.first_name);
            $('#last_name').val(response.user.last_name);
            $('#email').val(response.user.email);
            $('#status').val(response.user.status);
            $('#phone').val(response.user.phone || '');
            $('#userModalLabel').text('Edit User');
            $passwordWrapper.hide();
            
            // Show existing avatar if available
            if (response.avatar_url) {
                $('#avatar-preview').show();
                $('#avatar-preview-img').attr('src', response.avatar_url);
            }
            
            userModal?.show();
        }).always(() => {
            $btn.prop('disabled', false);
        }).fail((xhr) => {
            if (xhr.status === 404) {
                showAlert('warning', 'User not found.');
            } else {
                showAlert('danger', 'Failed to load user data.');
            }
        });
    });

    $(document).on('click', '.delete-user', function () {
        if (!confirm('Delete this user?')) {
            return;
        }

        const userId = $(this).data('id');
        const $btn = $(this);
        $btn.prop('disabled', true);
        
        $.post(`${baseUrl}/${userId}`, { _method: 'DELETE' })
            .done((response) => {
                showAlert('success', response.message || 'User deleted successfully.');
                loadUsers();
            })
            .fail((xhr) => {
                if (xhr.status === 404) {
                    showAlert('warning', xhr.responseJSON?.message || 'User not found.');
                } else {
                    showAlert('danger', xhr.responseJSON?.message || 'Delete failed. Please try again.');
                }
            })
            .always(() => {
                $btn.prop('disabled', false);
            });
    });

    $(document).on('click', '.restore-user', function () {
        const userId = $(this).data('id');
        const $btn = $(this);
        $btn.prop('disabled', true);
        
        $.post(`${baseUrl}/${userId}/restore`, { _method: 'PUT' })
            .done((response) => {
                showAlert('success', response.message || 'User restored successfully.');
                loadUsers();
            })
            .fail((xhr) => {
                if (xhr.status === 404) {
                    showAlert('warning', xhr.responseJSON?.message || 'User not found.');
                } else {
                    showAlert('danger', xhr.responseJSON?.message || 'Restore failed. Please try again.');
                }
            })
            .always(() => {
                $btn.prop('disabled', false);
            });
    });

    // Add custom phone validation method
    $.validator.addMethod('phone', function (value, element) {
        if (this.optional(element)) {
            return true; // Phone is optional
        }
        // Match backend regex: /^\+?[0-9\s\-\(\)]+$/
        const phoneRegex = /^\+?[0-9\s\-\(\)]+$/;
        return phoneRegex.test(value);
    }, 'Please enter a valid phone number (e.g., +1234567890, (123) 456-7890, 123-456-7890)');

    // Add custom avatar MIME type validation method
    $.validator.addMethod('avatarMimeType', function (value, element) {
        if (this.optional(element)) {
            return true; // Avatar is optional
        }
        if (element.files && element.files.length > 0) {
            const file = element.files[0];
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            return allowedTypes.includes(file.type);
        }
        return true;
    }, 'Avatar must be a valid image file (JPEG, PNG, GIF, or WEBP).');

    $form.validate({
        ignore: [],
        errorClass: 'invalid-feedback',
        errorElement: 'div',
        errorPlacement: function (error, element) {
            const $feedback = $(element).next('.invalid-feedback');
            if ($feedback.length) {
                $feedback.text(error.text());
                return;
            }

            error.insertAfter(element);
        },
        highlight: function (element) {
            $(element).addClass('is-invalid');
        },
        unhighlight: function (element) {
            const $element = $(element);
            $element.removeClass('is-invalid');
            $element.next('.invalid-feedback').text('');
        },
        rules: {
            first_name: { required: true, maxlength: 100 },
            last_name: { required: true, maxlength: 100 },
            email: { required: true, email: true, maxlength: 255 },
            password: {
                required: () => $('#user-id').val() === '',
                minlength: 6,
            },
            status: { required: true },
            phone: { phone: true, maxlength: 20 },
            avatar: { avatarMimeType: true },
        },
        submitHandler: function (form) {
            if (isLoading) {
                return false;
            }

            resetErrors();
            setLoadingState(true);

            const formData = new FormData(form);
            const userId = $('#user-id').val();
            let url = baseUrl;

            if (userId) {
                url = `${baseUrl}/${userId}`;
                formData.append('_method', 'PUT');
            }

            $.ajax({
                url,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    userModal?.hide();
                    showAlert('success', response.message || 'User saved successfully.');
                    loadUsers();
                },
                error: (xhr) => {
                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON.errors || {};
                        showAlert('warning', 'Please fix the highlighted fields and try again.');
                        Object.keys(errors).forEach((field) => {
                            const message = errors[field][0];
                            const $input = $form.find(`[name="${field}"]`);
                            $input.addClass('is-invalid');
                            $input.next('.invalid-feedback').text(message);
                        });
                    } else {
                        const message = xhr.responseJSON?.message || 'Something went wrong. Please try again.';
                        showAlert('danger', message);
                    }
                },
                complete: () => {
                    setLoadingState(false);
                },
            });

            return false;
        },
    });
});
