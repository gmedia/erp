<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class UserGuideController extends Controller
{
    public function index(): JsonResponse
    {
        $guides = collect(File::glob(base_path('docs/user-guide-*.md')))
            ->map(function (string $path) {
                $filename = basename($path, '.md');
                $slug = Str::after($filename, 'user-guide-');
                $content = File::get($path);
                $title = $this->extractTitle($content);

                return [
                    'slug' => $slug,
                    'title' => $title,
                    'filename' => $filename,
                ];
            })
            ->sortBy('title')
            ->values()
            ->all();

        return response()->json(['data' => $guides]);
    }

    public function show(string $slug): JsonResponse
    {
        $path = base_path("docs/user-guide-{$slug}.md");

        if (! File::exists($path)) {
            return response()->json(['message' => 'Guide not found.'], 404);
        }

        $content = File::get($path);
        $title = $this->extractTitle($content);

        return response()->json([
            'data' => [
                'slug' => $slug,
                'title' => $title,
                'content' => $content,
            ],
        ]);
    }

    private function extractTitle(string $content): string
    {
        if (preg_match('/^#\s+(.+)$/m', $content, $matches)) {
            return trim($matches[1]);
        }

        return 'Untitled';
    }
}
