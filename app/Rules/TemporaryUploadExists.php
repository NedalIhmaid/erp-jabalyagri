<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use League\Flysystem\FilesystemException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Fails when a Livewire temporary upload no longer exists on disk (already stored
 * by a previous submit, or purged by Livewire's 24h cleanup), so later rules like
 * max/mimetypes don't throw UnableToRetrieveMetadata on the missing file.
 */
class TemporaryUploadExists implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof TemporaryUploadedFile) {
            return;
        }

        try {
            $exists = $value->exists();
        } catch (FilesystemException) {
            $exists = false;
        }

        if (! $exists) {
            $fail(__('general.upload_expired'));
        }
    }
}
