@include('layouts.adminheader')

<!DOCTYPE html>

<html>



<head>



    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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

        #levelSelectDiv {
            margin-bottom: 1rem !important;
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }

        body {
            font-family: Century Gothic;
            background-color: #e9e9e9;

        }

        .form-select {
            width: 50% !important;
            font-size: 14px;
            margin-left: 4%;
        }

        .row {}
    </style>

</head>



<body>

    <div class="container-fluid" style="">
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
                <div class="row" style="background: #fff; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);">
                    <h1 style="font-size: 21px;font-weight: bold;    margin-top: 2%;">Create Criteria Aganist Format</h1>

                    <div class="col-md-12">

                        <!-- User selection section -->

                        <div class="mb-3">

                            <label for="user_id" class="form-label">Select Client:</label>

                            <select style="margin-right: 31%; width: 38% !important;" name="user_id" id="user_id" class="form-select">

                                <option value="">Select Client</option>



                                @foreach($process as $id => $name)

                                <option value="{{ $name->id }}">{{ $name->name }}</option>

                                @endforeach

                            </select>

                        </div>



                        <!-- Format selection section -->

        



                        <!-- Criteria selection section -->

                        <div id="formsubmit" style="display:none">
                            <hr>
                            <h4>Select Criteria</h4>
                            <form action="{{ route('storeCriteria') }}" method="POST">
                                @csrf
                                <input type="hidden" id="selected_format_id" name="format_id">
                                <div>
                                    <input type="radio" id="defaultOption" name="option" value="default"> Default Option
                                    <table class="table table-striped table-bordered" id="criteriaTable" style="display: none;">
                                        <thead>
                                            <tr>
                                                <th scope="col">Criteria</th>
                                                <th scope="col">Operator</th>
                                                <th scope="col">Range1</th>
                                                <th scope="col">Range2</th>
                                                <th scope="col">Color</th>
                                                <th scope="col">Action</th>

                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>Good</td>
                                                <td>&gt;</td>
                                                <td>80</td>
                                                <td>0</td>
                                                <td style="background-color: #ff8fab;"></td>
                                            </tr>
                                            <tr>
                                                <td>Average</td>
                                                <td>b/w</td>
                                                <td>65</td>
                                                <td>75</td>
                                                <td style="background-color:#ffcad4;"></td>
                                            </tr>
                                            <tr>
                                                <td>Poor</td>
                                                <td>&lt;</td>
                                                <td>65</td>
                                                <td>0</td>
                                                <td style="background-color: #9a8c98;"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div>
                                    <input type="radio" id="customOption" name="option" value="custom"> Custom Option
                                    <div class="mb-3" id="levels" style="display: none;">
                                        <label for="numLevels">Select Number of Levels:</label>
                                        <select name="numLevels" id="numLevels" class="form-select">
                                            @for ($i = 1; $i <= 10; $i++) <option value="{{ $i }}">{{ $i }}</option>
                                                @endfor
                                        </select>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-2"> </div>
                                        <div class="col-md-8">
                                            <div id="criteriaRows" style="display:none;margin-top: 5%;"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-2"> </div>
                                </div>
                                <hr>
                                <div style="display: flex; margin-top: 3%; margin-bottom: 4%; justify-content: flex-end;">
                                    <button style="    margin-bottom: 5%;" type="submit" class="btn btn-primary">Next</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editModalLabel">Edit Criterion</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="editCriterionForm">
                                @csrf
                                <div class="mb-3">
                                    <label for="editLabel" class="form-label">Label:</label>
                                    <input type="text" id="editLabel" name="label" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="editOperator" class="form-label">Operator:</label>
                                    <select id="editOperator" name="operator" class="form-select" required>
                                        <option value=">">Greater Than</option>
                                        <option value=">=">Greater Than And Equal To</option>
                                        <option value="<">Less Than</option>
                                        <option value="<=">Less Than And Equal To</option>
                                        <option value="between">Between</option>
                                        <option value="==">Equal To</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="editColor" class="form-label">Color:</label>
                                    <input type="color" id="editColor" name="color" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="editRange1" class="form-label">Range 1:</label>
                                    <input type="number" id="editRange1" name="range1" class="form-control" required>
                                </div>
                                <div class="mb-3 second-range" style="display:none;">
                                    <label for="editRange2" class="form-label">Range 2:</label>
                                    <input type="number" id="editRange2" name="range2" class="form-control">
                                </div>
                                <input type="hidden" id="editCriterionId" name="criterion_id">
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
</body>

</html>




<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {

        $('#numLevels').change(function() {

            var numLevels = $(this).val();

            $('#criteriaRows').empty(); // Clear previous rows



            for (var i = 1; i <= numLevels; i++) {

                var row = `

                    <div class="criteria-group mb-4 p-3" style="background-color:#51c1d3 !important">

                        <h5>Criteria ${i}</h5>

                        <div class="mb-3">

                            <label for="label${i}" class="form-label">Label:</label>

                            <input style="width: 50%;" type="text" id="label${i}" name="labels[]" class="form-control" required>

                        </div>

                        <div class="mb-3">

                            <label  for="operator${i}" class="form-label">Operator:</label>

                            <select     style="width: 50% !important;" name="operators[]" class="form-select operator-select" required>

                                <option value=">">Greater Than</option>

                                <option value=">=">Greater Than And Equal To</option>

                                <option value="<">Less Than</option>

                                <option value="<=">Less Than And Equal To</option>

                                <option value="between">Between</option>

                                <option value="==">Equal To</option>

                                <!-- Add more options as needed -->

                            </select>

                        </div>

                        <div class="mb-3">

                            <label  for="color${i}" class="form-label">Color:</label>

                            <input style="width: 50%;" type="color" id="color${i}" name="colors[]" class="form-control" required>

                        </div>

                        <div class="mb-3">

                            <label  for="range${i}" class="form-label">Range:</label>

                            <input type="number" style="width: 50%;" id="range${i}" name="ranges[]" class="form-control range-input" required>

                        </div>

                        <div class="mb-3 second-range" style="display:none;">

                            <label for="range${i}_2" class="form-label">Second Range:</label>

                            <input type="number" style="width: 50%;" id="range${i}_2" name="ranges_2[]" class="form-control range-input">

                        </div>

                    </div>

                `;

                $('#criteriaRows').append(row);

            }

        });



        $(document).on('change', '.operator-select', function() {

            var selectedOperator = $(this).val();

            var $secondRange = $(this).closest('.criteria-group').find('.second-range');



            if (selectedOperator === 'between') {

                $secondRange.slideDown();

            } else {

                $secondRange.slideUp();

            }

        });

    });
</script>



<script>
    $(document).ready(function() {

        $('#user_id').change(function() {

       
                    $('#formatSelectDiv').show(); // Show the format select box

                    $('#formsubmit').show();
                  $('#selected_format_id').val($('#user_id').val());

        $('#nameModal').modal('show'); // Show the modal when a format is selected

        });

    });





   
</script>



<script>
    document.addEventListener('DOMContentLoaded', function() {

        const defaultOption = document.getElementById('defaultOption');

        const criteriaTable = document.getElementById('criteriaTable');

        const levels = document.getElementById('levels');

        const criteriaRows = document.getElementById('criteriaRows');

        defaultOption.addEventListener('change', function() {

            if (defaultOption.checked) {

                criteriaTable.style.display = 'block';

                levels.style.display = 'none';

                criteriaRows.style.display = 'none';

            } else {

                criteriaTable.style.display = 'none';

            }

        });



        // Show the table if default option is initially checked

        if (defaultOption.checked) {

            criteriaTable.style.display = 'block';

        }

    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        const defaultOption = document.getElementById('customOption');

        const criteriaTable = document.getElementById('criteriaTable');

        const levels = document.getElementById('levels');

        const criteriaRows = document.getElementById('criteriaRows');

        defaultOption.addEventListener('change', function() {

            if (customOption.checked) {

                criteriaTable.style.display = 'none';

                levels.style.display = 'block';

                criteriaRows.style.display = 'block';

            } else {

                criteriaTable.style.display = 'none';

            }

        })

    });
</script>

<script>
    $(document).ready(function() {

        $.ajaxSetup({

            headers: {

                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')

            }

        });

        $('#user_id').change(function() {

            var user_id = $(this).val();



            $.ajax({

                url: 'getCriteria', // Route to fetch criteria

                type: 'GET',

                data: {

                    format_id: user_id

                },

                success: function(response) {

                    displayCriteria(response);

                },

                error: function(xhr) {

                    console.log(xhr.responseText);

                }

            });

        });



        // Function to display criteria in the table

        function displayCriteria(criteria) {

            var tableBody = $('#criteriaTable tbody');

            tableBody.empty();



            criteria.forEach(function(criterion) {

                var row = `

                    <tr>

                     <tr data-criterion-id="${criterion.id}">

                        <td>${criterion.label}</td>

                        <td>${criterion.operator}</td>

                        <td>${criterion.range1}</td>

                        <td>${criterion.range2}</td>

                        <td style="background-color: ${criterion.color};"></td>

                        <td>

                            <button class="btn btn-primary btn-sm edit-btn">Edit</button>

                            <button class="btn btn-danger btn-sm delete-btn">Delete</button>

                        </td>

                    </tr>

                `;

                tableBody.append(row);

            });



            $('#criteriaTable').show(); // Show the table after updating

        }



        // Edit button click handler





    });
</script>

<script>
    $(document).ready(function() {

        // Prevent the form from submitting when the Save changes button is clicked

        $('#saveChangesBtn').click(function(event) {

            event.preventDefault(); // Prevent the default form submission

            var formData = $('#editCriterionForm').serialize();



            $.ajax({

                url: "{{ route('updateCriteria') }}", // Your route to update criterion

                type: 'POST',

                data: formData,

                success: function(response) {

                    // Update the table row with new data

                    var criterionId = $('#editCriterionId').val();

                    var row = $('tr[data-criterion-id="' + criterionId + '"]');

                    row.find('td:eq(0)').text($('#editLabel').val());

                    row.find('td:eq(1)').text($('#editOperator').val());

                    row.find('td:eq(2)').text($('#editRange1').val());

                    row.find('td:eq(3)').text($('#editRange2').val());

                    row.find('td:eq(4)').css('background-color', $('#editColor').val());



                    $('#editModal').modal('hide');

                },

                error: function(xhr) {

                    console.log(xhr.responseText);

                }

            });

        });



        // Handle Edit button click

        $(document).on('click', '.edit-btn', function(event) {

            event.preventDefault(); // Prevent the default behavior



            var criterionId = $(this).closest('tr').data('criterion-id');

            var row = $(this).closest('tr');

            var label = row.find('td:eq(0)').text();

            var operator = row.find('td:eq(1)').text();

            var range1 = row.find('td:eq(2)').text();

            var range2 = row.find('td:eq(3)').text();

            var color = row.find('td:eq(4)').css('background-color');



            // Populate the modal with current criterion data

            $('#editLabel').val(label);

            $('#editOperator').val(operator);

            $('#editRange1').val(range1);

            $('#editRange2').val(range2);

            $('#editColor').val(rgbToHex(color));

            $('#editCriterionId').val(criterionId);



            if (operator === 'between') {

                $('.second-range').show();

            } else {

                $('.second-range').hide();

            }



            $('#editModal').modal('show');

        });



        $(document).on('click', '.delete-btn', function(event) {

            event.preventDefault(); // Prevent the default behavior



            if (confirm('Are you sure you want to delete this criterion?')) {

                var criterionId = $(this).closest('tr').data('criterion-id');



                $.ajax({

                    url: "{{ route('deleteCriteria') }}",

                    type: 'DELETE',

                    data: {

                        id: criterionId

                    },

                    success: function(response) {

                        // Remove the row from the table

                        $('tr[data-criterion-id="' + criterionId + '"]').remove();

                    },

                    error: function(xhr) {

                        console.log(xhr.responseText);

                    }

                });

            }

        });





        // Convert RGB color to Hex

        function rgbToHex(rgb) {

            rgb = rgb.match(/^rgba?[\s+]?\(([\s\d]+),([\s\d]+),([\s\d]+),?[\s\d\.]*\)$/);

            return rgb ? "#" +

                ("0" + parseInt(rgb[1], 10).toString(16)).slice(-2) +

                ("0" + parseInt(rgb[2], 10).toString(16)).slice(-2) +

                ("0" + parseInt(rgb[3], 10).toString(16)).slice(-2) : '';

        }

    });
</script>