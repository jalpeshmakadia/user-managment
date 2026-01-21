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
    };

    const loadUsers = (url) => {
        const query = {
            search: $('#search-users').val(),
            withTrashed: $('#with-trashed').is(':checked') ? 1 : 0,
        };

        $.get(url || indexUrl, query, (response) => {
            $('#users-table').html(response.table);
            $('#users-pagination').html(response.pagination);
        });
    };

    let searchTimer = null;
    $('#search-users').on('keyup', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => loadUsers(), 300);
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

        $.get(`${baseUrl}/${userId}`, (response) => {
            $('#user-id').val(userId);
            $('#first_name').val(response.user.first_name);
            $('#last_name').val(response.user.last_name);
            $('#email').val(response.user.email);
            $('#status').val(response.user.status);
            $('#phone').val(response.user.phone || '');
            $('#userModalLabel').text('Edit User');
            userModal?.show();
        });
    });

    $(document).on('click', '.delete-user', function () {
        if (!confirm('Delete this user?')) {
            return;
        }

        const userId = $(this).data('id');
        $.post(`${baseUrl}/${userId}`, { _method: 'DELETE' }, () => {
            loadUsers();
        });
    });

    $(document).on('click', '.restore-user', function () {
        const userId = $(this).data('id');
        $.post(`${baseUrl}/${userId}/restore`, { _method: 'PUT' }, () => {
            loadUsers();
        });
    });

    $form.validate({
        ignore: [],
        rules: {
            first_name: { required: true, maxlength: 100 },
            last_name: { required: true, maxlength: 100 },
            email: { required: true, email: true, maxlength: 255 },
            password: {
                required: () => $('#user-id').val() === '',
                minlength: 6,
            },
            status: { required: true },
            phone: { maxlength: 30 },
        },
        submitHandler: function (form) {
            resetErrors();

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
                success: () => {
                    userModal?.hide();
                    loadUsers();
                },
                error: (xhr) => {
                    if (xhr.status !== 422) {
                        alert('Something went wrong. Please try again.');
                        return;
                    }

                    const errors = xhr.responseJSON.errors || {};
                    Object.keys(errors).forEach((field) => {
                        const message = errors[field][0];
                        const $input = $form.find(`[name="${field}"]`);
                        $input.addClass('is-invalid');
                        $input.next('.invalid-feedback').text(message);
                    });
                },
            });
        },
    });
});
