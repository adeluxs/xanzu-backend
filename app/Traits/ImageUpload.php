<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait ImageUpload
{
    public function imageUploadTrait($query, $old = null, $folder = 'global/images/', $newExt = false): string // Taking input image as parameter
    {

        $allowExt = ['jpeg', 'png', 'jpg', 'gif', 'svg', 'webp'];
        if ($newExt) {
            if (is_array($newExt)) {
                $allowExt = array_merge($allowExt, $newExt);
            } else {
                $allowExt[] = $newExt;
            }
        }

        $ext = strtolower($query->getClientOriginalExtension());

        // abort_if($query->getSize() > env('APP_DEMO') ? 1024 * 10 : 5100000, 403, __('Max file size:5MB '));
        abort_if($query->getSize() > (config('app.demo') ? 1024 * 10 : 5100000), 403, __('Max file size:5MB '));
        abort_if(!in_array($ext, $allowExt), 403, __('Only allow : ' . implode(', ', $allowExt)));
        abort_if(!$query->isValid(), 403, __('Invalid file'));

        if ($old != null) {
            $this->delete($old);
        }
        $imageFullName = Str::random(20) . '.' . $ext;

        $folder = '/' . ltrim($folder, '/');

        if (!str_contains($folder, 'global/uploads')) {
            $folder = 'global/uploads' . $folder;
        }

        $folder = rtrim($folder, '/') . '/';

        $uploadPath = "assets/{$folder}";
        $imageUrl = $uploadPath . $imageFullName;
        $success = $query->move($uploadPath, $imageFullName);

        return str_replace('assets/', '', $imageUrl); // Just return image
    }

    protected function delete($path)
    {
        if (file_exists('assets/' . $path)) {
            @unlink('assets/' . $path);
        }
    }
}
