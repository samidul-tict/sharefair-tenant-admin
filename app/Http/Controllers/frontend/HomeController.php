<?php

namespace App\Http\Controllers\frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(){
        return view('frontend.site.index');
    }
    public function about(){
        return view('frontend.site.about');
    }
    public function service(){
        return view('frontend.site.service');
    }
    public function contact(){
        return view('frontend.site.contact');
    }
    public function enquiry(){
        return view('frontend.site.enquiry');
    }
    public function privacyPolicy(){
        return view('frontend.site.privacy-policy');
    }
    public function termsOfUse(){
        return view('frontend.site.terms-of-use');
    }
    public function noticeOfPractices(){
        return view('frontend.site.notice-of-practices');
    }
}
