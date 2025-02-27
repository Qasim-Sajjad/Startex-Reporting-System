<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <style>
        body {
            font-family: Century Gothic;
            background-color: #000;
        }

        .navbar-nav {
            width: 100%;
            background-color: #131313 !important;
            color: #fbfbfb !important;
        }

        .navbar-nav .nav-item {
            width: 100%;
            text-align: right;
            background-color: #131313 !important;
            color: #fbfbfb !important;
            border-bottom: 2px solid #222222;
        }

        .navbar-nav .nav-item .nav-link {
            display: block;
            padding: 10px;
            background-color: #131313 !important;
            color: #fbfbfb !important;
        }

        .section-row {
            background-color: #222;
            color: #fff;
            font-weight: bold;
        }

        .question-row {
            background-color: #333;
            color: #ccc;
        }

        .overall-score {
            background-color: #444;
            color: #fff;
            text-align: center;
        }

        .circle-container {
            display: flex;

            justify-content: flex-end;
        }

        .circle {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin-right: 10px;
        }

        .red {
            background-color: red;
            text-align: right;
        }

        .green {
            background-color: green;
            text-align: right;
        }

        .score-description {
            text-align: right;
        }
    </style>
</head>

<body>

    @include('layouts.clientheader')

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2" style="background-color: #131313 !important;">
                @include('layouts.clientnavbar')
            </div>
            <div class="col-md-10" style="margin-top: 1%; background-color: #000 !important; color: #fff;">
                <div class="row">
                    <div class="col-md-12" style="margin-top: 1%; background-color: #000 !important; color: #fff;">
                        <div class="score-description">Click on any score to view comment analysis
                        </div>

                        <div class="circle-container">
                            <div class="circle green"></div>
                            (> 80)
                        </div>

                        <div class="circle-container">
                            <div class="circle red"></div>
                            (< 65) </div>

                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12" style="margin-top: 1%; background-color: #000 !important; color: #fff;">
                            <table class="table table-bordered table-hover" style="border: 1px solid #fff; border-collapse: collapse; font-size: 11px; width: 100%;">
                                <thead style="background: #ff9933; border-color: #fff !important; color: #fff;">
                                    <tr style="font-size: 15px; background: #ff9933; color: black;">
                                        <th style="text-align: center; border: 1px solid #fff; border-collapse: collapse;">Section</th>
                                        <th style="text-align: center; border: 1px solid #fff; border-collapse: collapse;">Attribute</th>
                                        <th style="text-align: center; border: 1px solid #fff; border-collapse: collapse;">Score</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($sections->unique('section_name') as $sectionData)
                                    <tr style="color: #fff; border-color: #fff !important;">
                                        <td style="color:#000;background: #ff9933; font-size: 15px; border: 1px solid #fff; border-collapse: collapse; text-align:center" rowspan="{{ $sections->where('section_name', $sectionData->section_name)->count() }}">
                                            {{ $sectionData->section_name }}
                                        </td>
                                        @php $firstQuestion = true; @endphp
                                        @foreach($sections->where('section_name', $sectionData->section_name) as $questionData)
                                        @if(!$firstQuestion)
                                    <tr style="color: #fff; border-color: #fff !important;">
                                        @endif
                                        <td style="border: 1px solid #fff; border-collapse: collapse; font-size:12px;">
                                            {{ $questionData->questionname }}
                                        </td>
                                        @php
                                        $questionPercentage = round($questionData->overallscore);
                                        $color = '';

                                        if ($questionPercentage > 80) {
                                        $color = 'color: green;';
                                        } elseif ($questionPercentage < 65) {
                                            $color='color: red;' ;
                                            }
                                            @endphp
                                            <td onclick="ghar(this);storeQuestionID(this);" style="font-size:12px; border: 1px solid #fff; border-collapse: collapse; text-align: center; {{ $color }}" data-questionid="{{ $questionData->questionID }}" data-questionname="{{ htmlspecialchars($questionData->questionname, ENT_QUOTES) }}" data-questionguideline="{{ htmlspecialchars($questionData->questionguidellines, ENT_QUOTES) }}">
                                            {{ $questionPercentage }}%
                                            </td>
                                            @if($firstQuestion)
                                            @php $firstQuestion = false; @endphp
                                            @endif
                                            @endforeach
                                    </tr>
                                    @endforeach

                                </tbody>
                            </table>
                        </div>


                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="commentAnalysisModal" tabindex="-1" aria-labelledby="commentAnalysisModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content" style="background-color: #000; color: #fff;">


                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="color: #fff;background-color:#fff"></button>

                <div class="modal-body">
                    <div id="commentAnalysisContent">

                        <tr>
                            <th>
                                <div id="questionName" style="font-size:12px;"></div> <!-- Add this line -->
                            </th>
                        </tr>
                        <table class="table table-bordered table-hover" style="border: 1px solid #fff; border-collapse: collapse; font-size: 18px; width: 100%;">
                            <thead style=" border-color: #fff !important; color: #fff;">
                                <tr>
                                    <th style="background: black;text-align: center; border: 1px solid #fff; border-collapse: collapse;">Total Visits</th>
                                    <th style="background: #70ad47;text-align: center; border: 1px solid #fff; border-collapse: collapse;">Compliant</th>
                                    <th style="background: #ff0000;text-align: center; border: 1px solid #fff; border-collapse: collapse;">Non-Compliant</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td style="color:#fff;text-align: center; border: 1px solid #fff; border-collapse: collapse;">
                                        <div class="total_number">
                                        </div>
                                    </td>
                                    <td style="color:#fff;text-align: center; border: 1px solid #fff; border-collapse: collapse;">
                                        <div class="first-part-data">
                                        </div>
                                    </td>
                                    <td style="color:#fff;text-align: center; border: 1px solid #fff; border-collapse: collapse;">
                                        <div class="rest-data">
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <tr>
                            <th>
                                <div id="questionGuideline" style=" background-color: #ffffcc; color: black;"></div>

                            </th>
                        </tr>
                        <div id="resultDiv" style="margin-top: 3%;"></div>
                    </div>
                </div>

            </div>
        </div>
    </div>




    <script src="https://cdn.canvasjs.com/canvasjs.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Bootstrap JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        function ghar(element) {
            var questionID = $(element).data('questionid');
            var questionName = $(element).data('questionname');
            var questionGuideline = $(element).data('questionguideline');

            // Make sure to include questionID in the URL
            var url = '{{ route("commentanalysis", ["questionID" => "__QUESTION_ID__"]) }}'.replace('__QUESTION_ID__', questionID);

            $.ajax({
                url: url,
                success: function(response) {
                    const data = response; // No need to parse, response is already an object
                    console.log(response);
                    // Extracting the necessary values from the response
                    const compliant = data.compliant;
                    const non_compliant = data.non_compliant;
                    const total = data.totalcount;
                    // const originalString = response;
                    // const [firstPart, ...rest] = originalString.split('value');

                    // Insert the data into the specific elements
                    $('#questionName').html('Q: ' + questionName);
                    $('#questionGuideline').html('Guideline:' + '<br>' + questionGuideline); // Set the question guideline
                    $('.first-part-data').html(compliant);
                    $('.rest-data').html(non_compliant);
                    $('.total_number').html(total);
                    // Show the modal
                    $('#commentAnalysisModal').modal('show');
                }
            });
        }

        function storeQuestionID(element) {
            var waveID = $('#secandwavew').val();
            var questionID = $(element).data('questionid');

            var url = '{{ route("keyword", ["questionID" => "__QUESTION_ID__"]) }}'.replace('__QUESTION_ID__', questionID);

            if (waveID) {
                url += '/' + waveID;
            }

            $.ajax({
                url: url,
                success: function(response) {
                    $('#resultDiv').html(response);
                },
            });
        }
    </script>
</body>

</html>