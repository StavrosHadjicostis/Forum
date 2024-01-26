<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Semester;
use App\Models\Notifications;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    // public function __construct()
    // {
    //     $this->middleware('auth');
    // }
    

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    // public function index()
    // {
    //     return view('dashboard');
    // }

    public function settings(){
        return view('settings');
    }

    public function upload(){
        return view('upload');
    }

    public function vathmoi(){

        $myNotifications = Notifications::where('toUser', '=', Session::get('loginId'))->orderBy('id', 'desc')->get();
        $myGrades = DB::table('Tests')->where('toUser', '=', Session::get('loginId'))->orderBy('id', 'desc')->get();

        return view('vathmoiDiagonismaton',['myGrades' => $myGrades]);
    }
}
