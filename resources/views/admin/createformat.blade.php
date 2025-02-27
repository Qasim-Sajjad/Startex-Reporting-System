@include('layouts.adminheader')

<!DOCTYPE html>

<html lang="en">



<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<meta name="csrf-token" content="{{ csrf_token() }}">


    <style>
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

        body {
            font-family: Century Gothic;
            background-color: #e9e9e9 !important;

        }

        .form-select {
            display: block;
            width: 100% !important;
        }

        .btn-danger {
            color: #fff;
            background-color: #dc3545;
            border-color: #dc3545;
            height: 38px;
            width: 87px;
        }
    </style>
</head>



<body>

    <div class="container-fluid" style="">
        <div class="row">
            <div class="col-md-2">@include('layouts.adminnavbar')
            </div>
            <div class="col-md-10" style="padding: 19px; ">
                @csrf
                @if(session('success'))
                <div class="alert alert-success" role="alert">
                    {{ session('success') }}
                </div>
                @endif
                <div class="row">
                    <div class="col-md-6" style="margin-top: 2%;">
                        <div class="mb-3">
                            <label for="user_id" class="form-label">Select Process:</label>
                            <select name="user_id" id="user_id" class="form-select">
                                <option value="">Select Process</option>
                        @foreach($process as $id => $details)
            <option value="{{ $details['id'] }}">{{ $details['name'] }}</option>
        @endforeach
                            </select>
                        </div>
                        <div class="mb-3" id="create-format-container" style="display: none;">
                        <!--    <a href="#" id="create-format-link" style="color:#fff !important" class="btn btn-primary">Create New Format</a>
 !-->                      
 </div>
                    </div>
                   <!-- <div class="col-md-6" style="margin-top: 2%;">
                        <button id="add-process-btn" class="btn btn-primary">+ Add Process</button>
                        <div class="mb-3" id="create-process-container" style="">
                            <label for="new-process" class="form-label">Enter New Process:</label>
                              <textarea id="new-process" class="form-control" onchange="saveProcess(this.value)"></textarea>


                        </div>
                    </div>!-->

                </div>
                <div class="row">
                    <div class="col-md-3" style=""></div>
                    <div class="col-md-6" style="">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover mt-5" style="text-align: center;" id="formatTable">
                                <thead class="">
                                    <tr>
                                        <th>Format Name</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Formats will be populated here via JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-3" style="">

                    </div>
                    <div style="display: flex; margin-top: 3%; margin-bottom: 4%; justify-content: flex-end;">
                        <a style="color: #fff !important;" href="{{ route('createcriteria') }}" class="btn btn-primary">Next</a>
                    </div>
                </div>



            </div>
        </div>
    </div>
    <!-- Bootstrap Modal -->
    <!-- Modal for creating a format -->
    <div class="modal fade" id="createFormatModal" tabindex="-1" aria-labelledby="createFormatModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createFormatModalLabel"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="display:flex">
                    <label for="format_name">Format Name</label>
                    <input type="text" style="margin-left: 2%;width: 50%;    margin-bottom: 8%;" class="form-control" id="format_name" onchange="saveFormatName(this.value)">
                    <input type="hidden" id="modal_user_id" value="">
                </div>
                <!-- <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="submitFormat">Create Format</button>
            </div> -->
            </div>
        </div>
    </div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
      var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function saveProcess(process){

         $.ajax({
    type: 'POST',
    url: '/admin/save-process',
    contentType: 'application/json',  // Send JSON content
    data: JSON.stringify({
        process: process,
        _token: csrfToken
    }),
    success: function(response) {
        console.log('Process saved successfully');
    },
    error: function(xhr, status, error) {
        console.error('Error saving process:', error);
    }
});


        }

      
        function saveFormatName(formatName) {
            var userId = document.getElementById('modal_user_id').value; // Get user ID

            // Check if format name is not empty
            if (formatName && userId) {
                fetch("{{ route('formats.store') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            name: formatName,
                            user_id: userId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Format name saved successfully!');
                            // Optionally, you can fetch and refresh the formats here
                            console.log('Calling fetchFormats...');
                            fetchFormats(userId); // Call fetchFormats after saving
                            // fetchFormats(userId); // Refresh formats after saving
                        } else {
                            alert('Error saving format name.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            var userSelect = document.getElementById('user_id');
            var createFormatContainer = document.getElementById('create-format-container');
            var createFormatLink = document.getElementById('create-format-link');
            var createFormatModal = new bootstrap.Modal(document.getElementById('createFormatModal'));

            // Show "Create New Format" button when client is selected
            userSelect.addEventListener('change', function() {
                var userId = this.value;

                if (userId) {
                    createFormatContainer.style.display = 'block';
                    fetchFormats(userId); // Fetch formats when the client is selected
                } else {
                    createFormatContainer.style.display = 'none';
                }
            });

            // Open modal on clicking "Create New Format" and pass user ID
            createFormatLink.addEventListener('click', function() {
                var userId = userSelect.value;

                if (userId) {
                    document.getElementById('modal_user_id').value = userId;
                    createFormatModal.show();
                } else {
                    alert("Please select a client before creating a format.");
                }
            });

            // Handle form submission with AJAX
            document.getElementById('createFormatForm').addEventListener('submit', function(event) {
                event.preventDefault(); // Prevent default form submission
                var formData = new FormData(this);

                fetch("{{ route('formats.store') }}", {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            createFormatModal.hide(); // Hide the modal
                            alert('Format created successfully!');
                            fetchFormats(userSelect.value); // Refresh the table with new format
                        } else {
                            alert('Error creating format.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            });


        });
        // Fetch and display formats dynamically based on selected client
        function fetchFormats(userId) {
            // alert(1);
            fetch("{{ route('fetchFormats') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        user_id: userId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    var tableBody = document.querySelector('#formatTable tbody');
                    tableBody.innerHTML = '';

                    if (data.length > 0) {
                        data.forEach(format => {
                            var row = document.createElement('tr');
                            row.innerHTML = `
                                <td>${format.name}</td>
                                <td>
                                    <button class="btn btn-primary" onclick="reorderFormat(${format.id})">Re-order</button>
                                    <button class="btn btn-primary" style="height: 38px; width: 87px;" onclick="editFormat(${format.id})">Edit</button>
                                    <button class="btn btn-danger btn-sm" onclick="deleteFormat(${format.id})">Delete</button>
                                     <button class="btn btn-primary" onclick="copyFormat(${format.id})">Copy</button>
                                </td>
                            `;
                            tableBody.appendChild(row);
                        });
                    } else {
                        tableBody.innerHTML = `<tr><td colspan="2">No formats available for this client.</td></tr>`;
                    }
                });
        }
        // Define the functions in the global scope
        function editFormat(formatId) {
            window.location.href = `${formatId}/editformat`;
        }

        function reorderFormat(formatId) {
            window.location.href = `${formatId}/reorderFormat`;
        }

        function deleteFormat(formatId) {
            if (confirm('Are you sure you want to delete this format?')) {
                fetch("{{ route('deleteFormat') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            format_id: formatId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {

                        // alert('Format created successfully!');
                        var userSelect = document.getElementById('user_id');

                        fetchFormats(userSelect.value); // Re

                    });
            }
        }


        function copyFormat(formatId) {
            if (confirm('Are you sure you want to copy this format?')) {
                fetch("{{ route('copyFormat') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            format_id: formatId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert(data.message);
                            var userSelect = document.getElementById('user_id');
                            fetchFormats(userSelect.value); // Refresh formats after copying
                        } else {
                            alert(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            }
        }
    </script>



</body>



</html>

@include('layouts.adminfooter')