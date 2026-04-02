<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;

class AuditLogController extends Controller
{
    public function index()
    {
        $logs = AuditLog::with('actor:id,name,email')
            ->latest()
            ->paginate(50);

        return response()->json($logs);
    }
}
