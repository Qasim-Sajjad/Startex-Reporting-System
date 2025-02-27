<!-- resources/views/layouts/clientnavbar.blade.php -->

@php
use Illuminate\Support\Facades\DB;
@endphp
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

<style>
    .navbar-light .navbar-nav .nav-link {
        color: blue !important;
        text-align: center;
        margin-top: 0px;
        font-size: 16px;
        color: black;
        text-decoration: underline !important;
    }

    body {
        background-color: #f8f9fa !important;
        font-family: Century Gothic !important;
    }

    .progress-container {
        display: flex;
        margin-top: 10%;
        flex-direction: column;
        align-items: center;
    }

    .progress-container .progress-step {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background-color: grey;
        color: white;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 16px;
        font-weight: bold;
        position: relative;
        margin-top: 8%;
    }

    .progress-container .progress-step.completed {
        background-color: green;
    }

    .progress-container::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 5%;
        right: 5%;
        height: 5px;
        background-color: lightgrey;
        z-index: -1;
    }

    .progress-container .progress-step.completed::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 100%;
        height: 5px;
        background-color: green;
        z-index: -1;
    }

    .progress-container .progress-step:first-child::before {
        display: none;
    }

    .progress-label {
        text-align: center;
        margin-top: 8px;
        font-size: 16px;
        color: black;
    }

    .progress-link {
        text-decoration: none;
        /* Removes the blue underline */
        color: inherit;
        /* Prevents link color from turning blue */
    }

    .progress-link div {
        color: inherit;
        /* Ensures the div elements keep their original color */
    }

    .progress-link:visited {
        color: inherit;
        /* Prevents visited links from changing color */
    }

    .progress-link:hover {
        text-decoration: none;
        /* Ensures no underline on hover as well */
    }

    a {
        color: grey !important;
        text-decoration: none !important;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
</style>

<nav class="navbar navbar-expand-lg navbar-light" style="    border-right: 1px solid lightgray; font-size: 80%; font-family: Century Gothic;">
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0 flex-column">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 flex-column">
                <div class="progress-container">
                    <a href="{{ route('createhierarchy') }}">
                        <div class="progress-step" id="step-1">1</div>
                        <div class="progress-label">Create Hierarchy</div>
                    </a>

                    <a href="{{ route('createProcess') }}">
                        <div class="progress-step" id="step-2">2</div>
                        <div class="progress-label">Create Process/Frequency</div>
                    </a>
                    <a href="{{ route('createformat') }}">
                        <div class="progress-step" id="step-3">3</div>
                        <div class="progress-label">Create/Edit checklist</div>
                    </a>
                   
                      <a href="{{ route('createcriteria') }}">
                        <div class="progress-step" id="step-4">4</div>
                        <div class="progress-label">Create/Edit Criteria</div>
                    </a>
                    <a href="{{ route('assignHierarchy') }}">
                        <div class="progress-step" id="step-5">5</div>
                        <div class="progress-label">Assign Hierarchy to process</div>
                    </a>
                    <a href="{{ route('DepartmentUser') }}">
                        <div class="progress-step" id="step-6">6</div>
                        <div class="progress-label">Create/Edit Department</div>
                    </a>
                </div>
                <li class="nav-item">
                    <a id="logoutLink" class="nav-link" href="{{ route('logout') }}">
                        <i class="fas fa-sign-out-alt"></i> Log out
                    </a>
                </li>
            </ul>
    </div>
</nav>