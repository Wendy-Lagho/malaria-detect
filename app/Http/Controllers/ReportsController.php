<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReportsController extends Controller
{
    public function index()
{
    return view('reports.index'); // Adjust this to the correct view you want to return
}

}
