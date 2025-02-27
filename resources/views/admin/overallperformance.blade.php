@include('layouts.adminheader')

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">



<style>
    /* Style for the table */

    #reportTable {

        border-collapse: collapse;

        width: 100%;

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

    body {
        font-family: Century Gothic;
        background-color: #e9e9e9;
    }

    #waveSelectDiv {
        margin-bottom: 1rem !important;
        display: flex;
        justify-content: flex-end;
        align-items: center;
    }

    .form-select {
        width: 62% !important;
        font-size: 16px;
        margin-left: 4%;
    }

    input#hierarchy_name {
        width: 62% !important;
    }

    input#searchBox {
        margin-top: 2%;
        width: 40% !important;
    }

    #reportTable th,

    #reportTable td {

        border: 1px solid #dddddd;

        padding: 8px;

        text-align: center;

    }



    #reportTable th {

        background-color: #f2f2f2;

    }



    #reportTable tr:nth-child(even) {

        background-color: #f9f9f9;

    }



    .table {

        margin-top: 20px;

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

    .table th,

    .table td {

        text-align: center;

        vertical-align: middle;

    }



    .table .fas {

        color: #007bff;

        cursor: pointer;

    }



    .table .fas:hover {

        color: #0056b3;

    }
</style>

<div class="container-fluid" style="">
    <div class="row">
        <div class="col-md-2" style="min-height: 500px;background-color:#d7ddde !important;color:#fff">@include('layouts.adminnavbar')
        </div>
        <div class="col-md-10" style="    padding: 19px; ">
            @csrf

            @if(session('success'))

            <div class="alert alert-success" role="alert">

                {{ session('success') }}

            </div>

            @endif


            <div class="row">
                <div class="col-md-4" style=" padding: 32px 14px 32px 14px;">
                    <div class="mb-3">

                        <label for="user_id" class="form-label">Select Client:</label>

                        <select name="user_id" id="user_id" class="form-select" required>

                            <option value="">Select Client</option>

                            @foreach($users as $id=> $name)

                            <option value="{{ $id }}">{{ $name }}</option>

                            @endforeach

                        </select>

                    </div>
                </div>
                <div class="col-md-4" style=" padding: 32px 14px 32px 14px;">
                    <div id="formatSelectDiv" style="">

                        <label for="format_id" class="form-label">Select Format:</label>

                        <select name="format_id" id="format_id" class="form-select" required>

                            <!-- Formats related to the selected user will be populated here -->

                        </select>

                    </div>
                </div>
                <div class="col-md-4" style=" padding: 32px 14px 32px 14px;">
                    <div id="waveSelectDiv" style="">

                        <label for="wave_id" class="form-label">Select Wave:</label>

                        <select name="wave_id" id="wave_id" class="form-select" required>

                            <!-- wave related to the selected user will be populated here -->

                        </select>

                    </div>
                    <div class="" style="    margin-bottom: 1rem !important;
    display: flex;
    justify-content: flex-end;
    align-items: center;">
                        <button id="submit_button" type="submit" class="btn btn-primary">submit</button>

                    </div>
                </div>

            </div>

            <div class="row">
                <div class="col-md-12" style=" padding: 32px 14px 32px 14px;">
                    <table id="reportTable" style="">

                        <thead>

                            <tr>

                                <th>Client Name</th>

                                <th>Format Name</th>

                                <!-- <th>Location Name</th> -->

                                <th>Assigned </th>

                                <th>Remaining Shops </th>

                                <th>Shopper </th>

                                <th>Manager </th>

                                <th>Submit to Client </th>

                            </tr>

                        </thead>

                        <tbody id="tableBody">

                            <!-- Table rows will be dynamically added here -->

                        </tbody>

                    </table>
                </div>
            </div>
            <input type="hidden" name="checked_location_ids" id="checkedLocationIds" value="">


            <div class="row">
                <div class="col-md-12" style=" padding: 32px 14px 32px 14px;">

                    <table id="detailsTable" class="table table-striped table-bordered" style="">

                        <input type="text" id="searchBox" class="form-control" placeholder="Search branch...">



                        <thead class="thead-dark">

                            <tr>

                                <th>Sr.# </th>

                                <th>Wave</th>



                                <th>Branch</th>
                                <th>Edit By</th>

                                <!-- <th>ID</th>

                <th>Location ID</th> -->

                                <th>Action</th>

                            </tr>

                        </thead>

                        <tbody id="detailsTableBody">

                            <!-- Table rows will be dynamically added here -->

                        </tbody>

                    </table>
                </div>
            </div>

            <!-- <button type="submit" class="btn btn-primary">Assign</button> -->



        </div>

    </div>
</div>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    $(document).ready(function() {



        $('#searchBox').on('keyup', function() {

            var value = $(this).val().toLowerCase();

            $('#detailsTableBody tr').filter(function() {

                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)

            });

        });



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






        // $('#wave_id').change(function() {
        // $('#submit_button').change(function() {
        $('#submit_button').click(function() {

            // var waveId = $(this).val();
            var waveId = $('#wave_id').val();
            console.log(waveId);
            var formatId = $('#format_id').val();

            if (waveId !== '') {

                $.ajax({

                    url: 'getreport', // Replace with your route to fetch shops

                    type: 'GET',

                    data: {

                        format_id: formatId,

                        wave_id: waveId

                    },

                    success: function(response) {

                        var table = $('#reportTable');

                        var tbody = $('#tableBody');

                        tbody.empty();

                        response.forEach(function(item) {

                            console.log(item.locationDetails);

                            var assignedDetails = JSON.stringify(item.locationDetails['Assigned']);

                            // var assignedremaining = JSON.stringify(item.locationDetails['remaning']); // Check this line

                            var shopperDetails = JSON.stringify(item.locationDetails['shopper']);

                            var managerDetails = JSON.stringify(item.locationDetails['manager approved']);

                            var submittedDetails = JSON.stringify(item.locationDetails['submit to client']);



                            var row = '<tr>' +

                                '<td>' + item.clientName + '</td>' +

                                '<td>' + item.formatName + '</td>' +

                                '<td>' + item.assignedCount + '</td>' +

                                '<td><a href="#" class="count-link"  data-type="not" data-status="Assigned" data-details=\'' + assignedDetails + '\'>' + item.Assigned + '</a></td>' +

                                '<td><a href="#" class="count-link" data-type="not" data-status="shopper" data-details=\'' + shopperDetails + '\'>' + item.shopperCount + '</a></td>' +

                                '<td><a href="#" class="count-link"  data-type="not" data-status="manager approved" data-details=\'' + managerDetails + '\'>' + item.managerCount + '</a></td>' +

                                '<td><a href="#" class="count-link"  data-type="not" data-status="submitted" data-details=\'' + submittedDetails + '\'>' + item.submittedCount + '</a></td>' +

                                '</tr>';

                            tbody.append(row); // Append each row to the table

                        });

                        table.show();



                        // Add click event listeners to the counts

                        $('.count-link').click(function(e) {

                            e.preventDefault();

                            var status = $(this).data('status');

                            var details = JSON.parse($(this).attr('data-details'));

                            var type = $(this).data('type');

                            displayLocationDetails(status, details, type);

                        });

                    },

                    error: function(xhr) {

                        console.log(xhr.responseText);

                    }

                });

            } else {

                $('#reportTable').hide();

            }

        });



        function displayLocationDetails(status, details, type) {

            var detailsTable = $('#detailsTable');

            var detailsTbody = $('#detailsTableBody');

            console.log("Type passed to function:", type);

            detailsTbody.empty();

            details.forEach(function(detail, index) {

                var row = '<tr>' +

                    '<td>' + (index + 1) + '</td>' + // Serial number

                    //'<td>' + detail.locationName + '</td>' +


                    //  '<td>' + detail.locationID + '</td>' +

                    '<td>' + detail.waveName + '</td>' +

                    '<td>' + detail.locationName + ' (' + detail.branchcode + ')</td>' +
                    '<td>' + detail.edit + '</td>' +


                    '<td><a href="#" class="view-icon" data-type="' + type + '" data-id="' + detail.ID + '" data-location-id="' + detail.locationID + '"><i class="fas fa-eye"></i></a></td>' +

                    '</tr>';

                detailsTbody.append(row);

            });

            detailsTable.show();



            // Add click event listener for the view icons

            $('.view-icon').click(function(e) {

                e.preventDefault(); // Prevent default link behavior

                var formatId = $('#format_id').val();

                var shopId = $(this).data('id'); // Get shop ID from data-id attribute

                var locationId = $(this).data('location-id'); // Get location ID from data-location-id attribute

                var waveId = $('#wave_id').val();

                var type = $(this).data('type');

                var user = "admin";

                // Construct the URL with IDs and redirect

                var url = '../viewreport?format_id=' + formatId + '&shop_id=' + shopId + '&wave_id=' + waveId + '&location_id=' + locationId + '&status=' + "submit to client" + '&type=' + type + '&user=' + user;

                window.open(url, '_blank');

            });

        }





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

    });
</script>