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

    public function store(Request $request){
        $uid = $request->uid;
        $school = $request->school;
        $grade = $request->grade;
        $gender = $request->gender;

        
    }
}
