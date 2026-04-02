<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GameTemplate;

class TemplateController extends Controller
{
    public function index()
    {
        $templates = GameTemplate::where('status', 'enabled')
            ->get(['id', 'code', 'name', 'type', 'input_schema', 'renderer_component']);

        return response()->json(['data' => $templates]);
    }
}
