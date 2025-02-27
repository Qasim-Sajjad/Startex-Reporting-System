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
        }

        .mb-3 {
            display: flex !important;
            justify-content: flex-end !important;
        }

        .add-input {
            margin-top: 10px;
            cursor: pointer;
            background-color: #28a745;
            color: white;
            padding: 5px 10px;
            border-radius: 3px;
            border: none;
        }

        .add-input:hover {
            background-color: #218838;
        }

        .input-container {
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2">@include('layouts.adminnavbar')</div>
            <div class="col-md-10" style="padding: 19px;">
                @csrf
                @if(session('success'))
                <div class="alert alert-warning">
                    {{ session('success') }}
                </div>
                @endif
                <div class="row" style="background: #fff; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);">
                    <h1 style="font-size: 18px; font-weight: bold; margin-top: 2%;">Create user for Department</h1>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" id="name" class="form-control">
                        </div>
                        <div style="    display: flex;align-items: center;">
                            <select name="department" id="department" class="form-control">
                                <option value="">Select Department</option>
                                @foreach($departments as $department)
                                <option value="{{ $department->id }}">{{ $department->name }}</option>
                                @endforeach
                            </select>
                            <button style="margin-right: 1%;margin-left: 1%;" class="add-input" id="add-department">+</button>
                            <textarea name="newdepartment" id="newdepartment" onchange="savenewdepartment()"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" class="form-control">
                        </div>
                        <button class="btn btn-primary" id="save-user" onclick="saveUser()">Save</button>

                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- jQuery to handle the dynamic input field and saving data -->
    <script src=" https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function savenewdepartment() {
            var newdepartment = $('#newdepartment').val(); // Get the value of the textarea

            $.ajax({
                type: 'POST',
                url: '{{ url("savenewdepartment") }}', // Use the correct route URL
                data: {
                    _token: '{{ csrf_token() }}', // Add the CSRF token to the request
                    department: newdepartment // Send the department data
                },
                success: function(response) {
                    console.log('Department saved successfully.');
                    // You can also update the department dropdown with the new value if needed
                    $('#department').append(new Option(newdepartment, newdepartment)); // Option is created with value and text

                },
                error: function(xhr, status, error) {
                    console.error('Error saving department:', error);
                }
            });
        }

        // Save the user with department information
        function saveUser() {
            var name = $('#name').val();
            var username = $('#username').val();
            var password = $('#password').val();
            var departmentId = $('#department').val(); // Get selected department ID

            if (!name || !username || !password || !departmentId) {
                alert("Please fill all fields");
                return;
            }

            $.ajax({
                type: 'POST',
                url: '{{ url("saveuser") }}', // URL to save user
                data: {
                    _token: '{{ csrf_token() }}',
                    name: name,
                    username: username,
                    password: password,
                    department_id: departmentId
                },
                success: function(response) {
                    alert('User saved successfully');
                    // Optionally, reset the form
                    $('#name').val('');
                    $('#username').val('');
                    $('#password').val('');
                    $('#department').val('');
                },
                error: function(xhr, status, error) {
                    console.error('Error saving user:', error);
                }
            });
        }
    </script>
</body>

</html>