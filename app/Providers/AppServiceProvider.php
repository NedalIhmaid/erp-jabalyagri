<?php

namespace App\Providers;

use App\Rules\TemporaryUploadExists;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Runs before each field's own rules are chained, so `bail` stops max/mimetypes
        // from touching a temp file that has already been deleted.
        FileUpload::configureUsing(fn (FileUpload $upload) => $upload->rules(['bail', new TemporaryUploadExists]));
    }
}
