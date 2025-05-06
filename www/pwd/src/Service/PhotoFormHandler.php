<?php

namespace App\Service;

use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class PhotoFormHandler
{
    private $blogPostImagesPath;

    /**
     * AdvertPhotoUploader constructor.
     * @param $advertImagesPath
     */
    public function __construct($blogPostImagesPath)
    {
        $this->blogPostImagesPath = $blogPostImagesPath;
    }

    public function uploadFilesFromForm(Form $photoForm) {
        /**
         * @var UploadedFile $imageFile
         */
        $imageFile = $photoForm->get('image')->getData();
        if ($imageFile) {
            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            // this is needed to safely include the file name as part of the URL
            $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

            // Move the file to the directory where brochures are stored
            try {
                $imageFile->move(
                    $this->blogPostImagesPath,
                    $newFilename
                );
            } catch (FileException $e) {
                // ... handle exception if something happens during file upload
            }
            $photoForm->getData()->setPath($newFilename);
        }

    }

}