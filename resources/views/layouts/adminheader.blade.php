<!DOCTYPE html>

<html lang="en">



<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <style>
        .navbar-dark {

            background-color: #00BCD4 !important;

            /* Dark blue background color */

        }



        .navbar-dark .navbar-nav .nav-link {

            color: #ffffff;

            /* White text color */

        }



        .navbar-dark .navbar-nav .nav-link:hover {

            color: #f8f9fa;

            /* Lighter text color on hover */

        }



        .navbar-dark .navbar-nav .nav-item {

            border-right: 1px solid #ffffff;

            /* White border between nav items */

        }



        .navbar-dark .navbar-nav .nav-item:last-child {

            border-right: none;

            /* Remove border on last nav item */

        }
    </style>
</head>

<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container-fluid">
                <img src="../public/image/logo.gif" alt="Logo" style="    height: 73px;
    padding: 14px 0px 14px 0px;">

                <!-- <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">

                    <span class="navbar-toggler-icon"></span>

                </button>

                <div class="collapse navbar-collapse" id="navbarNav">

                    <ul class="navbar-nav ms-auto">





                        <li class="nav-item dropdown">

                            <a class="nav-link dropdown-toggle" href="#" id="usermanagement" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">

                                User Management

                            </a>

                            <div class="dropdown-menu" aria-labelledby="usermanagement">

                                <a class="dropdown-item" href="{{ route('usermanagement') }}">Create Main Users</a>

                                <a class="dropdown-item" href="{{ route('HierarchyUsers') }}">Create Hierarchy Users</a>






            </div>

            </li>

            <li class="nav-item">

                <a class="nav-link" href="{{ route('createformat') }}">Format Creation</a>

            </li>

            <li class="nav-item">

                <a class="nav-link" href="{{ route('createcriteria') }}">Criteria Setting</a>

            </li>

            <li class="nav-item">

                <a class="nav-link" href="{{ route('createhierarchy') }}">Create Hierarchy</a>

            </li>


                <li class="nav-item">

                    <a class="nav-link" href="{{ route('createwave') }}">Wave Creation</a>

                </li>

                <li class="nav-item dropdown">

                    <a class="nav-link dropdown-toggle" href="#" id="assignManagementDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">

                        Assign management

                    </a>

                    <div class="dropdown-menu" aria-labelledby="assignManagementDropdown">

                        <a class="dropdown-item" href="{{ route('assignHierarchy') }}">Assign Hierarchy to format</a>

                        <a class="dropdown-item" href="{{ route('assignproject') }}">Assign Project to manager</a>

                        <a class="dropdown-item" href="{{ route('assignshops') }}">Assign shops to shopper</a>




            </div>

            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('contestlist1') }}">Contest List</a>
            </li>

            <li class="nav-item">

                <a class="nav-link" href="{{ route('overallperformance') }}">overallperformance</a>

            </li>

            <li class="nav-item">
                <a id="logoutLink" class="nav-link" href="{{ route('logout') }}">Log out</a>
            </li>


                </ul>

            </div> -->

            </div>

        </nav>

    </header>

</body>



</html>