<?php

namespace App\Http\Controllers;

use illuminate\Http\Request;

class HomeController extends Controller
{
    function index()
    {
        return view('index');
    }
}
