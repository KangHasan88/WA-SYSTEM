<?php

namespace App\Http\Controllers;

use App\Models\WaTemplate;
use Illuminate\Http\Request;

class WaTemplateController extends Controller
{
    public function index(Request $request)
    {
        $query = WaTemplate::query()->latest();

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $templates = $query->paginate(10)->withQueryString();
        $categories = WaTemplate::query()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view('wa-templates.index', compact('templates', 'categories'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedTemplate($request);
        $data['is_active'] = $request->boolean('is_active', true);

        WaTemplate::create($data);

        return back()->with('success', 'Template berhasil dibuat.');
    }

    public function update(Request $request, int $id)
    {
        $template = WaTemplate::findOrFail($id);
        $data = $this->validatedTemplate($request);
        $data['is_active'] = $request->boolean('is_active');

        $template->update($data);

        return back()->with('success', 'Template berhasil diperbarui.');
    }

    public function destroy(int $id)
    {
        WaTemplate::findOrFail($id)->delete();

        return back()->with('success', 'Template berhasil dihapus.');
    }

    public function toggle(int $id)
    {
        $template = WaTemplate::findOrFail($id);
        $template->update(['is_active' => ! $template->is_active]);

        return back()->with('success', 'Status template berhasil diperbarui.');
    }

    public function getTemplates()
    {
        return response()->json([
            'success' => true,
            'templates' => WaTemplate::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function show(int $id)
    {
        return response()->json([
            'success' => true,
            'template' => WaTemplate::findOrFail($id),
        ]);
    }

    public function preview(Request $request)
    {
        $message = (string) $request->input('message', '');
        $variables = (array) $request->input('variables', []);

        foreach ($variables as $key => $value) {
            $message = str_replace('{{' . $key . '}}', (string) $value, $message);
        }

        return response()->json([
            'success' => true,
            'preview' => $message,
        ]);
    }

    private function validatedTemplate(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'title' => ['nullable', 'string', 'max:100'],
            'message' => ['required', 'string'],
            'image_url' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:50'],
        ]);
    }
}
