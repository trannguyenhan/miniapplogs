<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogApplication;
use App\Models\Tag;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function index()
    {
        $tags = Tag::orderBy('name')->paginate(20);
        return view('admin.tags.index', compact('tags'));
    }

    public function create()
    {
        return view('admin.tags.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:tags,name',
        ]);

        $tag = Tag::create([
            'name' => trim($validated['name']),
        ]);

        return redirect()->route('admin.tags.index')
            ->with('success', __('app.tag_added', ['name' => $tag->name]));
    }

    public function edit(Tag $tag)
    {
        return view('admin.tags.edit', compact('tag'));
    }

    public function update(Request $request, Tag $tag)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:tags,name,' . $tag->id,
        ]);

        $oldName = $tag->name;
        $newName = trim($validated['name']);

        $tag->update(['name' => $newName]);

        if ($oldName !== $newName) {
            LogApplication::query()->select(['id', 'tags'])->chunkById(100, function ($apps) use ($oldName, $newName) {
                foreach ($apps as $app) {
                    $tags = $app->tags ?? [];
                    if (!in_array($oldName, $tags, true)) {
                        continue;
                    }

                    $updated = array_map(
                        static fn ($t) => $t === $oldName ? $newName : $t,
                        $tags
                    );
                    $updated = array_values(array_unique($updated));

                    $app->update(['tags' => empty($updated) ? null : $updated]);
                }
            });
        }

        return redirect()->route('admin.tags.index')
            ->with('success', __('app.tag_updated', ['name' => $tag->name]));
    }

    public function destroy(Tag $tag)
    {
        $name = $tag->name;

        LogApplication::query()->select(['id', 'tags'])->chunkById(100, function ($apps) use ($name) {
            foreach ($apps as $app) {
                $tags = $app->tags ?? [];
                if (!in_array($name, $tags, true)) {
                    continue;
                }

                $updated = array_values(array_filter($tags, static fn ($t) => $t !== $name));
                $app->update(['tags' => empty($updated) ? null : $updated]);
            }
        });

        $tag->delete();

        return redirect()->route('admin.tags.index')
            ->with('success', __('app.tag_deleted', ['name' => $name]));
    }
}
