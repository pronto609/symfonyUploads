<?php

namespace App\Service;

use Gedmo\Sluggable\Util\Urlizer;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use Psr\Log\LoggerInterface;
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
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var string
     */
    private $uploadedAssetsBaseUrl;

    public function __construct(
        FilesystemInterface $publicUploadFilesystem,
        RequestStackContext $requestStackContext,
        LoggerInterface $logger,
        string $uploadedAssetsBaseUrl
    ) {
        $this->requestStackContext = $requestStackContext;
        $this->publicUploadFilesystem = $publicUploadFilesystem;
        $this->logger = $logger;
        $this->uploadedAssetsBaseUrl = $uploadedAssetsBaseUrl;
    }

    public function uploadArticleImage(File $file, ?string $existingFilename = null): string
    {
        if ($file instanceof UploadedFile) {
            $originalFileName = $file->getClientOriginalName();
        } else {
            $originalFileName = $file->getFilename();
        }

        $newFileName = Urlizer::urlize(pathinfo($originalFileName, PATHINFO_FILENAME)) . '-'.\uniqid() . '.' . $file->guessExtension();

        $stream = fopen($file->getPathname(), 'r');
        $result = $this->publicUploadFilesystem->writeStream(
            self::ARTICLE_IMAGE . '/' . $newFileName,
            $stream
        );

        if (false === $result) {
            throw new \Exception(
                sprintf('Could not upload file "%s"', $newFileName)
            );
        }

        if (is_resource($stream)) {
            fclose($stream);
        }

        if ($existingFilename) {
            try {
                $result = $this->publicUploadFilesystem->delete(self::ARTICLE_IMAGE . '/' . $existingFilename);

                if (false === $result) {
                    throw new \Exception(
                        sprintf('Could not delete old file "%s"', $newFileName)
                    );
                }
            } catch (FileNotFoundException $exception) {
                $this->logger->alert(
                    sprintf('file name "%s" does not exists, but trying to delete', $existingFilename)
                );
            }

        }

        return $newFileName;
    }

    public function getPublicPath(string $path): string
    {
        return $this->requestStackContext
            ->getBasePath().$this->uploadedAssetsBaseUrl.'/' . $path;
    }
}