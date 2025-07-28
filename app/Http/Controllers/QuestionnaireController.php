<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class QuestionnaireController extends Controller
{
    //
    public function index($uid){
        return Inertia::render('Questionnaire/Index', ['uid' => $uid]);
    }
}
