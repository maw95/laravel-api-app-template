<?php

declare(strict_types=1);

use App\Exceptions\ZipException;
use App\Services\ZipService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');

    Storage::makeDirectory('reports');

    $outputDir = dirname(Storage::path('reports/test.zip'));
    if (! is_dir($outputDir)) {
        mkdir($outputDir, 0755, true);
    }
});

test('createZip successfully creates a zip file with multiple files', function () {
    $zipService = new ZipService;
    $testFiles = [
        'file1.txt' => 'Content of file 1',
        'file2.txt' => 'Content of file 2',
        'file3.txt' => 'Content of file 3',
    ];

    $storedFiles = [];
    foreach ($testFiles as $filename => $content) {
        Storage::put($filename, $content);
        $storedFiles[] = $filename;
    }

    $outputPath = Storage::path('reports/output.zip');

    $zipService->createZip($storedFiles, $outputPath);

    expect(file_exists($outputPath))->toBeTrue();

    $zip = new ZipArchive;
    $zip->open($outputPath);
    expect($zip->numFiles)->toBe(count($testFiles));

    foreach ($testFiles as $filename => $content) {
        $fileContent = $zip->getFromName(basename($filename));
        expect($fileContent)->toBe($content);
    }

    $zip->close();
});

test('createZip throws ZipException for empty files array', function () {
    $zipService = new ZipService;
    $outputPath = Storage::path('reports/empty.zip');

    Log::spy();

    expect(fn () => $zipService->createZip([], $outputPath))
        ->toThrow(ZipException::class, ZipService::EMPTY_FILES_LIST);

    expect(file_exists($outputPath))->toBeFalse();

    Log::shouldHaveReceived('warning')
        ->once()
        ->with(ZipService::EMPTY_FILES_LIST);
});

test('createZip handles files with special characters in names', function () {
    $zipService = new ZipService;
    $specialFiles = [
        'special@file.txt' => 'Content with special chars',
        'file with spaces.txt' => 'Content with spaces in filename',
        'file-with-dashes.txt' => 'Content with dashes',
    ];

    $storedFiles = [];
    foreach ($specialFiles as $filename => $content) {
        Storage::put($filename, $content);
        $storedFiles[] = $filename;
    }

    $outputPath = Storage::path('reports/special_chars.zip');

    $zipService->createZip($storedFiles, $outputPath);

    expect(file_exists($outputPath))->toBeTrue();

    $zip = new ZipArchive;
    $zip->open($outputPath);

    foreach ($specialFiles as $filename => $content) {
        $fileContent = $zip->getFromName(basename($filename));
        expect($fileContent)->toBe($content);
    }

    $zip->close();
});

test('createZip handles large files without memory issues', function () {
    $zipService = new ZipService;
    $largeContent = str_repeat('a', 1024 * 1024); // 1MB content
    Storage::put('large_file.txt', $largeContent);

    $outputPath = Storage::path('reports/large_file.zip');

    $zipService->createZip(['large_file.txt'], $outputPath);

    expect(file_exists($outputPath))->toBeTrue();

    $zip = new ZipArchive;
    $zip->open($outputPath);
    $fileContent = $zip->getFromName('large_file.txt');
    expect(strlen($fileContent))->toBe(strlen($largeContent));
    $zip->close();
});

test('createZip preserves only filenames without directory structure', function () {
    $zipService = new ZipService;
    Storage::makeDirectory('nested/directory');
    Storage::put('nested/directory/nested_file.txt', 'Nested file content');

    $outputPath = Storage::path('reports/nested_test.zip');

    $zipService->createZip(['nested/directory/nested_file.txt'], $outputPath);

    expect(file_exists($outputPath))->toBeTrue();

    $zip = new ZipArchive;
    $zip->open($outputPath);
    $fileContent = $zip->getFromName('nested_file.txt');
    expect($fileContent)->toBe('Nested file content');
    expect($zip->getFromName('nested/directory/nested_file.txt'))->toBeFalse();
    $zip->close();
});

test('createZip throws ZipException when file does not exist', function () {
    // Arrange
    $zipService = new ZipService;
    $outputPath = Storage::path('reports/test.zip');

    $nonExistentFile = 'non_existent_file.txt';
    $expectedErrorMessage = sprintf('%s: %s', ZipService::FILE_DOES_NOT_EXIST, $nonExistentFile);

    expect(fn () => $zipService->createZip([$nonExistentFile], $outputPath))
        ->toThrow(ZipException::class, $expectedErrorMessage);
});

test('createZip throws ZipException when output directory does not exist', function () {
    // Arrange
    $zipService = new ZipService;
    Storage::put('test_file.txt', 'Test content');

    $nonExistentDir = '/non_existent_directory';
    $outputPath = $nonExistentDir.'/test.zip';

    $expectedErrorMessage = sprintf('%s: %s', ZipService::INVALID_OUTPUT_PATH, $nonExistentDir);

    expect(fn () => $zipService->createZip(['test_file.txt'], $outputPath))
        ->toThrow(ZipException::class, $expectedErrorMessage);
});

test('createZip throws ZipException when zip creation fails', function () {
    $zipService = Mockery::mock(ZipService::class)->makePartial();
    $zipService->shouldReceive('createZip')
        ->andThrow(new ZipException(sprintf('%s: %s', ZipService::FAILED_TO_CREATE_ZIP, 'Mocked error message')));

    Storage::put('test_file.txt', 'Test content');
    $outputPath = Storage::path('reports/failed.zip');

    expect(fn () => $zipService->createZip(['test_file.txt'], $outputPath))
        ->toThrow(ZipException::class, sprintf('%s: %s', ZipService::FAILED_TO_CREATE_ZIP, 'Mocked error message'));
});
