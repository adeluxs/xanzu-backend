<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class rmi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rmi {--doc : Delete unused images from Documentation by scanning all HTML files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove unwanted images from upload assets or Documentation images when using --doc';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('doc')) {
            return $this->removeUnusedDocumentationImages();
        }

        return $this->removeUnusedUploadImages();
    }

    protected function removeUnusedUploadImages(): int
    {
        $sqlFileContent = file_get_contents(base_path('DB/' . basename(base_path()) . '.sql'));

        $allImages = File::allFiles(base_path('assets/global'));
        $deletedCount = 0;

        foreach ($allImages as $key => $file) {
            $fileName = basename($file);
            $mimeType = File::mimeType($file);

            if (!str_contains($sqlFileContent, $fileName) && str_contains($mimeType, 'image')) {
                File::delete(($file));
                $this->info("Deleted: $fileName");
                $deletedCount++;
            }
        }

        $this->info("Upload cleanup complete. Deleted {$deletedCount} image(s).");

        return self::SUCCESS;
    }

    protected function removeUnusedDocumentationImages(): int
    {
        $documentationPath = base_path('Documentation');

        if (!File::exists($documentationPath)) {
            $this->error('Documentation directory not found.');

            return self::FAILURE;
        }

        $imageExtensions = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'bmp', 'ico'];
        $htmlExtensions = ['html', 'htm'];

        $allFiles = File::allFiles($documentationPath);

        $imageFiles = [];
        $htmlFiles = [];

        foreach ($allFiles as $file) {
            $extension = strtolower($file->getExtension());

            if (in_array($extension, $imageExtensions, true)) {
                $imageFiles[] = $file;
            }

            if (in_array($extension, $htmlExtensions, true)) {
                $htmlFiles[] = $file;
            }
        }

        if (empty($htmlFiles)) {
            $this->error('No HTML files found in Documentation.');

            return self::FAILURE;
        }

        $this->info('Scanning Documentation HTML files for image usage...');
        foreach ($htmlFiles as $htmlFile) {
            $relativeHtmlPath = str_replace('\\', '/', ltrim(str_replace($documentationPath, '', $htmlFile->getPathname()), '\\/'));
            $this->line("HTML: {$relativeHtmlPath}");
        }

        $htmlContent = '';
        foreach ($htmlFiles as $htmlFile) {
            $htmlContent .= "\n" . File::get($htmlFile->getPathname());
        }

        $usedCount = 0;
        $unusedCount = 0;
        $deletedCount = 0;

        $this->newLine();
        $this->info('Documentation image usage report:');

        foreach ($imageFiles as $imageFile) {
            $relativeImagePath = str_replace('\\', '/', ltrim(str_replace($documentationPath, '', $imageFile->getPathname()), '\\/'));
            $fileName = $imageFile->getFilename();

            $encodedRelativePath = str_replace('%2F', '/', rawurlencode($relativeImagePath));
            $encodedFileName = rawurlencode($fileName);

            $needles = array_values(array_unique([
                $relativeImagePath,
                './' . $relativeImagePath,
                $fileName,
                $encodedRelativePath,
                $encodedFileName,
            ]));

            $isUsed = false;
            foreach ($needles as $needle) {
                if (stripos($htmlContent, $needle) !== false) {
                    $isUsed = true;
                    break;
                }
            }

            if ($isUsed) {
                $usedCount++;
                $this->line("USED   : {$relativeImagePath}");
                continue;
            }

            $unusedCount++;
            if (File::delete($imageFile->getPathname())) {
                $deletedCount++;
                $this->warn("UNUSED : {$relativeImagePath} (deleted)");
            } else {
                $this->error("UNUSED : {$relativeImagePath} (delete failed)");
            }
        }

        $this->newLine();
        $this->info("Summary: {$usedCount} used, {$unusedCount} unused, {$deletedCount} deleted.");

        return self::SUCCESS;
    }
}