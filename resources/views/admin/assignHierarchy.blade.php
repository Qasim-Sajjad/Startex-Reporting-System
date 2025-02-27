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
        background-color: #f8f9fa;

    }

    .form-select {
        width: 62% !important;
        font-size: 16px;
        margin-left: 4%;
    }

    input#hierarchy_name {
        width: 62% !important;
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
</style>

<div class="container-fluid" style="">
    <div class="row">
        <div class="col-md-2" style="height: 500px;background-color:#d7ddde !important;color:#fff">@include('layouts.adminnavbar')
        </div>
        <div class="col-md-10" style="    padding: 19px; ">
            @csrf
@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
@endif

            <div class="row">
                <h1 style="font-size: 21px;font-weight: bold; margin-top: 2%;">Assign Hierarchy To Process</h1>

                <div class="col-md-8">
<form action="{{ route('assignformat') }}" method="POST" onsubmit="return validateForm()">
    @csrf

    <div id="hierarchySelectDiv">
        <label for="hierarchy_id" class="form-label">Select Hierarchy:</label>
        <select name="hierarchy_id" id="hierarchy_id" class="form-select">
            <option value="">Select Hierarchy</option>
            @foreach($hierarchy as $id => $name)
                <option value="{{ $name->id }}">{{ $name->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <label for="Process" class="form-label">Select Process:</label>
        <select name="Process" id="Process" class="form-select">
            <option value="">Select Process</option>
            @foreach($process as $id => $name)
                <option value="{{ $name->id }}">{{ $name->name }}</option>
            @endforeach
        </select>
    </div>

    <div style="margin-bottom: 1rem !important; display: flex; justify-content: flex-end; align-items: center;">
        <button type="submit" class="btn btn-primary">Assign</button>
    </div>
</form>
                </div>
                <div class="col-m-4"></div>
            </div>
        </div>
    </div>
</div>


<script>
    function validateForm() {
        const hierarchy = document.getElementById('hierarchy_id').value;
        const process = document.getElementById('Process').value;

        if (!hierarchy) {
            alert('Please select a hierarchy.');
            return false;
        }

        if (!process) {
            alert('Please select a process.');
            return false;
        }

        return true; // Allow form submission
    }
</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
