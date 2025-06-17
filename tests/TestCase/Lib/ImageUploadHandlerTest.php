<?php
declare(strict_types=1);

namespace App\Test\TestCase\Lib;

use App\Lib\ImageUploadHandler;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use Symfony\Component\Filesystem\Filesystem;

class ImageUploadHandlerTest extends TestCase
{
    private string $tempDir;

    /**
     * Set up a temporary directory for testing.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'image_upload_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
    }

    /**
     * Clean up the temporary directory after tests.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $fs = new Filesystem();
        if ($fs->exists($this->tempDir)) {
            $fs->remove($this->tempDir);
        }

        parent::tearDown();
    }

    /**
     * Tests the handle and remove functionality of the ImageUploadHandler.
     *
     * This test creates a temporary image file, processes it with the ImageUploadHandler to generate
     * thumbnail images in specified sizes and quality, and verifies that the expected files are created
     * with correct dimensions. It then removes the generated files and asserts that they no longer exist.
     *
     * @return void
     */
    public function testHandleAndRemove(): void
    {
        $tmpFile = $this->tempDir . '/testimg.jpg';
        $this->createTestImage($tmpFile);

        $file = [
            'id' => 1,
            'tmp_name' => $tmpFile,
            'path' => '', // root in temp dir
        ];

        $handler = new ImageUploadHandler([
            'basePath' => $this->tempDir,
            'format' => 'jpeg',
            'thumbs' => [
                'small' => [100, 100],
                'wide' => [200, 50],
            ],
            'quality' => 80,
        ]);

        $handler->handle([$file]);

        $expectedFiles = [
            $this->tempDir . '/1-small.jpeg',
            $this->tempDir . '/1-wide.jpeg',
        ];

        foreach ($expectedFiles as $filePath) {
            $this->assertFileExists($filePath, basename($filePath) . ' should be created');

            [$width, $height] = getimagesize($filePath);
            if (str_contains($filePath, 'small')) {
                $this->assertSame(100, $width);
                $this->assertSame(100, $height);
            }
            if (str_contains($filePath, 'wide')) {
                $this->assertSame(200, $width);
                $this->assertSame(50, $height);
            }
        }

        $handler->remove([$file]);

        foreach ($expectedFiles as $path) {
            $this->assertFileDoesNotExist($path, basename($path) . ' should be deleted');
        }
    }

    public function testHandleWithWatermark(): void
    {
        $watermarkPath = $this->tempDir . '/watermark.png';
        $this->createWatermarkImage($watermarkPath);

        $handler = new ImageUploadHandler([
            'basePath' => $this->tempDir,
            'format' => 'jpeg',
            'thumbs' => ['marked' => [120, 120]],
            'watermark' => [
                'image' => str_replace(WWW_ROOT, '', $watermarkPath),
                'position' => 5,
                'scale' => 0.25,
            ],
        ]);

        $imagePath = $this->tempDir . '/source.jpg';
        $this->createTestImage($imagePath);

        $file = [
            'id' => 1,
            'tmp_name' => $imagePath,
            'path' => '',
        ];

        $handler->handle([$file]);

        $expectedThumb = $this->tempDir . '/1-marked.jpeg';
        $this->assertFileExists($expectedThumb);
    }

    public function testHandleGeneratesWebpThumbnails(): void
    {
        if (!function_exists('imagewebp')) {
            $this->markTestSkipped('WebP not supported on this system.');
        }

        $handler = new ImageUploadHandler([
            'basePath' => $this->tempDir,
            'format' => 'webp',
            'thumbs' => ['web' => [100, 100]],
        ]);

        $imagePath = $this->tempDir . '/webpimg.jpg';
        $this->createTestImage($imagePath);

        $file = [
            'id' => 1,
            'tmp_name' => $imagePath,
            'path' => '',
        ];

        $handler->handle([$file]);

        $expectedThumb = $this->tempDir . '/1-web.webp';
        $this->assertFileExists($expectedThumb);
    }

    public function testHandleGeneratesAvifThumbnails(): void
    {
        if (!function_exists('imageavif')) {
            $this->markTestSkipped('AVIF not supported on this system.');
        }

        $handler = new ImageUploadHandler([
            'basePath' => $this->tempDir,
            'format' => 'avif',
            'thumbs' => ['av' => [100, 100]],
        ]);

        $imagePath = $this->tempDir . '/avifimg.jpg';
        $this->createTestImage($imagePath);

        $file = [
            'id' => 1,
            'tmp_name' => $imagePath,
            'path' => '',
        ];

        $handler->handle([$file]);

        $expectedThumb = $this->tempDir . '/1-av.avif';
        $this->assertFileExists($expectedThumb);
    }

    public function testInvalidFormatThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ImageUploadHandler([
            'basePath' => $this->tempDir,
            'format' => 'tiff',
        ]);
    }

    private function createTestImage(string $path, int $width = 400, int $height = 300): void
    {
        $image = imagecreatetruecolor($width, $height);

        // Gradient background (top to bottom: blue → cyan)
        for ($y = 0; $y < $height; $y++) {
            $r = 0;
            $g = (int)(255 * $y / $height);
            $b = 255;
            $color = imagecolorallocate($image, $r, $g, $b);
            imageline($image, 0, $y, $width, $y, $color);
        }

        // Red circle
        $circleColor = imagecolorallocate($image, 255, 60, 60);
        imagefilledellipse($image, $width / 4, $height / 2, 100, 100, $circleColor);

        // Green rectangle
        $rectColor = imagecolorallocate($image, 60, 255, 100);
        imagefilledrectangle($image, $width / 2, $height / 2 - 50, $width / 2 + 100, $height / 2 + 50, $rectColor);

        // Border
        $borderColor = imagecolorallocate($image, 0, 0, 0);
        imagerectangle($image, 0, 0, $width - 1, $height - 1, $borderColor);

        // Label using built-in bitmap font
        $textColor = imagecolorallocate($image, 0, 0, 0);
        imagestring($image, 5, 10, $height - 30, 'TEST IMAGE', $textColor);

        // Save
        imagejpeg($image, $path, 90);
        imagedestroy($image);
    }

    private function createWatermarkImage(string $path): void
    {
        $size = 100;
        $image = imagecreatetruecolor($size, $size);
        imagesavealpha($image, true);

        $trans = imagecolorallocatealpha($image, 255, 255, 255, 127);
        imagefill($image, 0, 0, $trans);

        $red = imagecolorallocate($image, 255, 0, 0);
        imageline($image, 0, 0, $size, $size, $red);
        imageline($image, $size, 0, 0, $size, $red);

        imagepng($image, $path);
        imagedestroy($image);
    }
}
