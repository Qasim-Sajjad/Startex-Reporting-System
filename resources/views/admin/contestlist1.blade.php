

@php

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Session;



$clientID = Session::get('client_id');



$contestss = DB::table('contests')

    ->select(

        'contests.created_at as datecontest',

        'waves.name as waveName',

        'contests.branchName as branch',

        'contests.wave_id as wave_id',

        'contests.shop_id as shop_id',

        DB::raw('COUNT(*) as contest_count')

    )

    ->join('waves', 'contests.wave_id', '=', 'waves.id')

    ->where('contests.client_id', $clientID)

    ->groupBy(

        'contests.created_at',

        'waves.name',

        'contests.branchName',

        'contests.wave_id',

        'contests.shop_id'

    )

    ->get();

@endphp

@include('layouts.adminheader')

<!DOCTYPE html>

<html>



<head>



    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        
    body {
        font-family: Century Gothic;
        background-color: #f8f9fa;

    }
    .row{
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




   

            <div class="row" style="">

            <h1 style="    font-size: 18px;
    font-weight: bold;  margin-top: 2%;">Contests Detail</h1>

            <div class="col-md-12">

                    <table class="table table-bordered table-hover" style="font-size:11px;border-color: #3e3d3d !important;">

                        <thead style="border-color: #3e3d3d !important;">

                            <tr style="font-size: 14px; ">

                                <th class="text-center">Sr.No</th>

                                <th class="text-center">client</th>

                                <th class="text-center">Count</th>

                                <th class="text-center">Contest</th>

                                <th class="text-center">Responded</th>

                                <th class="text-center">Pending Response</th>

                            </tr>

                        </thead>

                        <tbody>

                        @php

                                $processedShopIds = [];

                                $serialNumber = 1;

                            @endphp

                            @foreach($contests as $contest)

                            @if(!in_array($contest->client_id, $processedShopIds))



                                    @php

                                    $processedShopIds[] = $contest->client_id;

                                       $client_id= $contest->client_id;

                                        $count = DB::table('contests')

                                            ->where('contests.client_id', $client_id)

                                            ->count();



                                        $Responded = DB::table('contests')

                                            ->where('contests.client_id', $client_id)

                                            ->whereNotNull('contests.AdminReply')

                                            ->count();



                                        $PendingResponse = DB::table('contests')

                                            ->where('contests.client_id', $client_id)

                                            ->whereNull('contests.AdminReply')

                                            ->count();

                                    @endphp

                                    <tr style="font-size: 14px; background: #fff; color: #000; font-size: 11px; border-color: #3e3d3d !important;">

                                        <td class="text-center">{{ $serialNumber }}</td>

                                        <td class="text-center">{{ $contest->clientName }}</td>



                                        <td class="text-center">{{ $count }}</td>

                                        <td class="text-center"> 

                                        <button type="button" class="btn btn-primary" onclick="viewBranches({{ $contest->client_id }})">

                                        View branches

                                            </button>

                                        </td>

                                        <td class="text-center">{{ $Responded }}</td>

                                        <td class="text-center">{{ $PendingResponse }}</td>

                                    </tr>

                                    @php

                                        $serialNumber++;

                                    @endphp

                                @endif

                            @endforeach

                        </tbody>

                    </table>

                </div>

                <div class="col-md-12">

                    <!-- <table class="table table-bordered table-hover" style="font-size:11px;border-color: #3e3d3d !important;">

                        <thead style="background:#D69C19; border-color: #3e3d3d !important; color: #fff;">

                            <tr style="font-size: 14px; background:#D69C19; color: white;">

                                <th class="text-center">Sr.No</th>

                                <th class="text-center">Date/Time</th>

                                <th class="text-center">Wave</th>

                                <th class="text-center">Branch</th>

                                <th class="text-center">Count</th>

                                <th class="text-center">Contest</th>

                                <th class="text-center">Responded</th>

                                <th class="text-center">Pending Response</th>

                            </tr>

                        </thead>

                        <tbody>

                            @php

                                $processedShopIds = [];

                                $serialNumber = 1;

                            @endphp



                            @foreach($contests as $contest)

                                @if(!in_array($contest->shop_id, $processedShopIds))

                                    @php

                                        $processedShopIds[] = $contest->shop_id;

                                        $shop_id = $contest->shop_id;

                                        $wave_id = $contest->wave_id;

                                        $count = DB::table('contests')

                                            ->where('contests.shop_id', $shop_id)

                                            ->count();



                                        $Responded = DB::table('contests')

                                            ->where('contests.shop_id', $shop_id)

                                            ->whereNotNull('contests.clientReply')

                                            ->count();



                                        $PendingResponse = DB::table('contests')

                                            ->where('contests.shop_id', $shop_id)

                                            ->whereNull('contests.clientReply')

                                            ->count();

                                    @endphp

                                    <tr style="font-size: 14px; background: #171616; color: #fff; font-size: 11px; border-color: #3e3d3d !important;">

                                        <td class="text-center">{{ $serialNumber }}</td>

                                        <td class="text-center">{{ $contest->datecontest }}</td>

                                        <td class="text-center">{{ $contest->waveName }}</td>

                                        <td class="text-center">{{ $contest->branch }}</td>

                                        <td class="text-center">{{ $count }}</td>

                                        <td class="text-center"> 

                                            <button type="button" class="btn btn-primary" onclick="ghar(this)"  data-waveid="{{ $wave_id }}" data-shopid="{{ $shop_id }}">

                                                View Contest

                                            </button>

                                        </td>

                                        <td class="text-center">{{ $Responded }}</td>

                                        <td class="text-center">{{ $PendingResponse }}</td>

                                    </tr>

                                    @php

                                        $serialNumber++;

                                    @endphp

                                @endif

                            @endforeach

                        </tbody>

                    </table> -->

                </div>

            </div>


 </div> 
</div>

    </div>



<!-- Modal Structure -->

<div class="modal fade" id="shopModal2" tabindex="-1" aria-labelledby="shopModalLabel2" aria-hidden="true">

  <div class="modal-dialog">

    <div class="modal-content" style="background-color: #fff;color: #000;    width: 144%;

">

      <div class="modal-header" style="background-color: ;">

        <h class="modal-title" id="shopModalLabel2"></h>

        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

      </div>

      <div class="modal-body">

      <table class="table table-bordered table-hover" style="font-size:11px;border-color: #3e3d3d !important;">

                        <thead style=" border-color: #3e3d3d !important;">

                            <tr style="font-size: 14px;">

                                <th class="text-center">Sr.No</th>

                                <th class="text-center">Date/Time</th>

                                <th class="text-center">Wave</th>

                                <th class="text-center">Branch</th>

                                <th class="text-center">Count</th>

                                <th class="text-center">Contest</th>

                                <!-- <th class="text-center">Responded</th>

                                <th class="text-center">Pending Response</th> -->

                            </tr>

                        </thead>

                        <tbody>

                            @php

                                $processedShopIds = [];

                                $serialNumber = 1;

                            @endphp



                            @foreach($contestss as $contest)

                                @if(!in_array($contest->shop_id, $processedShopIds))

                                    @php

                                        $processedShopIds[] = $contest->shop_id;

                                        $shop_id = $contest->shop_id;

                                        $wave_id = $contest->wave_id;

                                        $count = DB::table('contests')

                                            ->where('contests.shop_id', $shop_id)

                                            ->count();



                                        $Responded = DB::table('contests')

                                            ->where('contests.shop_id', $shop_id)

                                            ->whereNotNull('contests.clientReply')

                                            ->count();



                                        $PendingResponse = DB::table('contests')

                                            ->where('contests.shop_id', $shop_id)

                                            ->whereNull('contests.clientReply')

                                            ->count();

                                    @endphp

                                    <tr style="font-size: 14px; font-size: 11px; border-color: #3e3d3d !important;">

                                        <td class="text-center">{{ $serialNumber }}</td>

                                        <td class="text-center">{{ $contest->datecontest }}</td>

                                        <td class="text-center">{{ $contest->waveName }}</td>

                                        <td class="text-center">{{ $contest->branch }}</td>

                                        <td class="text-center">{{ $count }}</td>

                                        <td class="text-center"> 

                                            <button type="button" class="btn btn-primary" onclick="ghar(this)"  data-waveid="{{ $wave_id }}" data-shopid="{{ $shop_id }}">

                                                View Contest

                                            </button>

                                        </td>

                                        <!-- <td class="text-center">{{ $Responded }}</td>

                                        <td class="text-center">{{ $PendingResponse }}</td> -->

                                    </tr>

                                    @php

                                        $serialNumber++;

                                    @endphp

                                @endif

                            @endforeach

                        </tbody>

                    </table>      </div>

      <div class="modal-footer">

        <!-- <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button> -->

      </div>

    </div>

  </div>

</div>

    

    

<!-- Modal Structure -->

<div class="modal fade" id="shopModal" tabindex="-1" aria-labelledby="shopModalLabel" aria-hidden="true">

  <div class="modal-dialog">

    <div class="modal-content" style="background-color: #fff;color: #000;    width: 144%;

">

      <div class="modal-header" style="background-color: ;">

        <h class="modal-title" id="shopModalLabel" style="color:#fff">View Conversation</h>

        <!-- <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> -->

      </div>

      <div class="modal-body">

        <!-- Modal content will be loaded here via AJAX -->

      </div>

      <div class="modal-footer">

        <!-- <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button> -->

      </div>

    </div>

  </div>

</div>





<!-- Modal Structure -->

<div class="modal fade" id="shopModal1" tabindex="-1" aria-labelledby="shopModalLabel1" aria-hidden="true">

    <div class="modal-dialog">

        <div class="modal-content" style="background-color: #fff; color: #000; width: 144%;">

            <div class="modal-header" style="background-color: ;">

                <h class="modal-title" id="shopModalLabel1" style="color:#fff">Respond to Contest</h>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

            </div>

            <div class="modal-body">

                <textarea id="responseMessage" class="form-control" rows="4" placeholder="Type your response here..."></textarea>

                <input type="hidden" id="contestId" value="">

            </div>

            <div class="modal-footer">

                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>

                <button type="button" class="btn btn-primary" onclick="submitResponse()">Submit</button>

            </div>

        </div>

    </div>

</div>



<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>



<script>

    // function viewBranches(clientId) {

    // // console.log(clientId);

    // put::session('client_id',clientId);

    // $('#shopModal2').modal('show');

    // }

    function viewBranches(clientId) {

            // console.log(clientId);

            $.ajax({

                url: '{{ route('contestshow') }}',

                method: 'GET',

                data: {

                    id: clientId,

                    _token: '{{ csrf_token() }}' // Include CSRF token for Laravel

                },

                success: function(response) {

                    $('#shopModal2').modal('show');

                    // Handle the response here if needed

                },

                error: function(xhr) {

                    console.log('Error:', xhr.responseText);

                }

            });

        }

function ghar1(element) {

    var client_id = $(element).data('clientid');



    $.ajax({

        url: '{{ route('contestshow') }}',

        method: 'get',

        data: {

            id: client_id,

            _token: '{{ csrf_token() }}' // Include CSRF token for Laravel

        },

        success: function(response) {

        

        },

        error: function(xhr) {

            console.log('Error:', xhr.responseText);

        }

    });

}

function ghar(element) {

    var shopId = $(element).data('shopid');



    var waveid = $(element).data('waveid');

    var url = '{{ route('contestadmin', ['shopId' => '__shop_ID__', 'waveid' => '__wave_ID__']) }}'

            .replace('__shop_ID__', shopId)

            .replace('__wave_ID__', waveid);

       $.ajax({

        url: url,

        method: 'GET',

        success: function(response) {

            var data = response; // No need to parse, response is already an object

            console.log(response);



            var modalBody = $('#shopModal .modal-body');

            modalBody.empty(); // Clear existing content



            if (data.length > 0) {

                var content = '';



                $.each(data, function(index, contest) {

                    var status = '';

                    if (contest.comentAcceptReject == 1) {

                        status = '<b style="color:green;">Approved</b>';

                    } else if (contest.comentAcceptReject == 0) {

                        status = '<b style="color:red;">Rejected</b>';

                    } else if (contest.comentAcceptReject == 2) {

                        status = '<b style="color:#000;">Response</b>';



                    } else {

                        status = '<b style="color:gray;">Pending</b>';

                    }



                    content += '<div class="row"><div class="col-md-12">';

                    content += '    ' + contest.created_at + ' (' + status + ')';

                    content += '    <br>';

                    content += '    <h>' + contest.branchName + '</h>';

                    content += '    <p style="background:lightgray; border-radius: 0px; padding:20px;color: #000;">' + (contest.contest || 'No comment available') + '</p>';



                    if (contest.comentAcceptReject == 1) {

    // If contest has a value of 1, display the response button

    content += '<div style="text-align: right;    margin-bottom: 2%;">';

    content += '    <button type="button" class="btn btn-primary" onclick="openResponseModal(' + contest.id + ')">Respond</button>';

    content += '</div>';

} else if (contest.comentAcceptReject === null) {

    // If contest is null, display Accept and Reject buttons

    content += '<div style="text-align: right;">';

    // content += '    <button type="button" class="btn btn-success" onclick="updateCommentStatus(' + contest.id + ', 1)">Accept</button>';

    // content += '    <button type="button" class="btn btn-danger" onclick="updateCommentStatus(' + contest.id + ', 0)">Reject</button>';

    content += '</div>';

} else if (contest.comentAcceptReject === 2) {

    // If contest has a value of 2, do not display any buttons

    content += '<div style="text-align: right;">';

    content += '    <!-- No buttons to display -->';

    content += '</div>';

}



                    content += '</div>';

                });



                modalBody.html(content);

            } else {

                modalBody.html('<p>No contests found.</p>');

            }



            // Show the modal

            $('#shopModal').modal('show');

        },

        error: function(xhr) {

            console.log('Error:', xhr.responseText);

        }

    });

}



function updateCommentStatus(contestId, status) {

    $.ajax({

        url: '{{ route('updateCommentStatusadmin') }}',

        method: 'POST',

        data: {

            id: contestId,

            status: status,

            _token: '{{ csrf_token() }}' // Include CSRF token for Laravel

        },

        success: function(response) {

            alert('Comment status updated successfully!');

            $('#shopModal').modal('hide'); // Hide the modal after update

            // Optionally, refresh the content in the modal

            // ghar($('#someElement')); // You need to call `ghar` with appropriate element or refresh logic

        },

        error: function(xhr) {

            console.log('Error:', xhr.responseText);

        }

    });

}

function openResponseModal(contestId) {

    $('#contestId').val(contestId); // Set the contest ID in the hidden input field

    $('#responseMessage').val(''); // Clear the textarea

    $('#shopModal1').modal('show'); // Show the modal for response

}



function submitResponse() {

    var contestId = $('#contestId').val();

    var responseMessage = $('#responseMessage').val();



    if (responseMessage.trim() === '') {

        alert('Please type a response.');

        return;

    }



    $.ajax({

        url: '{{ route("submitResponseadmin") }}', // Your route for submitting the response

        method: 'POST',

        data: {

            id: contestId,

            clientReply: responseMessage,

            _token: '{{ csrf_token() }}' // Include CSRF token for Laravel

        },

        success: function(response) {

            alert('Response submitted successfully!');

            $('#shopModal').modal('hide'); // Hide the modal after submission

        },

        error: function(xhr) {

            console.log('Error:', xhr.responseText);

        }

    });

}

</script>

</body>



</html>


