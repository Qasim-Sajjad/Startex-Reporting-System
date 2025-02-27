@include('layouts.adminheader')
<style>
    .mb-3 {
        margin-bottom: 1rem !important;
        display: flex;
        justify-content: flex-end;
        align-items: center;
        margin-right: 25%;
    }

    #formatSelectDiv {
        margin-bottom: 1rem !important;
        display: flex;
        justify-content: flex-end;
        align-items: center;
        margin-right: 25%;
    }

    #hierarchyNameDiv {
        margin-bottom: 1rem !important;
        display: flex;
        justify-content: flex-end;
        align-items: center;
        margin-right: 25%;
    }

    #uploadFileDiv {
        margin-bottom: 1rem !important;
        display: flex;
        justify-content: flex-end;
        align-items: center;
        margin-right: 25%;
    }

    body {
        font-family: Century Gothic;
        background-color: #e9e9e9 !important;

    }

    .form-select {
        width: 50% !important;
        font-size: 15px;
        margin-left: 4%;
    }

    input#hierarchy_name {
        width: 50% !important;
        font-size: 15px;
        margin-left: 4%;
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
        <div class="col-md-2" style="">@include('layouts.adminnavbar')
        </div>
        <div class="col-md-10" style="    padding: 19px; ">
            @if(session('success'))

            <div class="alert alert-success" role="alert">

                {{ session('success') }}

            </div>

            @endif
            <div class="row" style="">
                <h1 style="font-size: 21px;font-weight: bold;margin-top: 2%">Create Hierarchy Aganist Client</h1>

                <div class="col-md-12">

                    <form style="" action="{{ route('processdata') }}" method="POST" enctype="multipart/form-data">

                        @csrf
                        <div id="hierarchyNameDiv" style="display: ;">

                            <label for="hierarchy_name" class="form-label">Enter Hierarchical Name:</label>

                            <input type="text" name="hierarchy_name" id="hierarchy_name" class="form-control" required>
                        </div>
                        <div id="uploadFileDiv" style="">

                            <label for="excel_file" class="form-label">Upload Excel Sheet:</label>

                            <input style="    margin-left: 2%;" type="file" name="excel_file" id="excel_file" accept=".xlsx,.xls" class="form-control-file" required>

                        </div>


                        <div style="display: flex; margin-top: 3%; margin-bottom: 4%; justify-content: flex-end;">
                            <button type="submit" class="btn btn-primary" style="">Submit</button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
</div>





<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>