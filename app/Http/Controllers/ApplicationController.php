<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreApplicationRequest;
use App\Jobs\SendEmailJob;
use App\Mail\ApplicationCreated;
use App\Models\Application;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class ApplicationController extends Controller
{
//    public function index()
//    {
//        $applications = Application::all(); // Or however you retrieve your applications
//        return view('dashboard')->with('applications', $applications);
//    }
    public function store(StoreApplicationRequest $request)
    {
        // 1 kunda 1 marta ariza berish
        if ($this->checkDate()) {
          return  redirect()->back()->with('error','You can create only 1 application a day');
        }

        // file bilan ishlash
        if ($request->hasFile('file')){
            $name = $request->file('file')->getClientOriginalName();
            $path = $request->file('file')->storeAs('files', $name ,'public');
        }

        $application = Application::create([
            'user_id'=>auth()->user()->id,
            'subject' => $request->subject,
            'message' => $request->message,
            'file_url' => $path ?? null,
        ]);

        // eshttirish - tarqatish (email)
        dispatch(new SendEmailJob($application));
        return redirect()->back();
    }

    protected function checkDate()
    {
        // yangi user ning  applications bor yoki yo'qligini tekshirish
        if(auth()->user()->applications()->latest()->first() == null){
            return false;
        }
        // 1 kunda 1 marta ariza yuborish
        $last_application = auth()->user()->applications()->latest()->first();
        $last_app_date = Carbon::parse($last_application->created_at)->format('Y-m-d');
        $today = Carbon::now()->format('Y-m-d');

        if ($last_app_date == $today){
            return true;
        }
    }
}
