<?php

namespace App\Normalisers;

class GalleryItemNormaliser
{
    /**
     * @param array $galleryItem
     * @return array
     */
    public function normalise(array $galleryItem): array
    {
        return [
            'file_id' => $galleryItem['file_id'],
        ];
    }

    /**
     * @param array $multipleGalleryItems
     * @return array
     */
    public function normaliseMultiple(array $multipleGalleryItems): array
    {
        return array_map(function (array $galleryItem): array {
            return $this->normalise($galleryItem);
        }, $multipleGalleryItems);
    }
}
