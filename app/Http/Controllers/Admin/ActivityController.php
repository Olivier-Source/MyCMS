<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Contracts\View\View;

class ActivityController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.activity', [
            'logs' => ActivityLog::with('user')->latest('created_at')->latest('id')->paginate(40),
        ]);
    }
}
