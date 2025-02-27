@include('layouts.adminheader')

<style>
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

    #formatSelectDiv1 {
        margin-bottom: 1rem !important;
        display: flex;
        justify-content: flex-end;
        align-items: center;
    }

    #hierarchyNameDiv {
        margin-bottom: 1rem !important;
        display: flex;
        justify-content: flex-end;
        align-items: center;
    }

    #hierarchySelectDiv {
        margin-bottom: 1rem !important;
        display: flex;
        justify-content: flex-end;
        align-items: center;
    }

    #waveSelectDiv {
        margin-bottom: 1rem !important;
        display: flex;
        justify-content: flex-end;
        align-items: center;
    }

    #locationsTableDiv {
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
        width: 50% !important;
        font-size: 16px;
        margin-left: 4%;
    }

    input#hierarchy_name {
        width: 62% !important;
    }



    #percentageDiv {
        margin-bottom: 1rem !important;
        display: none;
        justify-content: flex-end;
        align-items: center;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-2" style="">
            @include('layouts.adminnavbar')
        </div>
        <div class="col-md-10" style="padding: 19px;">
            @csrf
            @if(session('success'))
            <div class="alert alert-success" role="alert">
                {{ session('success') }}
            </div>
            @endif
            <div class="row" style="background: #fff; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);">
                <h1 style="font-size: 21px;font-weight: bold;margin-top: 2%;">Assign Format To Manager</h1>
                <div class="col-md-8">
                    <form id="assignForm" action="{{ route('assignprojecttomanager') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="manager_id" class="form-label">Select Manager:</label>
                            <select name="manager_id" id="manager_id" class="form-select" required>
                                <option value="">Select Manager</option>
                                @foreach($managers as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="user_id" class="form-label">Select Client:</label>
                            <select name="user_id" id="user_id" class="form-select" required>
                                <option value="">Select Client</option>
                                @foreach($users as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div id="formatSelectDiv" style="display: none;">
                            <label for="format_id" class="form-label">Select Format:</label>
                            <select name="format_id" id="format_id" class="form-select" required>
                                <!-- Formats related to the selected user will be populated here -->
                            </select>
                        </div>

                        <!-- New section with radio buttons -->
                        <div class="mb-3" id="yesno" style="display:none">
                            <label class="form-label">Do you want to assign some percentage of this project?</label>
                            <div>
                                <input type="radio" id="yesRadio" name="assignPercentage" value="yes">
                                <label for="yesRadio">Yes</label>

                                <input type="radio" id="noRadio" name="assignPercentage" value="no">
                                <label for="noRadio">No</label>
                            </div>
                        </div>

                        <div id="percentageDiv" class="mb-3">
                            <label for="percentage" class="form-label">Percentage to Assign:</label>
                            <input style="   width: 62% !important; font-size: 16px;  margin-left: 4%;" type="number" id="percentage" name="percentage" class="form-control" placeholder="Enter percentage" min="0" max="100" required>
                        </div>

                        <div style="display: flex; justify-content: flex-end; align-items: center;margin-bottom:3%">
                            <button type="submit" id="assignButton" class="btn btn-primary">Assign</button>
                        </div>
                    </form>
                </div>
                <div class="col-md-4"></div>
            </div>

            <div class="row" style="margin-top:3%;background: #fff; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);">
                <h1 style="font-size: 21px;font-weight: bold;margin-top: 2%;">Assign Shop To Shopper</h1>
                <div class="col-md-8">
                    <form action="{{ route('assignlocation') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="shoppers_id" class="form-label">Select shopper:</label>
                            <select name="shoppers_id" id="shoppers_id" class="form-select" required>
                                <option value="">Select shopper</option>
                                @foreach($shoppers as $id=> $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="user_id1" class="form-label">Select Client:</label>
                            <select name="user_id1" id="user_id1" class="form-select" required>
                                <option value="">Select Client</option>
                                @foreach($users as $id=> $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div id="formatSelectDiv1" style="display: none;">
                            <label for="format_id1" class="form-label">Select Format:</label>
                            <select name="format_id1" id="format_id1" class="form-select" required>
                                <!-- Formats related to the selected user will be populated here -->
                            </select>
                        </div>
                        <div id="waveSelectDiv" style="display: none;">
                            <label for="wave_id" class="form-label">Select Hierarchy:</label>
                            <select name="wave_id" id="wave_id" class="form-select" required>
                                <!-- wave related to the selected user will be populated here -->
                            </select>
                        </div>
                        <div class="row" style="">

                            <div class="col-md-3"></div>
                            <div class="col-md-9">
                                <input type="text" style="width:50%" id="searchBox" class="form-control" placeholder="Search branch...">

                                <div id="locationsTableDiv" style="margin-top:2%">
                                    <!-- <h3>Locations</h3> -->
                                    <table class="table table-bordered" style="text-align:center">
                                        <thead>
                                            <tr>
                                                <th>Sr No.</th>
                                                <th><input type="checkbox" id="selectAll"> Select All</th>
                                                <th>Branch</th>
                                                <th>Code</th>

                                            </tr>
                                        </thead>
                                        <tbody id="locationsTableBody">
                                            <!-- Locations will be populated here -->
                                        </tbody>
                                    </table>
                                </div>
                                <input type="hidden" name="checked_location_ids" id="checkedLocationIds" value="">
                            </div>
                        </div>
                        <div style=" margin-bottom: 1rem !important;display: flex; justify-content: flex-end; align-items: center;">

                            <button type="submit" class="btn btn-primary">Assign</button>
                        </div>
                    </form>

                </div>
                <div class="col-m-4"></div>

            </div>

        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    $(document).ready(function() {
        // Initially hide percentage input
        $('#percentageDiv').hide();
        $('#percentage').prop('disabled', true);

        // Load formats based on selected client
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
                    $('#yesno').show(); // Show the format select box

                },
                error: function(xhr) {
                    console.log(xhr.responseText);
                }
            });
        });

        // Show percentage input if "Yes" is selected, hide it if "No" is selected
        $('input[name="assignPercentage"]').change(function() {
            if ($('#yesRadio').is(':checked')) {
                $('#percentageDiv').css('display', 'flex'); // Show percentage input
                $('#percentage').prop('disabled', false); // Enable input
            } else {
                $('#percentageDiv').css('display', 'none'); // Hide percentage input
                $('#percentage').prop('disabled', true); // Disable input
            }
        });
    });
</script>
<script>
    $(document).ready(function() {

        $('#user_id1').change(function() {

            var userId = $(this).val();



            $.ajax({

                url: 'getformat', // Replace with your route to fetch user formats

                type: 'GET',

                data: {

                    id: userId

                },

                success: function(response) {

                    $('#format_id1').html(response); // Populate the format select box

                    $('#formatSelectDiv1').show(); // Show the format select box

                },

                error: function(xhr) {

                    console.log(xhr.responseText);

                }

            });

        });



        $('#format_id1').change(function() {

            var format_id = $(this).val();



            $.ajax({

                url: 'getwave', // Replace with your route to fetch user formats

                type: 'GET',

                data: {

                    format_id: format_id

                },

                success: function(response) {

                    $('#wave_id').html(response); // Populate the format select box

                    $('#waveSelectDiv').show(); // Show the format select box

                },

                error: function(xhr) {

                    console.log(xhr.responseText);

                }

            });

        });



        $('#wave_id').change(function() {

            var waveId = $(this).val();

            var formatId = $('#format_id1').val();



            $.ajax({

                url: 'getshops', // Replace with your route to fetch shops

                type: 'GET',

                data: {

                    format_id: formatId,

                    wave_id: waveId

                },

                success: function(response) {

                    var locations = response;

                    var tableBody = $('#locationsTableBody');

                    tableBody.empty(); // Clear previous data



                    locations.forEach(function(location, index) {

                        var row = '<tr>' +

                            '<td>' + (index + 1) + '</td>' + // Serial number

                            '<td><input type="checkbox" class="location-checkbox" value="' + location.ID + '"></td>' +

                            '<td>' + location.locationName + '</td>' +
                            '<td>' + location.branch_code + '</td>' +
                            '</tr>';

                        tableBody.append(row);

                    });



                    $('#locationsTableDiv').show(); // Show the locations table



                    // Select All Checkbox functionality

                    $('#selectAll').change(function() {

                        var checked = $(this).is(':checked');

                        $('.location-checkbox').prop('checked', checked);

                    });

                },

                error: function(xhr) {

                    console.log(xhr.responseText);

                }

            });

        });





        $('#locationsTableDiv').closest('form').submit(function(event) {

            event.preventDefault(); // Prevent the form from submitting normally



            // Get the IDs of checked checkboxes

            var checkedIds = [];

            $('.location-checkbox:checked').each(function() {

                checkedIds.push($(this).val());

            });



            // Assign the IDs to a hidden input field in the form

            $('#checkedLocationIds').val(JSON.stringify(checkedIds));



            // Now you can submit the form with the IDs of checked locations

            $(this).unbind('submit').submit();

        });



        $('#searchBox').on('keyup', function() {

            var value = $(this).val().toLowerCase();

            $('#locationsTableDiv tr').filter(function() {

                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)

            });

        });

    });
</script>