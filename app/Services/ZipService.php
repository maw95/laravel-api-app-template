<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ZipException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ZipService
{
    const FAILED_TO_CREATE_ZIP = 'Failed to create ZIP archive';

    const EMPTY_FILES_LIST = 'No files provided for ZIP creation';

    const FILE_DOES_NOT_EXIST = 'File does not exist';

    const INVALID_OUTPUT_PATH = 'Invalid output path: directory does not exist';

    /**
     * @param  string[]  $files
     *
     * @throws ZipException
     */
    public function createZip(array $files, string $outputPath): void
    {
        if (empty($files)) {
            Log::warning(self::EMPTY_FILES_LIST);
            throw new ZipException(self::EMPTY_FILES_LIST);
        }

        foreach ($files as $file) {
            if (! Storage::exists($file)) {
                throw new ZipException(sprintf('%s: %s', self::FILE_DOES_NOT_EXIST, $file));
            }
        }

        $outputDir = dirname($outputPath);
        if (! is_dir($outputDir) || ! is_writable($outputDir)) {
            throw new ZipException(sprintf('%s: %s', self::INVALID_OUTPUT_PATH, $outputDir));
        }

        $zip = new ZipArchive;

        if ($zip->open($outputPath, ZipArchive::CREATE) === true) {
            foreach ($files as $file) {
                $realPath = Storage::path($file);
                $zip->addFile($realPath, basename($realPath));
            }
            $zip->close();
        } else {
            throw new ZipException(sprintf('%s: %s', self::FAILED_TO_CREATE_ZIP, $zip->getStatusString()));
        }
    }
}
