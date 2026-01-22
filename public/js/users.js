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
        $form.find('.invalid-feedback').text('').hide();
    };

    /**
     * Field name mapping: maps backend field names to frontend field names/selectors
     * This handles cases where field names might differ between backend and frontend
     */
    const fieldNameMap = {
        'user_id': 'user-id', // Backend uses user_id, frontend uses user-id
        // Add more mappings as needed
    };

    /**
     * Get the jQuery selector for a field by its name
     * @param {string} fieldName - The field name from the backend
     * @returns {jQuery} The jQuery object for the field element
     */
    const getFieldElement = (fieldName) => {
        // Check if there's a mapping for this field
        const mappedName = fieldNameMap[fieldName] || fieldName;
        
        // Try multiple selector strategies
        let $field = $form.find(`[name="${fieldName}"]`);
        
        if (!$field.length) {
            // Try with mapped name
            $field = $form.find(`[name="${mappedName}"]`);
        }
        
        if (!$field.length) {
            // Try by ID
            $field = $form.find(`#${fieldName}`);
        }
        
        if (!$field.length) {
            // Try mapped name by ID
            $field = $form.find(`#${mappedName}`);
        }
        
        if (!$field.length) {
            // Try with underscore to hyphen conversion (e.g., first_name -> first-name)
            const hyphenName = fieldName.replace(/_/g, '-');
            $field = $form.find(`[name="${hyphenName}"], #${hyphenName}`);
        }
        
        return $field;
    };

    /**
     * Display validation errors inline for each field
     * Maps JSON validation errors from backend to specific input fields using jQuery
     * @param {Object} errors - JSON object with field names as keys and error messages as values
     *                          Format: { "field_name": ["Error message"] } or { "field_name": "Error message" }
     */
    const displayValidationErrors = (errors) => {
        // Ensure errors is a valid object
        if (!errors || typeof errors !== 'object') {
            console.warn('Invalid errors object received:', errors);
            return;
        }
        
        resetErrors();
        
        // Track if any errors were successfully displayed
        let errorsDisplayed = false;
        
        Object.keys(errors).forEach((field) => {
            // Extract error message(s) - handle both array and string formats
            const errorData = errors[field];
            const errorMessages = Array.isArray(errorData) ? errorData : [errorData];
            const errorMessage = errorMessages[0]; // Get first error message for the field
            
            if (!errorMessage) {
                return; // Skip if no error message
            }
            
            // Find the field element using jQuery
            const $field = getFieldElement(field);
            
            if ($field.length) {
                // Add invalid class to the field
                $field.addClass('is-invalid');
                
                // Find the invalid-feedback element
                let $feedback = $field.next('.invalid-feedback');
                
                // If not found, check in parent container (for Bootstrap form structure)
                if (!$feedback.length) {
                    $feedback = $field.closest('.col-md-6, .col-12, .form-group, .mb-3').find('.invalid-feedback').first();
                }
                
                // If still not found, create one
                if (!$feedback.length) {
                    $feedback = $('<div class="invalid-feedback text-danger"></div>');
                    // Insert after the field, or after its wrapper if it exists
                    const $wrapper = $field.closest('.input-group');
                    if ($wrapper.length) {
                        $wrapper.after($feedback);
                    } else {
                        $field.after($feedback);
                    }
                }
                
                // Display the error message
                $feedback.text(errorMessage).show();
                errorsDisplayed = true;
            } else {
                // Field not found - log for debugging
                console.warn(`Field "${field}" not found in form. Error message: ${errorMessage}`);
            }
        });
        
        // If no errors were displayed but errors were provided, show a generic message
        if (!errorsDisplayed && Object.keys(errors).length > 0) {
            console.warn('Validation errors received but could not be mapped to form fields:', errors);
        }
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

    // State management for filters and pagination
    const state = {
        search: '',
        withTrashed: false,
        status: '',
        page: 1,
    };

    /**
     * Get current filter values from the UI
     * @returns {Object} Current filter state
     */
    const getCurrentFilters = () => {
        return {
            search: $('#search-users').val() || '',
            withTrashed: $('#with-trashed').is(':checked'),
            status: $('#status-filter').val() || '',
        };
    };

    /**
     * Update state from UI and reset page to 1
     * Used when filters change to ensure we start from page 1
     */
    const updateStateFromUI = () => {
        const filters = getCurrentFilters();
        state.search = filters.search;
        state.withTrashed = filters.withTrashed;
        state.status = filters.status;
        state.page = 1; // Reset to page 1 when filters change
    };

    /**
     * Build query parameters from current state
     * @returns {Object} Query parameters object
     */
    const buildQueryParams = () => {
        const params = {};
        if (state.search) {
            params.search = state.search;
        }
        if (state.withTrashed) {
            params.withTrashed = 1;
        }
        if (state.status) {
            params.status = state.status;
        }
        if (state.page > 1) {
            params.page = state.page;
        }
        return params;
    };

    /**
     * Extract page number from URL
     * @param {string} url - URL to extract page from
     * @returns {number} Page number or 1 if not found
     */
    const extractPageFromUrl = (url) => {
        if (!url) return 1;
        const match = url.match(/[?&]page=(\d+)/);
        return match ? parseInt(match[1], 10) : 1;
    };

    /**
     * Load users with current state
     * @param {string} url - Optional URL to load from (for pagination links)
     */
    const loadUsers = (url) => {
        // If URL is provided (from pagination), extract page from it
        if (url) {
            state.page = extractPageFromUrl(url);
        }

        const query = buildQueryParams();

        $('#users-table').addClass('opacity-50');
        $.get(url || indexUrl, query, (response) => {
            $('#users-table').html(response.table);
            $('#users-pagination').html(response.pagination);
        }).always(() => {
            $('#users-table').removeClass('opacity-50');
        });
    };

    /**
     * Debounce utility function to limit function execution frequency
     * @param {Function} func - The function to debounce
     * @param {number} wait - The number of milliseconds to delay
     * @returns {Function} The debounced function
     */
    const debounce = (func, wait = 400) => {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    };

    // Debounced search handler - waits 400ms after user stops typing before making request
    const debouncedSearch = debounce(() => {
        updateStateFromUI(); // Reset page to 1 when search changes
        loadUsers();
    }, 400);

    $('#search-users').on('input', debouncedSearch);

    $('#status-filter').on('change', function () {
        updateStateFromUI(); // Reset page to 1 when status filter changes
        loadUsers();
    });

    $('#with-trashed').on('change', function () {
        updateStateFromUI(); // Reset page to 1 when with-trashed filter changes
        loadUsers();
    });

    $(document).on('click', '#users-pagination a', function (event) {
        event.preventDefault();
        const url = $(this).attr('href');
        // Update state from UI first to ensure filters are current
        const filters = getCurrentFilters();
        state.search = filters.search;
        state.withTrashed = filters.withTrashed;
        state.status = filters.status;
        // Then load with the pagination URL (which will extract the page number)
        loadUsers(url);
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
                    // Parse JSON response - Laravel returns validation errors as JSON
                    // Format: { "message": "...", "errors": { "field_name": ["Error message"] } }
                    let responseJSON = null;
                    try {
                        // Try to parse JSON response
                        if (xhr.responseJSON) {
                            responseJSON = xhr.responseJSON;
                        } else if (xhr.responseText) {
                            responseJSON = JSON.parse(xhr.responseText);
                        }
                    } catch (e) {
                        console.error('Failed to parse JSON response:', e);
                    }
                    
                    if (xhr.status === 422) {
                        // Validation errors (422) - Laravel returns errors as JSON
                        // Expected format: { "message": "...", "errors": { "field": ["message"] } }
                        const errors = responseJSON?.errors || {};
                        if (Object.keys(errors).length > 0) {
                            // Map errors to specific input fields using jQuery
                            displayValidationErrors(errors);
                            
                            // Scroll to first error field within the modal
                            const $firstError = $form.find('.is-invalid').first();
                            if ($firstError.length && modalEl) {
                                // Scroll within modal body
                                const $modalBody = $(modalEl).find('.modal-body');
                                if ($modalBody.length) {
                                    const scrollTop = $modalBody.scrollTop() + $firstError.position().top - 20;
                                    $modalBody.animate({ scrollTop: scrollTop }, 300);
                                }
                                // Focus on the first error field
                                setTimeout(() => {
                                    $firstError.focus();
                                }, 100);
                            }
                        } else {
                            // Fallback if errors object is empty
                            const message = responseJSON?.message || 'Validation failed. Please check your input.';
                            showAlert('warning', message);
                        }
                    } else if (xhr.status === 404) {
                        const message = responseJSON?.message || 'Resource not found.';
                        showAlert('warning', message);
                    } else if (xhr.status === 403) {
                        const message = responseJSON?.message || 'You do not have permission to perform this action.';
                        showAlert('danger', message);
                    } else if (xhr.status === 500) {
                        const message = responseJSON?.message || 'Server error. Please try again later.';
                        showAlert('danger', message);
                    } else {
                        // Other errors - check if there's validation errors in the response
                        const errors = responseJSON?.errors;
                        if (errors && typeof errors === 'object' && Object.keys(errors).length > 0) {
                            // Map errors to specific input fields using jQuery
                            displayValidationErrors(errors);
                        } else {
                            const message = responseJSON?.message || 'Something went wrong. Please try again.';
                            showAlert('danger', message);
                        }
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
