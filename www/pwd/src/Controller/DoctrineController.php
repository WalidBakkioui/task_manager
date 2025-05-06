<?php

namespace App\Controller;

use App\Entity\BlogPost;
use App\Entity\Category;
use App\Repository\BlogPostRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class DoctrineController extends AbstractController
{

    #[Route('/utili/doctrine/create-posts/{name}', name: 'app_doctrine_posts')]
    public function createPosts(EntityManagerInterface $entityManager, CategoryRepository $categoryRepository, $name, $path): Response
    {
        $category = $categoryRepository->findOneBy(['name' => $name]);
        $photo = $categoryRepository->findOneBy(['path' => $path]);

        if (!$category) {
            throw $this->createNotFoundException('La catégorie n\'a pas été trouvée');
        }

        if (!$photo) {
            throw $this->createNotFoundException('Image non trouve');
        }

        $blogPost1 = new BlogPost();
        $blogPost1->setTitle("Acura-Integra-Type-R");
        $blogPost1->setYears('2001');
        $blogPost1->setMaxPeople("4");
        $blogPost1->setType("44");
        $blogPost1->setConsumption("ferf");
        $blogPost1->setBox("ff");
        $blogPost1->setCategory($category);
        $blogPost1->setPhoto($photo);

        $entityManager->persist($blogPost1);
        $entityManager->flush();

        return $this->redirectToRoute('app_doctrine_categories');
    }


    #[Route('/utili/doctrine/create-category/{name}', name: 'app_doctrine')]
    public function createCategory(String $name, EntityManagerInterface $entityManager): Response
    {
        $category = new Category();
        $category->setName($name);

        $entityManager->persist($category);
        $entityManager->flush();

        return $this->render('doctrine/index.html.twig', [
            'controller_name' => 'DoctrineController',
        ]);
    }
    #[Route('/utili/doctrine/categories', name: 'app_doctrine_categories')]
    public function categories(CategoryRepository $categoryRepository): Response{
        $categories = $categoryRepository->findBy([], ['name' => 'DESC'] );

        return $this->render('doctrine/categories.html.twig',
            ['categoryList' => $categories]);
    }

    /*AFFICHE tous les modeles d'une categorie*/
    #[Route('/utili/doctrine/categories/{name}', name: 'app_doctrine_category')]
    public function category(string $name,CategoryRepository $categoryRepository,BlogPostRepository $blogPostRepository): Response{
        $category = $categoryRepository->findOneBy(['name' => $name] );

        if($category === null){
            throw new NotFoundHttpException();
        }

        $blogPost = $blogPostRepository->findByCategory($category);
        return $this->render('doctrine/category.html.twig',
            ['category' => $category, 'posts' => $blogPost]);
    }

}