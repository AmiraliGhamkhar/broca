<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(): View
    {
        return view('admin.activity.index', [
            'logs' => AdminActivityLog::query()
                ->with('user')
                ->latest('id')
                ->limit(300)
                ->get(),
        ]);
    }
}
