<!doctype html>

<html lang="en" data-bs-theme="blue-theme">
<head>

  <meta charset="utf-8">

  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title>Startex Marketing</title>

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

  <!-- Material Icons -->
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">

  <!-- loader-->

  <link href="../../public/css/pace.min.css" rel="stylesheet">
  <script src="../../public/js/pace.min.js"></script>

  <!--plugins-->

  <link href="../../public/plugins/perfect-scrollbar/css/perfect-scrollbar.css" rel="stylesheet">
  <link rel="stylesheet" type="text/css" href="../../public/plugins/metismenu/metisMenu.min.css">
  <link rel="stylesheet" type="text/css" href="../../public/plugins/metismenu/mm-vertical.css">
  <link rel="stylesheet" type="text/css" href="../../public/plugins/simplebar/css/simplebar.css">

  <!--bootstrap css-->
  <link href="../../public/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css?family=Material+Icons+Outlined" rel="stylesheet">

  <!--main css-->
  <link href="../../public/css/bootstrap-extended.css" rel="stylesheet">
  <link href="../../public/css/sass/main.css" rel="stylesheet">
  <link href="../../public/css/sass/dark-theme.css" rel="stylesheet">
  <link href="../../public/css/sass/blue-theme.css" rel="stylesheet">
  <link href="../../public/css/sass/semi-dark.css" rel="stylesheet">
  <link href="../../public/css/sass/bordered-theme.css" rel="stylesheet">
  <link href="../../public/css/sass/responsive.css" rel="stylesheet">
  <link href="../../public/css/ProcessDashboard/processDashboard.css" rel="stylesheet">

  <style>
    body,
    h1, h2, h3, h4, h5, h6,
    p, span, div:not(.material-icons-outlined):not(.fa):not(.fa-solid):not(.fa-regular):not(.fa-light):not(.fa-brands) {
      font-family: 'Google Sans', sans-serif !important;
    }
    
    /* Override font-family for Material Icons */
    .material-icons-outlined {
      font-family: 'Material Icons Outlined' !important;
    }
  </style>
</head>

<body class="pace-done toggled">

  <!--start header-->
  <header class="custom-header">
    <nav class="custom-nav">
      <!-- Logo Section -->
      <div class="custom-logo">
        <div class="logo-wrapper">
          <img src="../../public/image/logo.gif" class="logo-img" alt="" style="width: 150px; height: auto;">
          <span class="logo-text"></span>
        </div>
      </div>

      <!-- Center Navigation -->
      <div class="custom-nav-center">
        <div class="d-flex align-items-center">
          <h5 class="mb-0 me-2">Process Dashboard:</h5>
          <span>{{ $selectedProcess->name }}</span>
        </div>
      </div>

      <!-- Keep search-bar with zero width but maintain classes -->
      <div class="search-bar" style="width: 0; overflow: hidden; position: absolute; visibility: hidden;">
        <div class="position-relative" style="display: none;">
          <input class="form-control rounded-5 px-5 search-control d-lg-block d-none" type="text" placeholder="Search">
          <span class="material-icons-outlined position-absolute d-lg-block d-none ms-3 translate-middle-y start-0 top-50">search</span>
          <span class="material-icons-outlined position-absolute me-3 translate-middle-y end-0 top-50 search-close">close</span>
          <div class="search-popup p-3">
            <div class="card rounded-4 overflow-hidden">
              <div class="card-header d-lg-none">
                <div class="position-relative">
                  <input class="form-control rounded-5 px-5 mobile-search-control" type="text" placeholder="Search">
                  <span class="material-icons-outlined position-absolute ms-3 translate-middle-y start-0 top-50">search</span>
                  <span class="material-icons-outlined position-absolute me-3 translate-middle-y end-0 top-50 mobile-search-close">close</span>
                </div>
              </div>
              <div class="card-body search-content">
                <p class="search-title">Recent Searches</p>
                <div class="d-flex align-items-start flex-wrap gap-2 kewords-wrapper">
                  <a href="javascript:;" class="kewords"><span>Angular Template</span><i class="material-icons-outlined fs-6">search</i></a>
                  <a href="javascript:;" class="kewords"><span>Dashboard</span><i class="material-icons-outlined fs-6">search</i></a>
                  <a href="javascript:;" class="kewords"><span>Admin Template</span><i class="material-icons-outlined fs-6">search</i></a>
                  <a href="javascript:;" class="kewords"><span>Bootstrap 5 Admin</span><i class="material-icons-outlined fs-6">search</i></a>
                  <a href="javascript:;" class="kewords"><span>Html eCommerce</span><i class="material-icons-outlined fs-6">search</i></a>
                  <a href="javascript:;" class="kewords"><span>Sass</span><i class="material-icons-outlined fs-6">search</i></a>
                  <a href="javascript:;" class="kewords"><span>laravel 9</span><i class="material-icons-outlined fs-6">search</i></a>
                </div>
                <hr>
                <p class="search-title">Tutorials</p>
                <div class="search-list d-flex flex-column gap-2">
                  <div class="search-list-item d-flex align-items-center gap-3">
                    <div class="list-icon">
                      <i class="material-icons-outlined fs-5">play_circle</i>
                    </div>
                    <div class="">
                      <h5 class="mb-0 search-list-title">Wordpress Tutorials</h5>
                    </div>
                  </div>
                  <div class="search-list-item d-flex align-items-center gap-3">
                    <div class="list-icon">
                      <i class="material-icons-outlined fs-5">shopping_basket</i>
                    </div>
                    <div class="">
                      <h5 class="mb-0 search-list-title">eCommerce Website Tutorials</h5>
                    </div>
                  </div>
                  <div class="search-list-item d-flex align-items-center gap-3">
                    <div class="list-icon">
                      <i class="material-icons-outlined fs-5">laptop</i>
                    </div>
                    <div class="">
                      <h5 class="mb-0 search-list-title">Responsive Design</h5>
                    </div>
                  </div>
                </div>
                <hr>
                <p class="search-title">Members</p>
                <div class="search-list d-flex flex-column gap-2">
                  <div class="search-list-item d-flex align-items-center gap-3">
                    <div class="memmber-img">
                      <img src="assets/images/avatars/01.png" width="32" height="32" class="rounded-circle" alt="">
                    </div>
                    <div class="">
                      <h5 class="mb-0 search-list-title">Andrew Stark</h5>
                    </div>
                  </div>
                  <div class="search-list-item d-flex align-items-center gap-3">
                    <div class="memmber-img">
                      <img src="assets/images/avatars/02.png" width="32" height="32" class="rounded-circle" alt="">
                    </div>
                    <div class="">
                      <h5 class="mb-0 search-list-title">Snetro Jhonia</h5>
                    </div>
                  </div>
                  <div class="search-list-item d-flex align-items-center gap-3">
                    <div class="memmber-img">
                      <img src="assets/images/avatars/03.png" width="32" height="32" class="rounded-circle" alt="">
                    </div>
                    <div class="">
                      <h5 class="mb-0 search-list-title">Michle Clark</h5>
                    </div>
                  </div>
                </div>
              </div>
              <div class="card-footer text-center bg-transparent">
                <a href="javascript:;" class="btn w-100">See All Search Results</a>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Side Icons -->
      <div class="custom-nav-right">
        <!-- Toggle Buttons -->
        <div class="nav-icon-wrapper me-3">
          <div class="toggle-buttons">
            <button class="toggle-btn active">YTD Wise</button>
            <button class="toggle-btn">Wave Wise</button>
          </div>
        </div>

        <!-- Notifications -->
        <div class="nav-icon-wrapper">
          <a href="javascript:;" class="nav-icon notification-icon" data-bs-toggle="dropdown">
            <i class="material-icons-outlined">notifications</i>
            <span class="badge-notify">5</span>
          </a>
          <div class="dropdown-menu dropdown-notify dropdown-menu-end shadow">
            <div class="px-3 py-1 d-flex align-items-center justify-content-between border-bottom">
              <h5 class="notiy-title mb-0">Notifications</h5>
              <div class="dropdown">
                <button class="btn btn-secondary dropdown-toggle dropdown-toggle-nocaret option" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                  <span class="material-icons-outlined">more_vert</span>
                </button>
                <div class="dropdown-menu dropdown-option dropdown-menu-end shadow">
                  <div><a class="dropdown-item d-flex align-items-center gap-2 py-2" href="javascript:;"><i class="material-icons-outlined fs-6">inventory_2</i>Archive All</a></div>
                  <div><a class="dropdown-item d-flex align-items-center gap-2 py-2" href="javascript:;"><i class="material-icons-outlined fs-6">done_all</i>Mark all as read</a></div>
                  <div><a class="dropdown-item d-flex align-items-center gap-2 py-2" href="javascript:;"><i class="material-icons-outlined fs-6">mic_off</i>Disable Notifications</a></div>
                  <div><a class="dropdown-item d-flex align-items-center gap-2 py-2" href="javascript:;"><i class="material-icons-outlined fs-6">grade</i>What's new ?</a></div>
                  <div><hr class="dropdown-divider"></div>
                  <div><a class="dropdown-item d-flex align-items-center gap-2 py-2" href="javascript:;"><i class="material-icons-outlined fs-6">leaderboard</i>Reports</a></div>
                </div>
              </div>
            </div>
            <div class="notify-list">
              <div>
                <a class="dropdown-item border-bottom py-2" href="javascript:;">
                  <div class="d-flex align-items-center gap-3">
                    <div class="">
                      <img src="assets/images/avatars/01.png" class="rounded-circle" width="45" height="45" alt="">
                    </div>
                    <div class="">
                      <h5 class="notify-title">Congratulations Jhon</h5>
                      <p class="mb-0 notify-desc">Many congtars jhon. You have won the gifts.</p>
                      <p class="mb-0 notify-time">Today</p>
                    </div>
                    <div class="notify-close position-absolute end-0 me-3">
                      <i class="material-icons-outlined fs-6">close</i>
                    </div>
                  </div>
                </a>
              </div>
              <div>
                <a class="dropdown-item border-bottom py-2" href="javascript:;">
                  <div class="d-flex align-items-center gap-3">
                    <div class="user-wrapper bg-primary text-primary bg-opacity-10">
                      <span>RS</span>
                    </div>
                    <div class="">
                      <h5 class="notify-title">New Account Created</h5>
                      <p class="mb-0 notify-desc">From USA an user has registered.</p>
                      <p class="mb-0 notify-time">Yesterday</p>
                    </div>
                    <div class="notify-close position-absolute end-0 me-3">
                      <i class="material-icons-outlined fs-6">close</i>
                    </div>
                  </div>
                </a>
              </div>
              <div>
                <a class="dropdown-item border-bottom py-2" href="javascript:;">
                  <div class="d-flex align-items-center gap-3">
                    <div class="">
                      <img src="assets/images/apps/13.png" class="rounded-circle" width="45" height="45" alt="">
                    </div>
                    <div class="">
                      <h5 class="notify-title">Payment Recived</h5>
                      <p class="mb-0 notify-desc">New payment recived successfully</p>
                      <p class="mb-0 notify-time">1d ago</p>
                    </div>
                    <div class="notify-close position-absolute end-0 me-3">
                      <i class="material-icons-outlined fs-6">close</i>
                    </div>
                  </div>
                </a>
              </div>
              <div>
                <a class="dropdown-item border-bottom py-2" href="javascript:;">
                  <div class="d-flex align-items-center gap-3">
                    <div class="">
                      <img src="assets/images/apps/14.png" class="rounded-circle" width="45" height="45" alt="">
                    </div>
                    <div class="">
                      <h5 class="notify-title">New Order Recived</h5>
                      <p class="mb-0 notify-desc">Recived new order from michle</p>
                      <p class="mb-0 notify-time">2:15 AM</p>
                    </div>
                    <div class="notify-close position-absolute end-0 me-3">
                      <i class="material-icons-outlined fs-6">close</i>
                    </div>
                  </div>
                </a>
              </div>
              <div>
                <a class="dropdown-item border-bottom py-2" href="javascript:;">
                  <div class="d-flex align-items-center gap-3">
                    <div class="">
                      <img src="assets/images/avatars/06.png" class="rounded-circle" width="45" height="45" alt="">
                    </div>
                    <div class="">
                      <h5 class="notify-title">Congratulations Jhon</h5>
                      <p class="mb-0 notify-desc">Many congtars jhon. You have won the gifts.</p>
                      <p class="mb-0 notify-time">Today</p>
                    </div>
                    <div class="notify-close position-absolute end-0 me-3">
                      <i class="material-icons-outlined fs-6">close</i>
                    </div>
                  </div>
                </a>
              </div>
              <div>
                <a class="dropdown-item py-2" href="javascript:;">
                  <div class="d-flex align-items-center gap-3">
                    <div class="user-wrapper bg-danger text-danger bg-opacity-10">
                      <span>PK</span>
                    </div>
                    <div class="">
                      <h5 class="notify-title">New Account Created</h5>
                      <p class="mb-0 notify-desc">From USA an user has registered.</p>
                      <p class="mb-0 notify-time">Yesterday</p>
                    </div>
                    <div class="notify-close position-absolute end-0 me-3">
                      <i class="material-icons-outlined fs-6">close</i>
                    </div>
                  </div>
                </a>
              </div>
            </div>
          </div>
        </div>

        <!-- User Profile -->
        <div class="nav-icon-wrapper">
          <a href="javascript:;" class="nav-icon" data-bs-toggle="dropdown">
            <i class="fa-solid fa-user-circle fa-lg"></i>
          </a>
          <div class="dropdown-menu dropdown-user dropdown-menu-end shadow">
            <a class="dropdown-item gap-2 py-2" href="javascript:;">
              <div class="text-center">
                <i class="fa-solid fa-user-circle fa-4x mb-3"></i>
                <h5 class="user-name mb-0 fw-bold">Hello {{ Auth::user() }}</h5>
              </div>
            </a>
            <hr class="dropdown-divider">
            <hr class="dropdown-divider">
            <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('logout') }}">
              <i class="fa-solid fa-sign-out-alt"></i>Logout
            </a>
          </div>
        </div>
      </div>
    </nav>
  </header>
  <!--end header-->

  <!--start main wrapper-->
  <main class="main-wrapper">
    <div class="main-content">
      <!-- Hide breadcrumb -->
      <div class="page-breadcrumb" style="width: 0; height: 0; overflow: hidden; display: none;">
        <div class="breadcrumb-title pe-3">Process Dashboard</div>
        <div class="ps-3">
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
              <li class="breadcrumb-item">
                <a href="javascript:;"><i class="bx bx-home-alt"></i></a>
              </li>
              <li class="breadcrumb-item active" aria-current="page">{{ $selectedProcess->name }}</li>
            </ol>
          </nav>
        </div>
      </div>

      <!-- First Row -->
      <div class="row g-3">
        <!-- Process Details Card -->
        <div class="col-12 col-lg-3">
          <div class="card rounded-3 h-100">
            <div class="card-body">
              <h5 class="mb-2">Process Details</h5>
              <div class="d-flex flex-column gap-1">
                <div class="d-flex justify-content-between">
                  <span class="text-secondary">Process Name</span>
                  <span>{{ $selectedProcess->name ?? '-' }}</span>
                </div>
                <div class="d-flex justify-content-between">
                  <span class="text-secondary">Coverage</span>
                  <span>{{ $totalShop ?? '-' }} stores</span>
                </div>
                <div class="d-flex justify-content-between">
                  <span class="text-secondary">Frequency</span>
                  <span>
                    @switch($selectedProcess->frequency_id ?? '')
                      @case(1)
                        Daily
                        @break
                      @case(2)
                        Weekly
                        @break
                      @case(3)
                        Monthly
                        @break
                      @default
                        -
                    @endswitch
                  </span>
                </div>
                <div class="d-flex justify-content-between">
                  <span class="text-secondary">Created on</span>
                  <span>-</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- YTD Score Card -->
        <div class="col-12 col-lg-3">
          <div class="card rounded-4 h-100">
            <div class="card-body ytd-card">
              <h5 class="mb-0">YTD</h5>
              <div class="this-month">This Month: 70%</div>
              <div id="ytdChart"></div>
              <div class="change text-danger">3%▼</div>
            </div>
          </div>
        </div>

        <!-- Trend Card -->
        <div class="col-12 col-lg-4 trend-card-column">
          <div class="card rounded-4 h-100">
            <div class="card-body">
              <div class="text-center">
                <h6 class="mb-0">Trend</h6>
              </div>
              <div class="mt-4" id="chart14"></div>
            </div>
          </div>
        </div>

        <!-- Submissions Cards -->
        <div class="col-12 col-lg-2 submissions-card-column">
          <div class="submissions-wrapper">
            <div class="submissions-card bg-success bg-opacity-10">
              <h2 class="mb-0">{{ $totalSubmittedShop->totalsubmittedShop ?? '0' }}</h2>
              <p class="mb-0">Total Submissions</p>
            </div>
            <div class="submissions-card bg-danger bg-opacity-10">
              <h2 class="mb-0">{{ $MissingShop ?? '0' }}</h2>
              <p class="mb-0">Missing Submissions</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Second Row -->
      <div class="row g-3 mt-3">
        <!-- Process Overview -->
        <div class="col-12 col-lg-3">
          <div class="card rounded-4 h-100">
            <div class="card-body">
              <h5 class="mb-3">Task Overview</h5>
              <table class="process-overview-table">
                <thead>
                  <tr>
                    <th></th>
                    <th>YTD</th>
                    <th>This Month</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>Total Tasks</td>
                    <td>100</td>
                    <td>20</td>
                  </tr>
                  <tr>
                    <td>Completed</td>
                    <td class="text-success">85</td>
                    <td class="text-success">15</td>
                  </tr>
                  <tr>
                    <td>Over Due</td>
                    <td class="text-danger">10</td>
                    <td class="text-danger">3</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        
        <!-- Sections Report -->
        <div class="col-12 col-lg-6">
          <div class="card rounded-4 h-100">
            <div class="card-body">
              <h5 class="mb-3">Sections Report</h5>
              <div id="chart10"></div>
            </div>
          </div>
        </div>
        <!-- Store Performance Chart -->
        <div class="col-12 col-lg-3">
          <div class="card rounded-4 h-100 performance-chart">
            <div class="card-body">
              <h5 class="mb-2">Performance Chart</h5>
              <div class="performance-chart-container">
                <div class="store-performance-legend">
                  <div class="legend-item">
                    <div class="legend-color" style="background: #00C49F;"></div>
                    <span>1st Qtr</span>
                  </div>
                  <div class="legend-item">
                    <div class="legend-color" style="background: #0088FE;"></div>
                    <span>2nd Qtr</span>
                  </div>
                  <div class="legend-item">
                    <div class="legend-color" style="background: #FFBB28;"></div>
                    <span>3rd Qtr</span>
                  </div>
                  <div class="legend-item">
                    <div class="legend-color" style="background: #FF8042;"></div>
                    <span>4th Qtr</span>
                  </div>
                </div>
                <div id="chart11"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>
  <!--end main wrapper-->

  <!--start overlay-->
  <div class="overlay btn-toggle"></div>

  <!--end overlay-->
  <!--bootstrap js-->
  <script src="../../public/js/bootstrap.bundle.min.js"></script>
  <!--plugins-->
  <script src="../../public/js/jquery.min.js"></script>
  <!--plugins-->
  <script src="../../public/plugins/perfect-scrollbar/js/perfect-scrollbar.js"></script>
  <script src="../../public/plugins/metismenu/metisMenu.min.js"></script>
  <script src="../../public/plugins/apexchart/apexcharts.min.js"></script>
  <script src="../../public/plugins/simplebar/js/simplebar.min.js"></script>
  <script src="../../public/plugins/peity/jquery.peity.min.js"></script>
  <script>
    $(".data-attributes span").peity("donut")
  </script>

  <script src="../../public/js/dashboard2.js"></script>
  <script src="../../public/js/data-widgets.js"></script>
  <script src="../../public/js/main.js"></script>

  <script>
    var ytdOptions = {
      series: [73],
      chart: {
        height: 200,
        type: 'radialBar',
      },
      plotOptions: {
        radialBar: {
          hollow: {
            size: '70%',
          },
          track: {
            background: 'rgba(255, 255, 255, 0.1)',
          },
          dataLabels: {
            name: {
              show: false
            },
            value: {
              fontSize: '1.6rem',
              fontWeight: 500,
              color: '#fff',
              formatter: function (val) {
                return val + '%';
              }
            }
          }
        }
      },
      colors: ['#36d6b7'],
      stroke: {
        lineCap: 'round'
      }
    };

    var ytdChart = new ApexCharts(document.querySelector("#ytdChart"), ytdOptions);
    ytdChart.render();
  </script>

  {{-- <script>
    // Trend chart configuration
    var options = {
      series: [{
        name: 'Score',
        data: [@json($TrendScores->pluck('acheivedScore'))]
      }],
      chart: {
        height: 350,
        type: 'line',
        foreColor: "#9ba7b2",
        toolbar: {
          show: false
        }
      },
      stroke: {
        width: 3,
        curve: 'smooth'
      },
      colors: ["#36d6b7"],
      grid: {
        borderColor: 'rgba(255, 255, 255, 0.1)',
      },
      xaxis: {
        categories: @json($TrendScores->pluck('name'))
      },
      yaxis: {
        title: {
          text: 'Score'
        }
      },
      tooltip: {
        theme: 'dark'
      }
    };

    var trendChart = new ApexCharts(document.querySelector("#chart14"), options);
    trendChart.render();
  </script> --}}

  <script>
    // Console logging all data passed from controller
    console.log('Processes:', @json($processes));
    console.log('Selected Process:', @json($selectedProcess));
    console.log('Waves:', @json($waves));
    console.log('Total Submitted Shop:', @json($totalSubmittedShop));
    console.log('Total Shop:', @json($totalShop));
    console.log('Missing Shop:', @json($MissingShop));
    console.log('Strengths:', @json($strengths));
    console.log('Weaknesses:', @json($weaknesses));
    console.log('Trend Scores:', @json($TrendScores));
    console.log('Region Scores:', @json($regionScores));
    console.log('Section Score:', @json($SectionScore));
    console.log('Overall Score:', @json($OverallScore));
    console.log('Recurring Questions:', @json($recurringQuestions));
  </script>
</body>
</html>