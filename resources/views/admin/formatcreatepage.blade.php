<!DOCTYPE html>

<html lang="en">



<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Create Format for {{ $user->name }}</title>

    <!-- Bootstrap CSS for styling -->

    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">

    <!-- jQuery library -->

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Bootstrap JS for additional functionalities -->

    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <style>
        /* Custom styles can be added here */
    </style>

</head>



<body>

    <div class="container">

        @csrf

        @if(session('success'))

        <div class="alert alert-success" role="alert">

            {{ session('success') }}

        </div>

        @endif







        <div class="mb-3">

            <label for="format_name" class="form-label">Format Name:</label>

            <!-- Use textarea instead of input -->

            <textarea class="form-control" id="format_name" name="format_name" onchange="saveFormat('{{ $user->id }}')"></textarea>

            <!-- Ensure correct function call in onchange -->



            <input type="hidden" name="format_id" id="format_id" value="">

        </div>



        <div id="sections-container">

            <!-- Section Template -->

            <template id="section-template">

                <div class="section mb-3">

                    <h3>Section <span class="section-index"></span></h3>

                    <div class="mb-2">

                        <label for="section_name" class="form-label">Section Name:</label>
                        <button class="btn btn-danger btn-sm remove-section-btn">Remove Section</button>

                        <input type="text" class="form-control section-name" name="section_name">

                        <input type="hidden" class="format-id" name="format_id" value="">

                    </div>

                    <div class="questions-container">

                        <button class="btn btn-primary add-question-btn" onclick="addQuestion(this)">Add Question</button>

                    </div>

                    <div class="options-container">

                    </div>

                    <div class="keyword-container">

                    </div>

                </div>

            </template>

        </div>



        <button class="btn btn-success mb-3" onclick="addSection()">Add Section</button>

        <button class="btn btn-primary mb-3" onclick="saveSectionsAndQuestions($('#format_id').val())">Save Sections and Questions</button>



        <p id="display_format_name"></p>

    </div>



    <script>
        // Save Format Name Function

        function saveFormat(userID) {

            const formatName = $('#format_name').val();



            $.ajax({

                type: 'POST',

                url: '{{ route("updateFormatName") }}', // Use the route function to generate the correct URL

                data: {

                    user_id: userID,

                    format_name: formatName,

                    _token: '{{ csrf_token() }}'

                },

                success: function(response) {

                    console.log('Format name saved successfully.');

                    // Optionally update display or other actions

                    $('#format_id').val(response.format_id);

                    console.log(response);

                },

                error: function(xhr, status, error) {

                    console.error('Error saving format name:', error);

                }

            });

        }



        // Counter for section indices

        let sectionCounter = 1;



        // Add Section Function

        function addSection() {

            // Clone the section template content

            const template = document.getElementById('section-template');

            const clone = template.content.cloneNode(true);

            const section = clone.querySelector('.section');

            section.querySelector('.section-index').textContent = sectionCounter;

            section.querySelector('.format-id').value = $('#format_id').val(); // Set the format ID



            // Append the cloned section to the sections container

            document.getElementById('sections-container').appendChild(clone);



            sectionCounter++;
            section.querySelector('.remove-section-btn').addEventListener('click', function() {
                section.remove();
            });

        }



        // Add Question Function

        function addQuestion(btn) {

            const questionsContainer = btn.closest('.section').querySelector('.questions-container');

            if (!questionsContainer) {

                console.error('Questions container not found.');

                return;

            }



            const questionField = document.createElement('div');

            questionField.classList.add('mb-2');

            questionField.innerHTML = `

                <label for="question_name" class="form-label">Question:</label>
        <button class="btn btn-danger btn-sm remove-question-btn">Remove Question</button>

                <input type="text" class="form-control question-name" name="question_name">

                <div class="options-container"></div>
    

                <button type="button" class="btn btn-sm btn-primary add-option-btn">Add Option</button>

                <div class="guide-container mb-2">

                    <label for="question_guide" class="form-label">Question Guidelines:</label>

                    <textarea class="form-control question-guide" name="question_guide"></textarea>

                </div>

                <div class="question-type mb-2">

                    <label for="question_type" class="form-label">Question Type:</label>

                    <select class="form-control question-type-select" name="question_type">

                        <option value="single">Single Choice</option>

                        <option value="multiple">Multiple Choice</option>

                        <option value="linear">Linear Scale</option>

                    </select>

                </div>

                <div class="question-score mb-2">

                    <label for="question_score" class="form-label">Question Score:</label>

                    <input type="number" class="form-control question-score-input" name="question_score">

                </div>

                       <div class="keyword-container"></div>

                <button type="button" class="btn btn-sm btn-primary add-keyword-btn">Add keyword</button>

            `;

            questionsContainer.appendChild(questionField);

            questionField.querySelector('.remove-question-btn').addEventListener('click', function() {
                questionField.remove();
            });

            // Add event listener for adding options

            questionField.querySelector('.add-option-btn').addEventListener('click', function() {

                const optionsContainer = questionField.querySelector('.options-container');

                const optionField = document.createElement('div');

                optionField.classList.add('mb-2');

                optionField.innerHTML = `

                    <label for="option_label" class="form-label">Option Label:</label>

                    <input type="text" class="form-control option-label" name="option_label">

                    <label for="option_score" class="form-label">Option Score:</label>

                    <input type="number" class="form-control option-score" name="option_score">

                `;

                optionsContainer.appendChild(optionField);
                optionClone.querySelector('.remove-option-btn').addEventListener('click', function() {
                    optionClone.remove();
                });

            });

            questionField.querySelector('.add-keyword-btn').addEventListener('click', function() {

                const keywordContainer = questionField.querySelector('.keyword-container');

                const keywordField = document.createElement('div');

                keywordField.classList.add('mb-2');

                keywordField.innerHTML = `

                    <label for="keyword_label" class="form-label">keyword Label:</label>

                    <input type="text" class="form-control keyword-label" name="keyword_label">

                `;

                keywordContainer.appendChild(keywordField);

            });

        }



        // Save Sections and Questions Function

        function saveSectionsAndQuestions(formatID) {

            const sections = [];

            // Iterate through each section

            $('.section').each(function(index) {

                const sectionName = $(this).find('.section-name').val();

                const questions = [];

                // Iterate through each question in the section

                $(this).find('.question-name').each(function(index) {

                    const questionName = $(this).val();

                    const questionGuide = $(this).siblings('.guide-container').find('.question-guide').val();

                    const questionType = $(this).siblings('.question-type').find('.question-type-select').val();

                    const questionScore = $(this).siblings('.question-score').find('.question-score-input').val();

                    const options = [];

                    // Iterate through each option for the question

                    $(this).siblings('.options-container').find('.option-label').each(function(index) {

                        const optionLabel = $(this).val();

                        const optionScore = $(this).siblings('.option-score').val();

                        options.push({

                            label: optionLabel,

                            score: optionScore

                        });

                    });

                    const keywords = [];

                    // Iterate through each keyword for the question

                    $(this).siblings('.keyword-container').find('.keyword-label').each(function(index) {

                        const keywordLabel = $(this).val();

                        keywords.push({

                            label: keywordLabel

                        });

                    });

                    questions.push({

                        question_name: questionName,

                        question_guide: questionGuide,

                        question_type: questionType,

                        question_score: questionScore,

                        options: options,

                        keywords: keywords // Include keywords in the question data

                    });

                });

                sections.push({

                    section_name: sectionName,

                    questions: questions

                });

            });



            // AJAX call to save sections and questions

            $.ajax({

                type: 'POST',

                url: '{{ route("savesectionsandquestions") }}',

                data: {

                    format_id: formatID,

                    sections: sections,

                    _token: '{{ csrf_token() }}'

                },

                success: function(response) {

                    console.log('Sections and questions saved successfully.');

                    if (response.redirect) {

                        window.location.href = response.redirect;

                    }

                },

                error: function(xhr, status, error) {

                    console.error('Error saving sections and questions:', error);

                }

            });

        }
    </script>

</body>



</html>