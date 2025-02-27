@include('layouts.adminheader')
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="your_csrf_token_value_here">
    <title>format</title>
    <!-- Other meta tags, stylesheets, and scripts -->
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
            <label for="user_id" class="form-label">Select User:</label>
            <select name="user_id" id="user_id" class="form-select">
                @foreach($users as $id => $name)
                <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3" id="create-format-container" style="display: none;">
            <a href="#" id="create-format-link" class="btn btn-primary">Create New Format</a>
        </div>

        <table class="table mt-5" id="formatTable">
            <thead>
                <tr>
                    <th>Format Name</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <!-- Formats will be populated here via JavaScript -->
            </tbody>
        </table>
    </div>
    <script>
        document.getElementById('user_id').addEventListener('change', function() {
            var userId = this.value;
            var createFormatContainer = document.getElementById('create-format-container');
            if (userId) {
                createFormatContainer.style.display = 'block';
                document.getElementById('create-format-link').href = 'formatcreatepage/' + userId;
            } else {
                createFormatContainer.style.display = 'none';
            }
        });
    </script>
</body>

</html>
@include('layouts.adminfooter')