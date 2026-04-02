<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\PromptVersion;
use App\Models\GameTemplate;

class PromptController extends Controller
{
    public function index()
    {
        $templates = GameTemplate::with(['promptVersions' => fn($q) => $q->orderByDesc('created_at')])
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'status']);

        return response()->json(['data' => $templates]);
    }

    public function show(int $id)
    {
        $prompt = PromptVersion::with('template:id,code,name')->findOrFail($id);
        return response()->json(['data' => $prompt]);
    }

    public function update(int $id)
    {
        $prompt = PromptVersion::findOrFail($id);

        $prompt->update([
            'prompt_text' => request()->validate([
                'prompt_text' => ['required', 'string', 'min:50'],
            ])['prompt_text'],
        ]);

        return response()->json(['data' => $prompt]);
    }

    public function store()
    {
        $data = request()->validate([
            'template_id' => ['required', 'integer', 'exists:game_templates,id'],
            'prompt_text' => ['required', 'string', 'min:50'],
        ]);

        // Auto-increment version number
        $lastVersion = PromptVersion::where('template_id', $data['template_id'])
            ->orderByDesc('created_at')
            ->value('version');

        $nextNum = 1;
        if ($lastVersion && preg_match('/(\d+)/', $lastVersion, $m)) {
            $nextNum = (int) $m[1] + 1;
        }

        $prompt = PromptVersion::create([
            'template_id' => $data['template_id'],
            'version'     => 'v' . $nextNum,
            'prompt_text' => $data['prompt_text'],
            'status'      => 'inactive',
        ]);

        return response()->json(['data' => $prompt], 201);
    }

    public function activate(int $id)
    {
        $prompt = PromptVersion::findOrFail($id);

        // Deactivate all for this template, then activate this one
        PromptVersion::where('template_id', $prompt->template_id)
            ->update(['status' => 'inactive']);

        $prompt->update(['status' => 'active']);

        return response()->json(['data' => $prompt]);
    }
}
