<?php

namespace App\Http\Controllers;

use App\Models\JobsGe;
use Carbon\Carbon;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\HttpClient;

class JobsGeController extends Controller
{
    public function index()
    {

    return view('welcome');

    }
}
