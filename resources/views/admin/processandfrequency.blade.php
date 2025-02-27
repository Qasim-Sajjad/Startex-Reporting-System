@include('layouts.adminheader')
<!-- Bootstrap JS (Ensure this is placed after Bootstrap CSS) -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>

<div class="container-fluid" style="">
    <div class="row">
        <div class="col-md-2" style="height: 500px;background-color:#d7ddde !important;color:#fff">@include('layouts.adminnavbar')
        </div>
        <div class="col-md-10" style="    padding: 19px; ">
            @csrf

            @if(session('success'))

            <div class="alert alert-success" role="alert">

                {{ session('success') }}

            </div>

            @endif
<div class="container-fluid">
  

    <div class="row justify-content-center">

  <form action="{{ route('processFrequency') }}" method="POST">
        @csrf

        <!-- Process Name -->
        <div class="mb-3">
            <label for="process_name" class="form-label">Process Name</label>
            <input type="text" name="process_name" id="process_name" class="form-control" required>
        </div>

        <!-- Select Frequency -->
        <div class="mb-3">
            <label for="frequency_id" class="form-label">Select Frequency</label>
            <select name="frequency_id" id="frequency_id" class="form-control" required>
                <option value="">-- Select Frequency --</option>
                @foreach($frequencies as $frequency)
                    <option value="{{ $frequency->id }}">{{ $frequency->name }}</option>
                @endforeach
            </select>
        </div>

        <!-- Start Date -->
        <div class="mb-3">
            <label for="start_date" class="form-label">Start Date</label>
            <input type="date" name="start_date" id="start_date" class="form-control" required>
        </div>

        <!-- End Date -->
        <div class="mb-3">
            <label for="end_date" class="form-label">End Date</label>
            <input type="date" name="end_date" id="end_date" class="form-control" required>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn btn-primary">Save Process</button>
    </form>

    </div>
</div>
      
          
          </div>

    </div>

