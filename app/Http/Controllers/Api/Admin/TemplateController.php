<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\GameTemplate;
use App\Models\PromptVersion;
use Illuminate\Support\Str;

class TemplateController extends Controller
{
    public function index()
    {
        $templates = GameTemplate::withCount('games')->latest()->get();
        return response()->json(['data' => $templates]);
    }

    public function store()
    {
        $data = request()->validate([
            'name'                 => ['required', 'string', 'max:100'],
            'code'                 => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/', 'unique:game_templates,code'],
            'type'                 => ['required', 'string', 'in:quiz,word,match,puzzle,memory,drag'],
            'renderer_component'   => ['required', 'string', 'max:100'],
            'output_schema'        => ['required', 'json'],
            'prompt_text'          => ['required', 'string', 'min:50'],
            'token_budget_base'    => ['nullable', 'integer', 'min:200', 'max:8000'],
            'token_budget_per_item'=> ['nullable', 'integer', 'min:10', 'max:500'],
        ]);

        $template = GameTemplate::create([
            'code'                 => $data['code'],
            'name'                 => $data['name'],
            'type'                 => $data['type'],
            'renderer_component'   => $data['renderer_component'],
            'input_schema'         => ['fields' => ['topic', 'students_count', 'language']],
            'output_schema'        => json_decode($data['output_schema'], true),
            'token_budget_base'    => $data['token_budget_base']    ?? 800,
            'token_budget_per_item'=> $data['token_budget_per_item'] ?? 60,
            'status'               => 'disabled',
        ]);

        PromptVersion::create([
            'template_id' => $template->id,
            'version'     => 'v1',
            'prompt_text' => $data['prompt_text'],
            'status'      => 'active',
        ]);

        $template->loadCount('games');

        return response()->json(['data' => $template], 201);
    }

    public function toggle(int $id)
    {
        $template = GameTemplate::findOrFail($id);
        $template->update([
            'status' => $template->status === 'enabled' ? 'disabled' : 'enabled',
        ]);

        return response()->json(['data' => $template]);
    }

    public function updateBudget(int $id)
    {
        $template = GameTemplate::findOrFail($id);
        $data = request()->validate([
            'token_budget_base'    => ['required', 'integer', 'min:200', 'max:8000'],
            'token_budget_per_item'=> ['required', 'integer', 'min:10', 'max:500'],
        ]);
        $template->update($data);
        return response()->json(['data' => $template]);
    }
}
