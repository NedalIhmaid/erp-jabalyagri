<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class ViewInventoryTest extends TestCase
{
    public function test_explicit_app_view_references_exist(): void
    {
        $views = collect(['app', 'routes'])
            ->flatMap(function (string $directory): array {
                return File::allFiles(base_path($directory));
            })
            ->flatMap(function ($file): array {
                $contents = $file->getContents();
                $views = [];

                preg_match_all('/protected string \$view\s*=\s*\'([^\']+)\'/', $contents, $propertyMatches);
                preg_match_all('/\bview\(\s*\'([^\']+)\'\s*(?:,|\))/', $contents, $functionMatches);

                foreach ([$propertyMatches[1] ?? [], $functionMatches[1] ?? []] as $matches) {
                    foreach ($matches as $view) {
                        if (! str_contains($view, '::')) {
                            $views[] = $view;
                        }
                    }
                }

                return $views;
            })
            ->unique()
            ->sort()
            ->values();

        $this->assertNotEmpty($views, 'No explicit app-owned views were discovered.');

        foreach ($views as $view) {
            $this->assertTrue(View::exists($view), "Missing app-owned view [{$view}].");
        }
    }
}
