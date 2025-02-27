@php
use Illuminate\Support\Facades\DB;
@endphp
@include('layouts.adminheader')
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Century Gothic;
            background-color: #f8f9fa;

        }

        .row {
            background: #fff;
            min-height: 50px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            position: relative;
            margin-bottom: 30px;
            -webkit-border-radius: 2px;
            -moz-border-radius: 2px;
            -ms-border-radius: 2px;
            border-radius: 2px;
        }

        .form-group label {

            font-weight: bold;

        }

        .btn-primary {

            background-color: #007bff;

            border-color: #007bff;

        }

        .btn-primary:hover {

            background-color: #0056b3;

            border-color: #0056b3;

        }

        .form-group {
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }

        .form-control {
            width: 34% !important;
            margin-left: 4%;
            margin-top: 1%;
            font-size: 14px;
            margin-right: 39%;
        }

        .mb-3 {
            display: flex !important;
            justify-content: flex-end !important;
        }
    </style>
</head>

<body>
    <div class="container-fluid" style="">
        <div class="row">
            <div class="col-md-2" style="">@include('layouts.adminsidebar')
            </div>
            <div class="col-md-10" style="    padding: 19px; ">
                @csrf
                @if(session('success'))
                <div class="alert alert-warning"> <!-- Use alert-warning for warning messages -->
                    {{ session('success') }}
                </div>
                @endif
                <div class="row" style="background: #fff; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);">
                    <h1 style="font-size: 18px; font-weight: bold;  margin-top: 2%;">New Client</h1>
                    <div class="col-md-12">
                        <form id="clientForm" style="font-size: 14px;" method="POST" action="{{ route('createuser') }}">
                            @csrf
                            <div class="form-group">
                                <label for="name">Client Name</label>
                                <input type="text" id="name" name="name" class="form-control" placeholder="Enter client name" required>
                            </div>
                            <div class="form-group">
                                <label for="emails">Email</label>
                                <input id="emails" name="emails" class="form-control" placeholder="Enter client email" required>
                            </div>
                            <!-- <small class="form-text text-muted">This email will be used as your username.</small> -->
  <div class="form-group">
                                <label for="name">industry</label>
                                <input type="text" id="industry" name="industry" class="form-control" placeholder="Enter industry" required>
                            </div>  <div class="form-group">
                                <label for="name">Address </label>
                                <input type="text" id="address" name="address" class="form-control" placeholder="Enter address" required>
                            </div>
                            <div class="form-group">
                                <label for="password">Password</label>
                                <input type="password" id="password" name="password" class="form-control" placeholder="Enter password" required>
                            </div>
                            <div class="form-group">
                                <label for="password_confirmation">Confirm Password</label>
                                <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" placeholder="Confirm password" required>
                            </div>
                            <small id="passwordError" class="form-text text-danger" style="display:none;">Passwords do not match.</small>
                       
                            <div style="display: flex; margin-top: 3%; margin-bottom: 4%; justify-content: flex-end;">
                                <button type="submit" class="btn btn-primary">Submit</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="row">
                    <h1 style="font-size: 18px; font-weight: bold;  margin-top: 2%;">Existing Client</h1>
                    <div class="col-md-12">
                        <table style="    margin-top: 1%;" class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Client Name</th>
                                    <th>Email</th>
                                    <!-- <th>Password</th> -->
                                    <th>Status</th>
                                    <th style="text-align:center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($users as $user)
                                <tr>
                                    <td>{{ $user->name }}</td>
                                    <td>{{ $user->email }}</td>
                                    <!-- <td>{{ str_repeat('*', strlen($user->password)) }}</td> -->
                                    <!-- <td>
                                    @if ($user->is_role == 0)
                                    Super Admin
                                    @elseif ($user->is_role == 1)
                                    Admin
                                    @elseif ($user->is_role == 2)
                                    Manager
                                    @elseif ($user->is_role == 3)
                                    Shopper
                                    @elseif ($user->is_role == 4)
                                    Client
                                    @endif
                                </td> -->
                                <td>Active/InActive</td>

                                    <td style="text-align:center">
                                        <button style="height: 38px;width: 83px;" type="button" class="btn btn-primary btn-sm edit-btn" data-userid="{{ $user->id }}">Edit</button>
                                        <form action="{{ route('users.destroy1', $user->id) }}" method="POST" style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">

                        <div class="modal-dialog">

                            <div class="modal-content">

                                <div class="modal-header">

                                    <h5 class="modal-title" id="editModalLabel">Client Edit</h5>

                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

                                </div>

                                <div class="modal-body">

                                    <form id="editUserForm">

                                        @csrf



                                        <input type="hidden" id="userId" name="userId" value="">

                                        <div class="mb-3">

                                            <label for="name" class="form-label">Name:</label>

                                            <input type="text" id="name" name="name" class="form-control" required>

                                        </div>

                                        <div class="mb-3">

                                            <label for="email" class="form-label">Email:</label>

                                            <input type="email" id="email" name="email" class="form-control" required>

                                        </div>

                                        <div class="mb-3">

                                            <label for="pass" class="form-label">Paaword:</label>

                                            <input type="text" id="pass" name="pass" class="form-control" required>

                                        </div>

                                    </form>

                                </div>



                                <div class="modal-footer">

                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>

                                    <button type="button" class="btn btn-primary" id="saveChangesBtn">Save changes</button>

                                </div>

                            </div>

                        </div>

                    </div>


                </div>



            </div>
        </div>
    </div>
</body>

</html>
<script>
    document.getElementById('clientForm').addEventListener('submit', function(e) {
        var password = document.getElementById('password').value;
        var confirmPassword = document.getElementById('password_confirmation').value;
        var passwordError = document.getElementById('passwordError');

        if (password !== confirmPassword) {
            e.preventDefault(); // Prevent form submission
            passwordError.style.display = 'block'; // Show error message
            document.getElementById('password').classList.add('is-invalid');
            document.getElementById('password_confirmation').classList.add('is-invalid');
        } else {
            passwordError.style.display = 'none'; // Hide error message if passwords match
            document.getElementById('password').classList.remove('is-invalid');
            document.getElementById('password_confirmation').classList.remove('is-invalid');
        }
    });
</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>



<script>
    $(document).ready(function() {

        // Handle Edit button click

        $(document).on('click', '.edit-btn', function(event) {

            event.preventDefault(); // Prevent the default behavior

            var userId = $(this).data('userid');

            var row = $(this).closest('tr');

            var name = row.find('td:eq(0)').text();

            var email = row.find('td:eq(1)').text();

            var pass = row.find('td:eq(2)').text();

            console.log(userId);

            // Populate the modal with current user data

            $('#editModal #name').val(name);

            $('#editModal #email').val(email);

            $('#editModal #pass').val(pass);

            $('#editModal #userId').val(userId);

            $('#editModal').modal('show');

        });

    });



    $(document).ready(function() {

        // Prevent the form from submitting when the Save changes button is clicked

        $('#saveChangesBtn').click(function(event) {

            event.preventDefault(); // Prevent the default form submission



            var formData = $('#editUserForm').serialize();



            $.ajax({

                url: "{{ route('updateUser1') }}", // Your route to update user data

                type: 'POST',

                data: formData,

                success: function(response) {

                    // Handle success response if needed

                    $('#editModal').modal('hide');

                },

                error: function(xhr) {

                    console.log(xhr.responseText);

                }

            });

        });

        $('#searchInput').on('keyup', function() {

            var searchText = $(this).val().toLowerCase();

            $('tbody tr').each(function() {

                var name = $(this).find('td:eq(0)').text().toLowerCase();

                var email = $(this).find('td:eq(1)').text().toLowerCase();

                if (name.includes(searchText) || email.includes(searchText)) {

                    $(this).show();

                } else {

                    $(this).hide();

                }

            });

        });

    });
</script>