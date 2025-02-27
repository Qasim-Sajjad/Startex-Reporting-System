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
        <div class="col-md-5 mb-4">
            <!-- Block 1: All Processes -->
    <div class="card text-center" data-bs-toggle="modal" data-bs-target="#processesModal">
        <div class="card-body">
            <div class="mb-3">
                <!-- Emoji Icon for Processes -->
                <span style="font-size: 40px;">📊</span>
            </div>
            <h5 class="card-title">All Processes</h5>
            <p class="card-text">Total Processes: {{ $processCount }}</p>
        </div>
    </div>
        </div>

        <div class="col-md-5 mb-4">
            <!-- Block 2: All Tasks (Example) -->
            <div class="card text-center">
                <div class="card-body">
                    <div class="mb-3">
                        <!-- Emoji Icon for Tasks -->
                        <span style="font-size: 40px;">📋</span>
                    </div>
                    <h5 class="card-title">All Tasks</h5>
                    <p class="card-text">Here you can display all tasks data.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-5 mb-4">
            <!-- Block 3: Late Report -->
            <div class="card text-center">
                <div class="card-body">
                    <div class="mb-3">
                        <!-- Emoji Icon for Late Reports -->
                        <span style="font-size: 40px;">⏳</span>
                    </div>
                    <h5 class="card-title">Late Report</h5>
                    <p class="card-text">Display the late report here.</p>
                </div>
            </div>
        </div>

        <div class="col-md-5 mb-4">
            <!-- Block 4: Late Tasks -->
            <div class="card text-center">
                <div class="card-body">
                    <div class="mb-3">
                        <!-- Emoji Icon for Late Tasks -->
                        <span style="font-size: 40px;">⏰</span>
                    </div>
                    <h5 class="card-title">Late Tasks</h5>
                    <p class="card-text">Display late tasks here.</p>
                </div>
            </div>
        </div>
    </div>
</div>
      
          
          </div>

    </div>
</div>

<!-- Modal for displaying all processes -->
<div class="modal fade" id="processesModal" tabindex="-1" aria-labelledby="processesModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="processesModalLabel">All Processes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ul id="processList">
                    {{-- <!-- Processes will be populated here --> --}}
                    @foreach($mergedData as $process)
                        <li>
                              <a href="{{ route('process.detailss', ['process_id' => $process->id, 'wave_id' => 'YTD']) }}">
                                {{ $process->processname  }} - Score: {{ $process->score }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>
