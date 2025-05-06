<?php

namespace App\Twig;

use App\Entity\BlogPost;
use App\Entity\Category;
use App\Repository\CategoryRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class MyTwigExtension extends AbstractExtension
{

//    public function __construct(private \Symfony\Component\Asset\Packages $assetsManager)
//    {
//    }

//  Autre manière de l'écrire.
    private \Symfony\Component\Asset\Packages $assetsManager;
    private string $assetFileFolderName;
    private CategoryRepository $categoryRepository;
    public function __construct(\Symfony\Component\Asset\Packages $assetsManager, string $assetFileFolderName, CategoryRepository $categoryRepository)
    {
        $this->assetsManager= $assetsManager;
        $this->assetFileFolderName= $assetFileFolderName;
        $this->categoryRepository = $categoryRepository;
    }


    public function getFunctions(){
        return  [
            new TwigFunction('photoOrDefault', [$this, 'photoOrDefault']),
            new TwigFunction('getCategories', [$this, 'getCategories'])
        ];
    }

    public function photoOrDefault(BlogPost $post)
    {
        if ($post->getPhoto() !== null) {
            return $this->assetsManager->getUrl($this->assetFileFolderName.'/'.$post->getPhoto()->getPath());
        } else {
            return $this->assetsManager->getUrl('assets/images/2001-Acura-Integra-Type-R.jpg');
        }
    }

    public function getCategories(): array
    {
        return $this->categoryRepository->findAll();
    }
}