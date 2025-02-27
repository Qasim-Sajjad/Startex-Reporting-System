@include('layouts.adminheader')

<style>
    .mb-3 {
        margin-bottom: 1rem !important;
        display: flex;
        justify-content: flex-end;
        align-items: center;
    }

    #formatSelectDiv,
    /* #wavesTableDiv, */
    #createButtonDiv {
        margin-bottom: 1rem !important;
        display: flex;
        justify-content: flex-end;
        align-items: center;
    }

    body {
        font-family: Century Gothic;
        background-color: #f8f9fa;
    }

    .form-select {
        width: 62% !important;
        font-size: 16px;
        margin-left: 4%;
    }

    input#hierarchy_name {
        width: 62% !important;
    }

    .row {
        /* background: #fff;
        min-height: 50px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        position: relative;
        margin-bottom: 30px;
        border-radius: 2px; */
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-2">@include('layouts.adminnavbar')</div>
        <div class="col-md-10" style="padding: 19px;">
            @csrf

            @if(session('success'))
            <div class="alert alert-success" role="alert">
                {{ session('success') }}
            </div>
            @endif

            <div class="row" style="box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2)">
                <h1 style="font-size: 21px;font-weight: bold;margin-top: 2%;">Create Wave</h1>

                <div class="col-md-8">
                    <div class="mb-3">
                        <label for="user_id" class="form-label">Select Client:</label>
                        <select name="user_id" id="user_id" class="form-select">
                            <option value="">Select Client</option>
                            @foreach($users as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div id="formatSelectDiv" style="display: none;">
                        <label for="format_id" class="form-label">Select Format:</label>
                        <select name="format_id" id="format_id" class="form-select">
                            <!-- Formats related to the selected user will be populated here -->
                        </select>
                    </div>

                    <div id="createButtonDiv" style="display: none; margin-top: 1rem;">
                        <button type="button" class="btn btn-primary" id="createWaveButton">Create Wave</button>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3" style=""></div>
                    <div class="col-md-6" style="">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover mt-5" style="text-align: center;" id="wavesTableDiv">
                                <thead>
                                    <tr>
                                        <th>Sr.No</th>
                                        <th>Wave</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="wavesTableBody">
                                    <!-- Dynamic waves will be appended here -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-3" style=""></div>

                </div>
            </div>

        </div>
    </div>
</div>
</div>

<!-- Modal for Creating and Editing Waves -->
<!-- Modal for Creating Waves -->
<div class="modal fade" id="createWaveModal" tabindex="-1" aria-labelledby="createWaveModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createWaveModalLabel">Create Wave</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('storedata') }}" method="POST">
                    @csrf
                    <input type="hidden" id="selected_format_id" name="format_id">
                    <div class="mb-3">
                        <label for="name" class="form-label">Wave:</label>
                        <input style="margin-left: 3%;" type="text" class="form-control" id="name" name="name">
                    </div>
                    <div style="display: flex; margin-top: 3%; margin-bottom: 4%; justify-content: flex-end;">
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Editing Waves -->
<div class="modal fade" id="editWaveModal" tabindex="-1" aria-labelledby="editWaveModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editWaveModalLabel">Edit Wave</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editWaveForm" action="{{ route('updateWave') }}" method="POST">
                    @csrf
                    <input type="hidden" id="edit_wave_id" name="wave_id"> <!-- ID of the wave to be updated -->
                    <div class="mb-3">
                        <label for="edit_wave_name" class="form-label">Wave:</label>
                        <input style="margin-left: 3%;" type="text" class="form-control" id="edit_wave_name" name="name">
                    </div>
                    <div style="display: flex; margin-top: 3%; margin-bottom: 4%; justify-content: flex-end;">
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        $('#user_id').change(function() {
            var userId = $(this).val();
            $.ajax({
                url: 'getformat', // Replace with your route to fetch user formats
                type: 'GET',
                data: {
                    id: userId
                },
                success: function(response) {
                    $('#format_id').html(response); // Populate the format select box
                    $('#formatSelectDiv').show(); // Show the format select box
                },
                error: function(xhr) {
                    console.log(xhr.responseText);
                }
            });
        });

        $('#format_id').change(function() {
            var format_id = $(this).val();
            $('#selected_format_id').val(format_id); // Set the selected format ID
            fetchWaves(format_id); // Fetch existing waves for the selected format
        });

        function fetchWaves(format_id) {
            $.ajax({
                url: 'getwave1', // Replace with your route to fetch existing waves
                type: 'GET',
                data: {
                    format_id: format_id
                },
                success: function(waves) {
                    var wavesTableBody = $('#wavesTableBody');
                    wavesTableBody.empty(); // Clear previous entries
                    waves.forEach(function(wave, index) {
                        var row = '<tr>' +
                            '<td>' + (index + 1) + '</td>' +
                            '<td>' + wave.name + '</td>' +
                            '<td><button class="btn btn-warning edit-wave" data-id="' + wave.id + '" data-name="' + wave.name + '">Edit</button></td>' +
                            '</tr>';
                        wavesTableBody.append(row);
                    });
                    $('#wavesTableDiv').show(); // Show the waves table
                    $('#createButtonDiv').show(); // Show the create button
                },
                error: function(xhr) {
                    console.log(xhr.responseText);
                }
            });
        }

        $(document).on('click', '.edit-wave', function() {
            var waveId = $(this).data('id');
            var waveName = $(this).data('name');

            // Populate modal with wave details
            $('#editWaveModalLabel').text('Edit Wave');
            $('#edit_wave_name').val(waveName);
            $('#edit_wave_id').val(waveId); // Set the selected wave ID for editing

            $('#editWaveModal').modal('show'); // Show the modal for editing
        });

        $('#createWaveButton').click(function() {
            $('#createWaveModalLabel').text('Create Wave');
            $('#name').val(''); // Clear the input for a new wave
            $('#selected_format_id').val(''); // Clear the format ID for a new wave

            // Get the selected format ID from the format dropdown
            var format_id = $('#format_id').val();
            $('#selected_format_id').val(format_id); // Set the selected format ID

            $('#createWaveModal').modal('show'); // Show the modal for creating
        });

        // Handle form submission for the create modal
        $('#createWaveModal form').submit(function(e) {
            e.preventDefault(); // Prevent default form submission
            var actionUrl = $(this).attr('action'); // Get the form action URL
            var formData = $(this).serialize(); // Serialize form data

            $.ajax({
                url: actionUrl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    $('#createWaveModal').modal('hide'); // Close modal
                    var format_id = $('#selected_format_id').val(); // Get the format ID
                    fetchWaves(format_id); // Refresh the waves table
                },
                error: function(xhr) {
                    console.log(xhr.responseText);
                }
            });
        });

        // Handle form submission for the edit modal
        $('#editWaveForm').submit(function(e) {
            e.preventDefault(); // Prevent default form submission
            var actionUrl = $(this).attr('action'); // Get the form action URL
            var formData = $(this).serialize(); // Serialize form data

            $.ajax({
                url: actionUrl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    $('#editWaveModal').modal('hide'); // Close modal
                    var format_id = $('#selected_format_id').val(); // Get the format ID
                    fetchWaves(format_id); // Refresh the waves table
                },
                error: function(xhr) {
                    console.log(xhr.responseText);
                }
            });
        });
    });
</script>