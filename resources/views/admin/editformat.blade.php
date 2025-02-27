@php
use Illuminate\Support\Facades\DB;

@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Edit Format</title>
    <!-- Google Fonts for a cleaner look -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS for styling -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- jQuery library -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS for additional functionalities -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f9f9f9;
        }

        .container {
            margin: auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            margin-bottom: 30px;
        }

        .section,
        .questions {
            margin-bottom: 20px;
            padding: 20px;
            /* border-radius: 8px; */
            background-color: #f1f1f1;
            /* box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); */
        }

        .option {
            margin-bottom: 2px !important;
            padding: 0px !important;
            border-radius: 8px;
            border: 0px solid rgba(0, 0, 0, .125) !important;

            background-color: #f1f1f1;
        }

        .form-control {
            border-radius: 0px !important;
            padding: 10px;
            margin-top: 10px;
            border: 1px solid #ccc;
        }

        textarea.form-control {
            height: 3rem !important;
        }

        .btn {
            background-color: #4285F4;
            color: white;
            border-radius: 4px;
        }

        .btn:hover {
            background-color: #357ae8;
        }

        .add-section,
        .add-question,
        .add-option {
            background-color: #34a853;
            margin-top: 15px;
            color: white;
        }

        textarea,
        input[type="number"] {
            border-radius: 4px;
            padding: 10px;
            font-size: 14px;
        }

        .card-body label {
            font-size: 14px;
            font-weight: 500;
            color: #202124;
        }

        .alert-success {
            margin-top: 20px;
            background-color: #34A853;
            color: white;
        }

        .spinner-border {
            display: none;
        }

        .delete-btn {
            background-color: #e63946;
            /* Red background for delete button */
            padding: 10px;
            color: white;
        }

        /* Custom styling for buttons */
        .btn.add-section {
            /* width: 100%; */
            /* padding: 10px; */
        }

        .btn.add-question {
            /* width: 100%; */
            /* margin-top: 10px; */
            /* padding: 10px; */
        }

        .btn.add-option {
            /* width: 100%; */
            /* padding: 10px; */
        }

        .btn.delete-btn {
            padding: 4px;
            margin-top: 8px !important;
            margin-bottom: 9px;
        }

        .position-fixed {
            position: fixed;
            right: 15px;
        }

        .form-control {

            display: block;
            width: 50% !important;
            margin-left: 1%;
            margin-right: 1%;
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        @csrf
        @if(session('success'))
        <div class="alert alert-success" role="alert">
            {{ session('success') }}
        </div>
        @endif
        <!-- <h2>Edit Format</h2> -->
        <div class="section">
            <div style="display: flex; align-items: center;">
                <label for="format_name" class="form-label">Format Name:</label>
                <textarea style="margin-left:3%;font-size:15px ;width: 30%;  height: 50px; padding: 5px;  border-radius: 4px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);" class="form-control" id="format_name" name="format_name" onchange="saveFormat()">{{ $format->name }}</textarea>
                <input type="hidden" name="format_id" id="format_id" value="{{ $format->id }}">
            </div>
         
        </div>
        <div id="sectionsContainer">
            @foreach($format->sections as $index => $section)
            <div class="card section" id="section_{{ $section->id }}">
                <div class="card-body">
                    <div>
                        <label class="form-label">Section {{ $index + 1 }} Name:</label>
                        <textarea class="form-control" id="section_name_{{ $section->id }}" onchange="saveSection('{{ $section->id }}')">{{ $section->name }}</textarea>
                        <!-- Mandatory Checkboxes for the Section -->
                        <div class="checkbox-group" data-section-id="{{ $section->id }}">
                            <label>
                                <input type="checkbox" class="update-mandatory" value="mandatory"
                                    {{ $section->mandatory ? 'checked' : '' }}>
                                Mandatory Section
                            </label>
                            <label>
                                <input type="checkbox" class="update-mandatory" value="audio"
                                    {{ $section->audio ? 'checked' : '' }}>
                                Mandatory Audio
                            </label>
                            <label>
                                <input type="checkbox" class="update-mandatory" value="comment"
                                    {{ $section->comment ? 'checked' : '' }}>
                                Mandatory Comment
                            </label>
                            <label>
                                <input type="checkbox" class="update-mandatory" value="video"
                                    {{ $section->video ? 'checked' : '' }}>
                                Mandatory Video
                            </label>
                            <label>
                                <input type="checkbox" class="update-mandatory" value="picture"
                                    {{ $section->picture ? 'checked' : '' }}>
                                Mandatory Picture
                            </label>
                        </div>
                        <button type="button" class="btn delete-btn" data-section-id="{{ $section->id }}">Delete Section</button>
                        <div class="questions" id="questions_{{ $section->id }}">
                            @foreach($section->questions as $qIndex => $question)
                            <div class="card question" style="margin-top: 10px;">
                                <div class="card-body" style="background-color:#f1f1f1">
                                    <div style="display: flex; justify-content: flex-end;">
                                        <button type="button" class="btn delete-btn" data-question-id="{{ $question->id }}">Delete Question</button>
                                    </div>
                                    <div style="display: flex; align-items: center;">
                                        <label class="form-label">Question {{ $qIndex + 1 }}:</label>
                                        <textarea style="width: 80% !important;" class="form-control" id="question_{{ $question->id }}" onchange="saveQuestion('{{ $section->id }}', '{{ $question->id }}')">{{ $question->text }}</textarea>
                                    </div>
                                    <div style="display: flex; align-items: center;">
                                        <label class="form-label">Guidelines:</label>
                                        <textarea class="form-control" id="guideline_{{ $question->id }}" onchange="saveQuestion('{{ $section->id }}', '{{ $question->id }}')">{{ $question->guidelines }}</textarea>
                                        <label class="form-label">Score:</label>
                                        <input type="number" style="width: 6% !important; margin-left: 1%;" class="form-control" id="score_{{ $question->id }}" value="{{ $question->score }}" onchange="saveQuestion('{{ $section->id }}', '{{ $question->id }}')">
                                    </div>

                                    <div style="display: flex; align-items: center;">
                                        <label class="form-label">Question Type:</label>
                                        <select class="form-control" id="question_type_{{ $question->id }}" onchange="saveQuestion('{{ $section->id }}', '{{ $question->id }}')">
                                            <option value="multiple_choice" {{ $question->type == 'multiple_choice' ? 'selected' : '' }}>Multiple Choice</option>
                                            <option value="checkbox" {{ $question->type == 'checkbox' ? 'selected' : '' }}>Checkbox</option>
                                            <option value="paragraph" {{ $question->type == 'paragraph' ? 'selected' : '' }}>Paragraph</option>
                                            <option value="single_choice" {{ $question->type == 'single_choice' ? 'selected' : '' }}>Single Choice</option>
                                        </select>
                                    </div>
                                    <div class="options" id="options_{{ $question->id }}">
                                       @foreach($question->options as $option)
                                <div class="card option" style="margin-top: 5px;">
                                    <div class="card-body" style="display: flex; align-items: center;">
                                        <label class="form-label">Option: {{ $option->name }}</label>
                                        <textarea class="form-control" style="width: 18% !important;" id="option_{{ $option->id }}" onchange="saveOption('{{ $option->id }}', '{{ $question->id }}')">{{ $option->name }}</textarea>
                                        
                                        <label class="form-label">Score:</label>
                                        <input type="number" class="form-control" style="width: 10% !important;" id="option_score_{{ $option->id }}" value="{{ $option->pivot->score }}" onchange="saveOption('{{ $option->id }}', '{{ $question->id }}')">
                                        <button type="button" class="btn btn-danger delete-option-btn" data-option-id="{{ $option->id }}" style="margin-left: 10px;">Delete</button>
                                    </div>
                                </div>
                                @endforeach
                                    </div>
                                    <div class="checkbox-group" data-question-id="{{ $question->id }}">
                                        <label>
                                            <input type="checkbox" class="update-mandatory" value="mandatory_question"
                                                {{ $question->mandatory_question ? 'checked' : '' }}>
                                            Mandatory Question
                                        </label>
                                        <label>
                                            <input type="checkbox" class="update-mandatory" value="mandatory_audio"
                                                {{ $question->mandatory_audio ? 'checked' : '' }}>
                                            Mandatory Audio
                                        </label>
                                        <label>
                                            <input type="checkbox" class="update-mandatory" value="mandatory_text"
                                                {{ $question->mandatory_text ? 'checked' : '' }}>
                                            Mandatory Comment
                                        </label>
                                        <label>
                                            <input type="checkbox" class="update-mandatory" value="mandatory_video"
                                                {{ $question->mandatory_video ? 'checked' : '' }}>
                                            Mandatory Video
                                        </label>
                                        <label>
                                            <input type="checkbox" class="update-mandatory" value="mandatory_picture"
                                                {{ $question->mandatory_picture ? 'checked' : '' }}>
                                            Mandatory Picture
                                        </label>
                                    </div>

                                    <div style="display: flex; justify-content: flex-end;">
                                        <button type="button" class="btn btn-primary mt-3 add-option" data-question-id="{{ $question->id }}">Add Option</button>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    <button type="button" class="btn add-question1" data-section-id="{{ $section->id }}">Add Question</button>
                </div>
            </div>
            @endforeach
        </div>
        <button type="button" class="btn add-section" data-format-id="{{ $format->id }}">Add Section</button>
    </div>
    <!-- <div style="text-align:center;margin-top:20px;">
        <button type="button" style="width: 50%;" class="btn" onclick="showAlert()">Save</button>
    </div> -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Function to handle the checkbox updates
        function updateSectionMandatoryFields(sectionId) {
            // Collect the values of the checkboxes
            let mandatoryFields = {
                section: $('#section_' + sectionId).prop('checked') ? 1 : 0,
                audio: $('#audio_' + sectionId).prop('checked') ? 1 : 0,
                comment: $('#comment_' + sectionId).prop('checked') ? 1 : 0,
                video: $('#video_' + sectionId).prop('checked') ? 1 : 0,
                picture: $('#mandatory_picture_' + sectionId).prop('checked') ? 1 : 0
            };

            // Send the data to the backend using AJAX
            $.ajax({
                url: '/update-section-mandatory-fields', // Your route to update the section
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}', // CSRF token for security
                    section_id: sectionId,
                    mandatoryFields: mandatoryFields
                },
                success: function(response) {
                    if (response.success) {
                        alert('Section mandatory fields updated successfully!');
                    } else {
                        alert('Error updating mandatory fields.');
                    }
                },
                error: function(xhr, status, error) {
                    console.error(error);
                    alert('An error occurred while updating the mandatory fields.');
                }
            });
        }

        // Attach the update function to the checkbox change event
        $(document).ready(function() {
            $('.update-mandatory').on('change', function() {
                // Get the section ID from the data attribute
                let sectionId = $(this).closest('.checkbox-group2').data('section-id');
                // Call the function to update the section's mandatory fields
                updateSectionMandatoryFields(sectionId);
            });
        });
    </script>
    <script>
        $(document).on('change', '.update-mandatory', function() {
            var questionId = $(this).closest('.checkbox-group').data('question-id');
            var field = $(this).val();
            var isChecked = $(this).prop('checked') ? 1 : 0;

            $.ajax({
                url: '/update-mandatory-field', // Your Laravel route
                method: 'POST',
                data: {
                    question_id: questionId,
                    field: field,
                    value: isChecked,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        alert('Mandatory field updated successfully!');
                    }
                },
                error: function() {
                    alert('Error updating mandatory field.');
                }
            });
        });
    </script>
    <script>
        $(document).ready(function() {
            function sendData() {
                var selectedLevels = $('input[name="hierarchy_levels[]"]:checked').map(function() {
                    return $(this).val();
                }).get();
                // Prepare data for sending
                var requestData = {
                    format_id: $('#format_id').val(),
                    hierarchy_levels: selectedLevels,
                    timeIn: $('input[name="timeIn"]').is(':checked') ? 1 : 0, // 1 if checked, else 0
                    timeOut: $('input[name="timeOut"]').is(':checked') ? 1 : 0, // 1 if checked, else 0
                    branchCode: $('input[name="branchcode"]').is(':checked') ? 1 : 0, // 1 if checked, else 0
                    date: $('input[name="date"]').is(':checked') ? 1 : 0, // 1 if checked, else 0
                    _token: '{{ csrf_token() }}'
                };
                $.ajax({
                    url: '/saveHierarchy',
                    type: 'POST',
                    data: requestData,
                    success: function(response) {
                        console.log('Data saved successfully:', response);
                    },
                    error: function(xhr, status, error) {
                        console.error('Error saving data:', error);
                    }
                });
            }
            // Attach change event listener to the inputs
            $('input[name="hierarchy_levels[]"], input[name="timeIn"], input[name="timeOut"], input[name="branchcode"], input[name="date"]').change(sendData);
        });
    </script>
    <script>
        let sectionCount = '';
        let sectionQuestionCounts = {}; // Object to keep track of question counts for each section

        function addSection(formatId, sectionid) {
            sectionCount++;
            sectionQuestionCounts[sectionCount] = 0; // Initialize question count for the new section
            // Generate a unique ID for this section
            var sectionId = `section_${sectionid}`;
            var sectionsContainer = $('#sectionsContainer');
            var newSectionHtml = `
        <div class="card section" id="${sectionid}">
            <div class="card-body">
                <label class="form-label">Section ${sectionCount} Name:</label>
                <textarea class="form-control" id="section_name_${sectionid}" onchange="saveSection('${sectionid}')"></textarea>
                 <!-- Mandatory Checkboxes for the Section -->
        <div class="checkbox-group" data-section-id="${sectionid}">
            <label>
                <input type="checkbox" class="update-mandatory" value="mandatory" 
                  >
                Mandatory Section
            </label>
            <label>
                <input type="checkbox" class="update-mandatory" value="audio" 
                 >
                Mandatory Audio
            </label>
            <label>
                <input type="checkbox" class="update-mandatory" value="comment" 
                 >
                Mandatory Comment
            </label>
            <label>
                <input type="checkbox" class="update-mandatory" value="video" 
                >
                Mandatory Video
            </label>
            <label>
                <input type="checkbox" class="update-mandatory" value="picture" 
                   >
                Mandatory Picture
            </label>
        </div>
                <div style="display: flex;justify-content: flex-end;">
                <button type="button" class="btn delete-btn" data-section-id="${sectionid}">Delete Section</button>
               </div>
                <div class="questions" id="questions_${sectionid}"></div>
                <button type="button" class="btn add-question1" data-section-id="${sectionid}">Add Question</button>
            </div>
        </div>
    `;
            sectionsContainer.append(newSectionHtml);
        }

        function addQuestion(sectionId, questionid) {
            // alert(1);
            sectionQuestionCounts[sectionId] = (sectionQuestionCounts[sectionId] || 0) + 1; // Increment question count for the section
            var questionsContainer = $('#questions_' + sectionId);
            var sectionId = sectionId;
            var newQuestionHtml = `
                <div class="card question">
                    <div class="card-body"  style="background-color:#f1f1f1">
                        <label class="form-label">Question ${sectionQuestionCounts[sectionId]}:</label>
                        <textarea class="form-control" id="question_${questionid}" onchange="saveQuestion('${sectionId}', '${questionid}')"></textarea>
                        <button type="button" class="btn delete-btn" data-question-id="${questionid}">Delete Question</button>
                      <br>
                        <label class="form-label">Guidelines:</label>
                        <textarea class="form-control" id="guideline_${questionid}" onchange="saveQuestion('${sectionId}', '${questionid}')"></textarea>
                    <div class="checkbox-group" data-question-id="${questionid}">
                <label>
                    <input type="checkbox" class="update-mandatory" value="mandatory_question" onchange="updateMandatory('${questionid}')">
                    Mandatory
                </label>
                <label>
                    <input type="checkbox" class="update-mandatory" value="mandatory_audio" onchange="updateMandatory('${questionid}')">
                    Audio
                </label>
                <label>
                    <input type="checkbox" class="update-mandatory" value="mandatory_text" onchange="updateMandatory('${questionid}')">
                    Comment
                </label>
                <label>
                    <input type="checkbox" class="update-mandatory" value="mandatory_video" onchange="updateMandatory('${questionid}')">
                    Video
                </label>
                <label>
                    <input type="checkbox" class="update-mandatory" value="mandatory_picture" onchange="updateMandatory('${questionid}')">
                    Picture
                </label>
            </div>
                
                        <div style="display: flex; align-items: center;">
                                        <label class="form-label">Question Type:</label>
                                        <select class="form-control" id="question_type_${questionid}" onchange="saveQuestion('${sectionId}', '${questionid}')">
                                            <option value="multiple_choice" >Multiple Choice</option>
                                            <option value="checkbox" >Checkbox</option>
                                            <option value="paragraph">Paragraph</option>
                                            <option value="single_choice" >Single Choice</option>
                                        </select>
                                    </div>
                        <label class="form-label">Score:</label>
                        <input type="number" style="    width: 10% !important;  margin-left: 1%;" class="form-control" id="score_${questionid}" value="0" onchange="saveQuestion('${sectionId}', '${questionid}')">
                        <div class="options" id="options_${questionid}"></div>
                        <button type="button" class="btn btn-primary mt-3 add-option" data-question-id="${questionid}">Add Option</button>
                 
                        </div>
                </div>
            `;
            questionsContainer.append(newQuestionHtml);
        }

        function addOption(questionId) {
            var optionsContainer = $('#options_' + questionId);
            var optionId = Date.now(); // Use current timestamp as a unique identifier
            var newOptionHtml = `
    <div class="card option">
        <div class="card-body" style=" display: flex;align-items: center;">
            <label class="form-label">Option:</label>
            <textarea style="width: 18% !important;" class="form-control" id='option_${optionId}' onchange="saveOption('${optionId}', '${questionId}')"></textarea>
            <label class="form-label">Score:</label>
            <input type="number" style="width: 10% !important;"  class="form-control" id="option_score_${optionId}" value="" onchange="saveOption('${optionId}', '${questionId}')">
                      <button type="button" class="btn btn-danger delete-option" data-option-id="${optionId}">Delete Option</button>

            </div>
    </div>`;
            optionsContainer.append(newOptionHtml);
        }

        function saveFormat() {
            var formatName = $('#format_name').val();
            var formatId = $('#format_id').val();
            $.ajax({
                url: '/admin/save-format',
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    format_name: formatName,
                    format_id: formatId
                },
                success: function(response) {
                    // alert(response.success);
                },
                error: function(xhr, status, error) {
                    var errorMessage = xhr.status + ': ' + xhr.statusText;
                    // alert('Error - ' + errorMessage);
                    console.log(xhr.responseText);
                }
            });
        }

        function saveSection1() {
            var formatId = $('#format_id').val();
            var sectionId = 1; // Replace this with your logic for determining the question ID

            $.ajax({
                url: '/admin/save-section',
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),

                    format_id: formatId,
                    section_id: sectionId
                },
                success: function(response) {
                    console.log("Section saved successfully: ", response);
                    addSection(formatId, response.section_id);
                },
                error: function(xhr, status, error) {
                    var errorMessage = xhr.status + ': ' + xhr.statusText;
                    console.error('Error - ' + errorMessage);
                    console.log(xhr.responseText);
                }
            });
        }

        function saveSection(sectionId) {
            var sectionName = $('#section_name_' + sectionId).val();
            var formatId = $('#format_id').val();
            // alert(sectionName);
            $.ajax({
                url: '/admin/save-section',
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    section_name: sectionName,
                    format_id: formatId,
                    section_id: sectionId
                },
                success: function(response) {
                    console.log("Section saved successfully: ", response);
                },
                error: function(xhr, status, error) {
                    var errorMessage = xhr.status + ': ' + xhr.statusText;
                    console.error('Error - ' + errorMessage);
                    console.log(xhr.responseText);
                }
            });
        }


        function saveQuestion(sectionId, questionId) {
            var questionName = $('#question_' + questionId).val();
            var guideline = $('#guideline_' + questionId).val();
            var score = $('#score_' + questionId).val();
            var questionType = $('#question_type_' + questionId).val(); // Get the selected question type

            $.ajax({
                url: '/admin/save-question',
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    question_name: questionName,
                    section_id: sectionId,
                    guideline: guideline,
                    score: score,
                    question_type: questionType, // Add question type to the data
                    question_id: questionId
                },
                success: function(response) {
                    // alert(response.success);
                    // addQuestion(sectionId, response.question_id);

                },
                error: function(xhr, status, error) {
                    var errorMessage = xhr.status + ': ' + xhr.statusText;
                    // alert('Error - ' + errorMessage);
                    console.log(xhr.responseText);
                }
            });

        }

        function saveQuestion1(sectionId) {

            // alert(1);
            // var questionName = $('#question_' + questionId).val();
            // var guideline = $('#guideline_' + questionId).val();
            // var score = $('#score_' + questionId).val();
            var questionId = 1; // Replace this with your logic for determining the question ID
            $.ajax({
                url: '/admin/save-question',
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    section_id: sectionId,

                    question_id: questionId
                },
                success: function(response) {
                    // alert(response.success);
                    addQuestion(sectionId, response.question_id);

                },
                error: function(xhr, status, error) {
                    var errorMessage = xhr.status + ': ' + xhr.statusText;
                    // alert('Error - ' + errorMessage);
                    console.log(xhr.responseText);
                }
            });

        }


        function saveOption(optionId, questionId) {
            // alert(1);
            var optionText = $('#option_' + optionId).val();
            var score = $('#option_score_' + optionId).val();
            // alert(optionText);
            // alert(score);
            // Check if both optionText and score are filled
            if (!optionText || !score) {
                // alert('Both text and score must be filled out.');
                return; // Exit the function if validation fails
            } else {

                $.ajax({
                    url: '/admin/save-option',
                    type: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        text: optionText,
                        score: score,
                        option_id: optionId, // Send the option ID if it exists
                        question_id: questionId // Send the correct question ID
                    },
                    success: function(response) {
                        console.log(response.success);
                        // Optionally, show a success message to the user
                    },
                    error: function(xhr, status, error) {
                        var errorMessage = xhr.status + ': ' + xhr.statusText;
                        console.log(xhr.responseText);
                    }
                });
            }
        }

        $(document).on('click', '.add-question', function() {
            // alert(1);
            var sectionId = $(this).data('section-id');
            // alert(sectionId);
            addQuestion(sectionId);
        });
        $(document).on('click', '.add-question1', function() {
            //alert(1);
            var sectionId = $(this).data('section-id');
            // alert(sectionId);
            saveQuestion1(sectionId);
            //alert(2);

        });

        function showAlert() {
            alert("Data saved successfully!");
        }
        $(document).on('click', '.add-section', function() {
            var formatId = $(this).data('format-id');
            // addSection(formatId);
            saveSection1();
        });
        $(document).on('click', '.add-option', function() {

            var questionId = $(this).data('question-id');
            addOption(questionId);
        });
        $(document).on('click', '.delete-btn[data-section-id]', function() {
            var sectionId = $(this).data('section-id');
            if (sectionId) {
                // Confirm deletion
                if (confirm('Are you sure you want to delete section ' + sectionId + '?')) {
                    // AJAX request to delete the section
                    $.ajax({
                        url: '/sections/' + sectionId, // Adjust the URL to match your backend route
                        type: 'DELETE',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content') // Include CSRF token
                        },
                        success: function(response) {
                            // If successful, remove the section from the DOM
                            $('#section_' + sectionId).remove();
                            alert('Section ' + sectionId + ' deleted successfully.');
                        },
                        error: function(xhr, status, error) {
                            // Handle error response
                            alert('Error deleting section: ' + xhr.responseText);
                        }
                    });
                }
            }
        });

        $(document).on('click', '.delete-btn[data-question-id]', function() {
            var questionId = $(this).data('question-id');
            if (questionId) {
                // Confirm deletion
                if (confirm('Are you sure you want to delete question ' + questionId + '?')) {
                    // AJAX request to delete the question
                    $.ajax({
                        url: '/questions/' + questionId, // Adjust the URL to match your backend route
                        type: 'DELETE',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content') // Include CSRF token
                        },
                        success: function(response) {
                            // If successful, remove the question from the DOM
                            $(this).closest('.question').remove();
                            alert('Question ' + questionId + ' deleted successfully.');
                        }.bind(this), // Bind 'this' to access the clicked element
                        error: function(xhr, status, error) {
                            // Handle error response
                            alert('Error deleting question: ' + xhr.responseText);
                        }
                    });
                }
            }
        });

        $(document).on('click', '.delete-option-btn[data-option-id]', function() {
            var optionId = $(this).data('option-id');
            if (optionId) {
                // Confirm deletion
                if (confirm('Are you sure you want to delete option ' + optionId + '?')) {
                    // AJAX request to delete the option
                    $.ajax({
                        url: '/options/' + optionId, // Adjust the URL to match your backend route
                        type: 'DELETE',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content') // Include CSRF token
                        },
                        success: function(response) {
                            // If successful, remove the option from the DOM
                            $(this).closest('.option').remove();
                            alert('Option ' + optionId + ' deleted successfully.');
                        }.bind(this), // Bind 'this' to access the clicked element
                        error: function(xhr, status, error) {
                            // Handle error response
                            alert('Error deleting option: ' + xhr.responseText);
                        }
                    });
                }
            }
        });
    </script>
</body>

</html>