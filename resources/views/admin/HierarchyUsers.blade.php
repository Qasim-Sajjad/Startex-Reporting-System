<style>
    .container {
        background-color: #fff;
        padding: 40px 10px 10px 10px;
        background-color: #f8f9fa !important;

        /* border-radius: 5px; */
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

    body {
        font-family: Century Gothic;
        background-color: #e9e9e9 !important;

    }

    .mb-3 {
        margin-bottom: 1rem !important;
        display: flex;
        justify-content: flex-end;
        align-items: center;
    }

    #formatSelectDiv {
        margin-bottom: 1rem !important;
        display: flex;
        justify-content: flex-end;
        align-items: center;
    }

    #levelSelectDiv {
        margin-bottom: 1rem !important;
        display: flex;
        justify-content: flex-end;
        align-items: center;
    }

    .form-select {
        width: 62% !important;
        font-size: 14px;
        margin-left: 4%;
    }
</style>
@include('layouts.adminheader')

<meta name="csrf-token" content="{{ csrf_token() }}">



<div class="container-fluid" style=" background-color: #e9e9e9 !important;">
    <div class="row">
        <div class="col-md-2" style="">@include('layouts.adminnavbar')
        </div>
        <div class="col-md-10" style="    padding: 19px; ">
            @csrf
            @if(session('success'))
            <div class="alert alert-success" role="alert">
                {{ session('success') }}
            </div>

            @endif
            <div class="row">
                <h1 style="font-size: 21px;font-weight: bold; margin-top: 2%;">Level User Registration</h1>
                <div class="col-md-8">
                    <div class="col-md-2"></div>

                    <div class="mb-3">

                        <label for="user_id" class="form-label">Select User:</label>

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

                    <div id="levelSelectDiv" style="display: none;">

                        <label for="level_id" class="form-label">Select Level:</label>

                        <select name="level_id" id="level_id" class="form-select">

                            <!-- Levels will be populated here -->

                        </select>

                    </div>
                </div>
                <div class="col-md-2"></div>

            </div>
        </div>
    </div>
</div>



<!-- Hidden form to submit level and format IDs -->

<form id="levelForm" style="display: none;">

    @csrf

    <input type="hidden" name="format_id" id="hidden_format_id">

    <input type="hidden" name="level_id" id="hidden_level_id">

</form>



<!-- Modal for displaying location data -->

<div class="modal fade" id="dataModal" tabindex="-1" aria-labelledby="dataModalLabel" aria-hidden="true">

    <div class="modal-dialog modal-lg"> <!-- Adjust modal size as needed -->

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title" id="dataModalLabel"></h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

            </div>

            <div class="modal-body">

                <table class="table table-striped">

                    <thead>

                        <tr>

                            <th>Level</th>

                            <th>Email</th>

                            <th>Password</th>

                            <!-- <th>Emails</th> -->

                            <th style="display :none">hierarchy_id</th>

                            <th style="display: none;">format_id</th>

                            <th style="display:none ;">level_id</th>



                        </tr>

                    </thead>

                    <tbody id="locationData">

                        <!-- Location data will be loaded here -->

                    </tbody>

                </table>

                <div id="errorMessage" class="alert alert-danger mt-3" style="display: none;"></div>

            </div>

            <div class="modal-footer">

                <button type="button" class="btn btn-primary" id="saveChangesButton">Save Changes</button>

                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>



            </div>

        </div>

    </div>

</div>







<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>

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

            var levels = '<option value="">Select Level</option>';

            for (var i = 1; i <= 6; i++) {

                levels += '<option value="' + i + '">Level ' + i + '</option>';

            }

            $('#level_id').html(levels); // Populate the level select box

            $('#levelSelectDiv').show(); // Show the level select box

        });



        $('#level_id').change(function() {

            var formatId = $('#format_id').val();

            var levelId = $(this).val();



            $('#hidden_format_id').val(formatId);

            $('#hidden_level_id').val(levelId);



            $.ajax({

                url: 'postLevelFormat', // Replace with your route to handle the POST request

                type: 'POST',

                data: $('#levelForm').serialize(),

                success: function(response) {

                    if (typeof response === 'string') {

                        $('.modal-body').html('<p>' + response + '</p>'); // Display error message

                    } else {

                        var html = '';

                        response.forEach(function(item) {

                            html += '<tr>';

                            html += '<td>' + item.location_name + '</td>';
                            html += '<td><input type="email" class="form-control" name="emails[]" placeholder="Enter Email"></td>';
                            // html += '<td><input type="text" class="form-control" name="username[]" placeholder="Enter Username"></td>';

                            html += '<td><input type="password" class="form-control" name="password[]" placeholder="Enter Password"></td>';



                            html += '<td style="display: none;"><input type="hidden" name="hierarchy_id[]" value="' + item.hierarchy_id + '"></td>'; // Hidden input for hierarchy_id

                            html += '<td style="display: none;"><input type="hidden" name="format_id[]" value="' + item.format_id + '"></td>'; // Hidden input for format_id

                            html += '<td style="display: none;"><input type="hidden" name="level_id[]" value="' + item.level_id + '"></td>'; // Hidden input for format_id



                            html += '</tr>';

                        });

                        $('#locationData').html(html);

                    }

                    $('#dataModal').modal('show'); // Show the modal

                },

                error: function(xhr) {

                    console.log(xhr.responseText);

                }

            });

        });



        $('#saveChangesButton').click(function() {

            var formData = $('#locationData input').serialize(); // Serialize all input data



            $.ajax({

                url: 'saveChanges', // Replace with your route to handle saving changes

                type: 'POST',

                data: formData,

                headers: {

                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') // Include CSRF token in headers

                },

                success: function(response) {

                    // Display success message or handle response

                    alert('Changes saved successfully');

                },

                error: function(xhr) {

                    console.log(xhr.responseText);

                }

            });

        });

    });
</script>