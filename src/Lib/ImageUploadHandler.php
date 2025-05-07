<?php
declare(strict_types=1);

namespace App\Lib;

use Cake\Core\InstanceConfigTrait;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\Palette\RGB;
use Imagine\Image\Point;
use InvalidArgumentException;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\Visibility;
use Override;

class ImageUploadHandler extends AbstractUploadHandler
{
    use InstanceConfigTrait;

    /**
     * Default configuration.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'basePath' => WWW_ROOT . 'media',
        'thumbs' => [],
        'quality' => 75,
        'format' => 'jpeg', // Options: 'jpeg', 'webp', 'avif'
        'watermark' => null,
    ];
    private ?Filesystem $_storage = null;
    private Imagine $imagine;

    /**
     * ImageUploadHandler constructor.
     *
     * @param array<string, mixed> $config Configuration options.
     * @throws \InvalidArgumentException If an unsupported image format is specified.
     */
    public function __construct(array $config = [])
    {
        $this->setConfig($config);

        $allowedFormats = ['jpeg', 'webp', 'avif'];
        $format = strtolower($this->_config['format']);
        if (!in_array($format, $allowedFormats, true)) {
            throw new InvalidArgumentException("Unsupported image format: $format");
        }

        $visibility = PortableVisibilityConverter::fromArray([
            'file' => ['public' => 0640, 'private' => 0600],
            'dir' => ['public' => 0750, 'private' => 0700],
                ], Visibility::PUBLIC);

        $adapter = new LocalFilesystemAdapter($this->_config['basePath'], $visibility);
        $this->_storage = new Filesystem($adapter);
    }

    /**
     * Handles image upload and thumbnail generation.
     *
     * @param array<string, mixed> $files Uploaded files.
     * @return void
     */
    #[Override]
    public function handle(array $files): void
    {
        $this->imagine = new Imagine();
        $format = strtolower($this->_config['format']);
//        $extension = $format === 'jpeg' ? 'jpg' : $format;

        foreach ($files as $file) {
            if (empty($file['tmp_name'])) {
                continue;
            }

            $image = $this->imagine->open($file['tmp_name']);

            foreach ($this->_config['thumbs'] as $alias => $size) {
                $thumbName = $file['id'] . '-' . $alias . '.' . $format;
                [$thumbWidth, $thumbHeight] = is_array($size) ? $size : [$size, $size];
                $this->createThumbnail($image, $file['path'], $thumbName, (int)$thumbWidth, (int)$thumbHeight);
            }
        }
    }

    /**
     * Removes uploaded files and thumbnails from storage.
     *
     * @param array<string, mixed> $files Files to remove.
     * @return void
     */
    #[Override]
    public function remove(array $files): void
    {
        foreach ($files as $file) {
            $pattern = '/' . preg_quote((string)$file->id, '/') . '-[A-Za-z]+\.(jpe?g|webp|avif)$/i';

            $foundFiles = $this->_storage->listContents($file->path)
                    ->filter(fn(StorageAttributes $attr) => $attr->isFile())
                    ->filter(fn(StorageAttributes $attr) => preg_match($pattern, basename($attr->path())))
                    ->toArray();

            foreach ($foundFiles as $foundFile) {
                $this->_storage->delete(DS . $foundFile->path());
            }
        }
    }

    /**
     * Creates a thumbnail image with optional watermark.
     *
     * @param \Imagine\Image\ImageInterface $image Original image instance.
     * @param string $path Path where the thumbnail should be saved.
     * @param string $name Thumbnail file name.
     * @param int $width Width of the thumbnail.
     * @param int $height Height of the thumbnail.
     * @return void
     */
    private function createThumbnail(ImageInterface $image, string $path, string $name, int $width, int $height): void
    {
        $box = new Box($width, $height);
        $thumb = $image->thumbnail($box);
        $thumbSize = $thumb->getSize();

        $background = $this->imagine->create($box, (new RGB())->color('#fff'));
        $background->paste($thumb, new Point((int)(($width - $thumbSize->getWidth()) / 2), (int)(($height - $thumbSize->getHeight()) / 2)));

        if (!empty($this->_config['watermark']['image'])) {
            $watermark = $this->createWatermark($width, $height);
            $position = $this->getWatermarkPosition($width, $height, $watermark->getSize()->getWidth(), $watermark->getSize()->getHeight());
            $background->paste($watermark, $position);
        }

        $format = strtolower($this->_config['format']);
        $options = match ($format) {
            'webp' => ['webp_quality' => $this->_config['quality']],
            'avif' => ['avif_quality' => $this->_config['quality']],
            default => ['jpeg_quality' => $this->_config['quality']],
        };

        $imageData = $background->get($format, $options);
        $this->_storage->write($path . DS . $name, $imageData);
    }

    /**
     * Creates a watermark image.
     *
     * @param int $width Width of the base image.
     * @param int $height Height of the base image.
     * @return \Imagine\Image\ImageInterface Watermark image.
     */
    private function createWatermark(int $width, int $height): ImageInterface
    {
        $scale = $this->_config['watermark']['scale'];
        $watermarkPath = WWW_ROOT . ltrim($this->_config['watermark']['image'], DS);

        return $this->imagine
                        ->open($watermarkPath)
                        ->thumbnail(new Box((int)($width * $scale), (int)($height * $scale)));
    }

    /**
     * Determines the position for the watermark.
     *
     * @param int $width Width of the base image.
     * @param int $height Height of the base image.
     * @param int $wmWidth Width of the watermark.
     * @param int $wmHeight Height of the watermark.
     * @return \Imagine\Image\Point Position of the watermark.
     */
    private function getWatermarkPosition(int $width, int $height, int $wmWidth, int $wmHeight): Point
    {
        $pos = (int)($this->_config['watermark']['position'] ?? 9);

        return match ($pos) {
            1 => new Point(0, 0), // top-left
            2 => new Point(($width - $wmWidth) / 2, 0), // top-center
            3 => new Point($width - $wmWidth, 0), // top-right
            4 => new Point(0, ($height - $wmHeight) / 2), // middle-left
            5 => new Point(($width - $wmWidth) / 2, ($height - $wmHeight) / 2), // middle-center
            6 => new Point($width - $wmWidth, ($height - $wmHeight) / 2), // middle-right
            7 => new Point(0, $height - $wmHeight), // bottom-left
            8 => new Point(($width - $wmWidth) / 2, $height - $wmHeight), // bottom-center
            default => new Point($width - $wmWidth, $height - $wmHeight), // bottom-right
        };
    }
}
