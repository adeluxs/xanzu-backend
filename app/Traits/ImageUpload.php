<?php

namespace App\Traits;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

trait ImageUpload
{
    public function imageUploadTrait($query, $old = null, $folder = 'global/images/', $newExt = false): string
    {
        $allowExt = ['jpeg', 'png', 'jpg', 'gif', 'svg', 'webp'];
        if ($newExt) {
            if (is_array($newExt)) {
                $allowExt = array_merge($allowExt, $newExt);
            } else {
                $allowExt[] = $newExt;
            }
        }

        abort_if(!$query || !$query->isValid(), 422, __('Invalid file'));

        $ext = strtolower((string) $query->getClientOriginalExtension());
        abort_if($query->getSize() > (config('app.demo') ? 1024 * 10 : 5100000), 422, __('Max file size:5MB'));
        abort_if(!in_array($ext, $allowExt, true), 422, __('Only allow : ' . implode(', ', $allowExt)));

        $imageFullName = Str::random(20) . '.' . $ext;
        $folder = '/' . ltrim((string) $folder, '/');

        if (!str_contains($folder, 'global/uploads')) {
            $folder = 'global/uploads' . $folder;
        }

        $folder = trim($folder, '/') . '/';
        $relativeUploadPath = 'assets/' . $folder;
        $absoluteUploadPath = base_path($relativeUploadPath);

        File::ensureDirectoryExists($absoluteUploadPath, 0755, true);

        // Store the replacement first. The old image is deleted only after the
        // new file has been moved successfully, preventing a failed upload from
        // leaving the user without an avatar.
        $movedFile = $query->move($absoluteUploadPath, $imageFullName);
        if (!$movedFile || !File::exists($movedFile->getPathname())) {
            throw new RuntimeException('Unable to store uploaded image.');
        }

        if ($old) {
            $this->delete($old);
        }

        return $folder . $imageFullName;
    }

    protected function delete($path)
    {
        if (!$path) {
            return;
        }

        $relativePath = ltrim((string) $path, '/');
        if (Str::startsWith($relativePath, 'assets/')) {
            $relativePath = Str::after($relativePath, 'assets/');
        }

        $absolutePath = base_path('assets/' . $relativePath);
        if (File::exists($absolutePath)) {
            File::delete($absolutePath);
        }
    }
}
