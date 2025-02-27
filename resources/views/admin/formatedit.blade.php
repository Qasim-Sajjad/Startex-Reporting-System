<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Custom Form Builder</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>

<body>
    @include('layouts.adminheader')

    <div class="container mt-5">
        <h2 class="mb-4">Custom Form Builder</h2>
        <form id="customForm">
            <div id="formBuilder">
                <!-- Form elements will be added here -->
            </div>
            <div class="mb-3">
                <button type="button" class="btn btn-primary mr-2" onclick="addSection()">Add Section</button>
                <button type="button" class="btn btn-success mr-2" onclick="addQuestion()">Add Question</button>
                <button type="submit" class="btn btn-primary">Submit Form</button>
            </div>
        </form>
    </div>

    @include('layouts.adminfooter')

    <script>
        function addSection() {
            let section = document.createElement('div');
            section.className = 'mb-4';
            section.innerHTML = `
                <h3>Section</h3>
                <div class="form-group">
                    <label for="sectionTitle">Section Title</label>
                    <input type="text" class="form-control" id="sectionTitle" name="sectionTitle[]" placeholder="Enter Section Title" required>
                </div>
                <div class="form-group">
                    <label for="sectionDescription">Section Description</label>
                    <textarea class="form-control" id="sectionDescription" name="sectionDescription[]" placeholder="Enter Section Description" required></textarea>
                </div>
            `;
            document.getElementById('formBuilder').appendChild(section);
        }

        function addQuestion() {
            let question = document.createElement('div');
            question.className = 'mb-4';
            question.innerHTML = `
                <h4>Question</h4>
                <div class="form-group">
                    <label for="questionTitle">Question Title</label>
                    <input type="text" class="form-control" id="questionTitle" name="questionTitle[]" placeholder="Enter Question Title" required>
                </div>
                <div class="form-group">
                    <label for="questionType">Question Type</label>
                    <select class="form-control" id="questionType" name="questionType[]" required onchange="toggleOptions(this)">
                        <option value="text">Text</option>
                        <option value="textarea">Textarea</option>
                        <option value="radio">Multiple Choice</option>
                        <option value="checkbox">Checkbox</option>
                    </select>
                </div>
                <div class="questionOptions" style="display: none;">
                    <label for="questionOptions">Options</label>
                    <input type="text" class="form-control" id="questionOptions" name="questionOptions[]" placeholder="Enter Option">
                    <!-- Add more options as needed -->
                </div>
            `;
            document.getElementById('formBuilder').appendChild(question);
        }

        function toggleOptions(selectElement) {
            let optionsDiv = selectElement.parentElement.nextElementSibling;
            if (selectElement.value === 'radio' || selectElement.value === 'checkbox') {
                optionsDiv.style.display = 'block';
            } else {
                optionsDiv.style.display = 'none';
            }
        }

        document.getElementById('customForm').addEventListener('submit', function(event) {
            event.preventDefault();
            // Handle form submission
            alert('Form submitted! This is where you can handle the data.');
        });
    </script>

</body>

</html>