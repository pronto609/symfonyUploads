<?php

namespace App\Service;

use Gedmo\Sluggable\Util\Urlizer;
use League\Flysystem\FilesystemInterface;
use Symfony\Component\Asset\Context\RequestStackContext;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploaderHelper
{
    const ARTICLE_IMAGE = 'article_image';

    /**
     * @var RequestStackContext
     */
    private $requestStackContext;
    /**
     * @var FilesystemInterface
     */
    private $filesystem;

    public function __construct(
        FilesystemInterface $publicUploadFilesystem,
        RequestStackContext $requestStackContext
    ) {
        $this->requestStackContext = $requestStackContext;
        $this->publicUploadFilesystem = $publicUploadFilesystem;
    }

    public function uploadArticleImage(File $file): string
    {
        if ($file instanceof UploadedFile) {
            $originalFileName = $file->getClientOriginalName();
        } else {
            $originalFileName = $file->getFilename();
        }

        $newFileName = Urlizer::urlize(pathinfo($originalFileName, PATHINFO_FILENAME)) . '-'.\uniqid() . '.' . $file->guessExtension();

        $this->publicUploadFilesystem->write(
            self::ARTICLE_IMAGE . '/' . $newFileName,
            file_get_contents($file->getPathname())
        );

        return $newFileName;
    }

    public function getPublicPath(string $path): string
    {
        return $this->requestStackContext
            ->getBasePath().'/uploads/' . $path;
    }
}