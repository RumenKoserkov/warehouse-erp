<?php

declare(strict_types=1);

namespace App\Services;

use finfo;
use Throwable;

class ProductImageService
{
    private const MAX_FILE_SIZE =
        2097152;

    private const MAX_WIDTH = 6000;

    private const MAX_HEIGHT = 6000;

    private const MAX_PIXELS =
        25000000;

    private const ALLOWED_MIME_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public function upload(
        array $file
    ): array {
        if (
            !isset($file['error']) ||
            (int) $file['error'] ===
                UPLOAD_ERR_NO_FILE
        ) {
            return $this->success(null);
        }

        if (
            (int) $file['error'] !==
            UPLOAD_ERR_OK
        ) {
            return $this->failure(
                'Image upload failed.'
            );
        }

        if (
            !isset($file['tmp_name']) ||
            !is_string(
                $file['tmp_name']
            ) ||
            $file['tmp_name'] === '' ||
            !is_uploaded_file(
                $file['tmp_name']
            )
        ) {
            return $this->failure(
                'Invalid uploaded file.'
            );
        }

        $fileSize = 0;

        if (isset($file['size'])) {
            $fileSize =
                (int) $file['size'];
        }

        if (
            $fileSize <= 0 ||
            $fileSize >
                self::MAX_FILE_SIZE
        ) {
            return $this->failure(
                'Image size must be between 1 byte and 2MB.'
            );
        }

        if (!class_exists(finfo::class)) {
            return $this->failure(
                'Server file type validation is unavailable.'
            );
        }

        $fileInfo = new finfo(
            FILEINFO_MIME_TYPE
        );

        $mimeType = $fileInfo->file(
            $file['tmp_name']
        );

        if (
            !is_string($mimeType) ||
            !array_key_exists(
                $mimeType,
                self::ALLOWED_MIME_TYPES
            )
        ) {
            return $this->failure(
                'The uploaded file is not a supported image.'
            );
        }

        $imageInformation =
            @getimagesize(
                $file['tmp_name']
            );

        if (
            !is_array(
                $imageInformation
            ) ||
            !isset(
                $imageInformation[0],
                $imageInformation[1]
            )
        ) {
            return $this->failure(
                'The uploaded file is not a valid image.'
            );
        }

        $width =
            (int) $imageInformation[0];

        $height =
            (int) $imageInformation[1];

        if (
            $width <= 0 ||
            $height <= 0 ||
            $width > self::MAX_WIDTH ||
            $height > self::MAX_HEIGHT ||
            $width * $height >
                self::MAX_PIXELS
        ) {
            return $this->failure(
                'Image dimensions are too large.'
            );
        }

        $detectedImageType =
            $imageInformation['mime'] ??
            '';

        if (
            !is_string(
                $detectedImageType
            ) ||
            $detectedImageType !==
                $mimeType
        ) {
            return $this->failure(
                'Image file type validation failed.'
            );
        }

        $extension =
            self::ALLOWED_MIME_TYPES[
                $mimeType
            ];

        try {
            $fileName =
                'product_' .
                bin2hex(
                    random_bytes(16)
                ) .
                '.' .
                $extension;
        } catch (Throwable) {
            return $this->failure(
                'A secure filename could not be generated.'
            );
        }

        $uploadDirectory =
            __DIR__ .
            '/../../public/uploads/products/';

        if (!is_dir($uploadDirectory)) {
            $directoryCreated = mkdir(
                $uploadDirectory,
                0775,
                true
            );

            if (!$directoryCreated) {
                return $this->failure(
                    'The upload directory could not be created.'
                );
            }
        }

        if (
            !is_writable(
                $uploadDirectory
            )
        ) {
            return $this->failure(
                'The upload directory is not writable.'
            );
        }

        $destination =
            $uploadDirectory .
            $fileName;

        if (
            !move_uploaded_file(
                $file['tmp_name'],
                $destination
            )
        ) {
            return $this->failure(
                'Could not save uploaded image.'
            );
        }

        @chmod(
            $destination,
            0644
        );

        return $this->success(
            '/uploads/products/' .
            $fileName
        );
    }

    private function success(
        ?string $path
    ): array {
        return [
            'success' => true,
            'path' => $path,
            'error' => null,
        ];
    }

    private function failure(
        string $message
    ): array {
        return [
            'success' => false,
            'path' => null,
            'error' => $message,
        ];
    }
}