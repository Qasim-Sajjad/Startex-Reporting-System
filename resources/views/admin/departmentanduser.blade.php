@include('layouts.adminheader')

<div class="container-fluid">
    <div class="row">
        <div class="col-md-2" style="height: 500px;background-color:#d7ddde !important;color:#fff">@include('layouts.adminnavbar')</div>
        <div class="col-md-10" style="padding: 19px;">
            @csrf

            @if(session('success'))
                <div class="alert alert-success" role="alert">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Select Dropdown for Departments -->
            <div class="form-group">
                <label for="departmentSelect">Select Department</label>
                <div class="d-flex">
                    <select id="departmentSelect" name="department_id" class="form-control" style="flex: 1;">
                        <option value="">Select a Department</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-primary ml-2" data-toggle="modal" data-target="#createDepartmentModal">
                        Create New
                    </button>
                </div>
            </div>

            <!-- Select Dropdown for Hierarchy -->
            <div id="hierarchySelectDiv">
                <label for="hierarchy_id" class="form-label">Select Hierarchy:</label>
                <select name="hierarchy_id" id="hierarchy_id" class="form-select">
                    <option value="">Select Hierarchy</option>
                    @foreach($hierarchy as $id => $name)
                        <option value="{{ $name->id }}">{{ $name->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Select Dropdown for Level -->
            <div class="form-group">
                <label for="levelSelect">Select Level</label>
                <select id="levelSelect" name="level_id" class="form-control">
                    <option value="">Select a Level</option>
                    <!-- Levels will be dynamically populated here -->
                </select>
            </div>

            <!-- Select Dropdown for Data -->
            <div class="form-group">
                <label for="dataSelect">Select Data</label>
                <select id="dataSelect" class="form-control">
                    <option value="">Select Data</option>
                    <!-- Data will be populated here -->
                </select>
            </div>
          <div class="form-group">
    <label for="username">Username</label>
    <input type="text" id="username" name="username" class="form-control" required>
</div>

<div class="form-group">
    <label for="password">Password</label>
    <input type="password" id="password" name="password" class="form-control" required>
</div>

<!-- Add Save button -->
<button type="button" id="saveButton" class="btn btn-primary">Save</button>

<!-- Display success message or error message -->
<div id="message" style="display: none;" class="alert alert-success mt-3"></div>
          
          
        </div>
    </div>

    <!-- Create New Department Modal -->
    <div class="modal fade" id="createDepartmentModal" tabindex="-1" role="dialog" aria-labelledby="createDepartmentModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form id="createDepartmentForm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="createDepartmentModalLabel">Create New Department</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="newDepartmentName">Department Name</label>
                            <input type="text" id="newDepartmentName" name="name" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>

<script>
    $(document).ready(function () {
        // Create new department
        $('#createDepartmentForm').on('submit', function (e) {
            e.preventDefault();

            let departmentName = $('#newDepartmentName').val();

            $.ajax({
                url: '{{ route('create.department') }}', // Define this route in your Laravel application
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    name: departmentName
                },
                success: function (response) {
                    if (response.success) {
                        $('#createDepartmentModal').modal('hide');
                        $('#departmentSelect').append(new Option(response.department.name, response.department.id));
                        $('#newDepartmentName').val('');
                        alert('Department created successfully and added to the dropdown!');
                    }
                },
                error: function () {
                    alert('An error occurred while creating the department.');
                }
            });
        });

        // Handle hierarchy change
        $('#hierarchy_id').on('change', function () {
            let hierarchyId = $(this).val();
            $('#levelSelect').empty().append('<option value="">Select a Level</option>');

            if (hierarchyId) {
                $.ajax({
                    url: '{{ route('get.levels') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        hierarchy_id: hierarchyId
                    },
                    success: function (response) {
                        if (response.success) {
                            $.each(response.levels, function (index, level) {
                                $('#levelSelect').append(new Option(level.name, level.id));
                            });
                        }
                    },
                    error: function () {
                        alert('An error occurred while fetching levels.');
                    }
                });
            }
        });

        // On level change, fetch and populate data in select box
        $('#levelSelect').on('change', function () {
            let levelId = $(this).val();
            let hierarchyId = $('#hierarchy_id').val();

            $('#dataSelect').empty().append('<option value="">Select Data</option>');

            if (levelId && hierarchyId) {
                $.ajax({
                    url: '{{ route('get.data.by.level') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        level_id: levelId,
                        hierarchy_id: hierarchyId
                    },
                    success: function (response) {
                        if (response.success) {
                            $.each(response.data, function (index, row) {
                                $('#dataSelect').append(new Option(row.location_name, row.id));
                            });
                        } else {
                            alert('No data found for the selected level.');
                        }
                    },
                    error: function () {
                        alert('An error occurred while fetching data.');
                    }
                });
            }
        });
   $('#saveButton').on('click', function () {
      let departmentId = $('#departmentSelect').val();  // Correctly fetch the department ID
    let departmentName = $('#newDepartmentName').val();
    let username = $('#username').val();
    let password = $('#password').val();
    let hierarchy = $('#dataSelect').val();  // Correctly fetch the hierarchy ID


        // Send data to the server
        $.ajax({
            url: '{{ route('userDepartment') }}', // Define your route for handling the form submission
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                 
                deparment: departmentId,
                username: username,
                password: password,
                hierarchy:hierarchy
            },
            success: function (response) {
                if (response.success) {
                    // Clear the input fields
                    $('#newDepartmentName').val('');
                    $('#username').val('');
                    $('#password').val('');

                    // Display success message
                    $('#message').text('Department, Username, and Password saved successfully!').show();

                    // Optionally, close the modal or perform other actions
                    $('#createDepartmentModal').modal('hide');
                } else {
                    alert('Failed to save data. Please try again.');
                }
            },
            error: function () {
                alert('An error occurred while saving the data.');
            }
        });
    });
    });
</script>
