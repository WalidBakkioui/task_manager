<?php

namespace App\Controller;

use App\Entity\BlogPost;
use App\Entity\Comment;
use App\Entity\Photo;
use App\Form\CommentType;
use App\Form\PhotoType;
use App\Form\UpdateUserType;
use App\Repository\BlogPostRepository;
use App\Repository\CategoryRepository;
use App\Repository\CommentRepository;
use App\Repository\PhotoRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Reflection\DocBlock\Tags\Author;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;


class DefaultController extends AbstractController
{
    private $entityManager;


    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;

    }

    #[Route('/', name: 'app_home')]
    public function index(Request $request, BlogPostRepository $blogPostRepository, CategoryRepository $categoryRepository): Response
    {
        $categories = $categoryRepository->findAll();

        $categoryId = $request->query->get('category');
        $title = $request->query->get('title');
        $maxPeople = $request->query->get('max_people');
        $type = $request->query->get('type');
        $consumption = $request->query->get('consumption');
        $box = $request->query->get('box');
        $years = $request->query->get('years');

        $categoryFilter = ($categoryId) ? ['category' => $categoryId] : [];
        $titleFilter = ($title) ? ['title' => $title] : [];
        $maxPeopleFilter = ($maxPeople) ? ['maxPeople' => $maxPeople] : [];
        $typeFilter = ($type) ? ['type' => $type] : [];
        $consumptionFilter = ($consumption) ? ['consumption' => $consumption] : [];
        $boxFilter = ($box) ? ['box' => $box] : [];
        $yearsFilter = ($years) ? ['years' => $years] : [];

        $filters = array_merge(
            $categoryFilter,
            $titleFilter,
            $maxPeopleFilter,
            $typeFilter,
            $consumptionFilter,
            $boxFilter,
            $yearsFilter
        );

        $articles = $blogPostRepository->findBy($filters, ['category' => 'ASC']);

        dump($articles);

        return $this->render('pages/home.html.twig', [
            'posts' => $articles,
            'categories' => $categories,
        ]);
    }

    #[Route('/user/profil', name: 'profil')]
    public function showProfile(CommentRepository $commentRepository): Response
    {
        $user = $this->getUser();
        $comments = $commentRepository->findBy(['author' => $user]);

        $articles = [];
        foreach ($comments as $comment) {
            $articles[] = $comment->getBlogPost();
        }

        return $this->render('pages/profil.html.twig', [
            'user' => $user,
            'comments' => $comments,
            'articles' => $articles,
        ]);
    }


    #[Route('/user/profil/edit', name: 'profil_edit')]
    public function editProfile(Request $request, UserInterface $user, UserPasswordHasherInterface $userPasswordHasher): Response

    {
        $form = $this->createForm(UpdateUserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('password')->getData()
                )
            );

            $this->entityManager->flush();
            return $this->redirectToRoute('profil', ['id' => $user->getId()]);
        }

        return $this->render('pages/profilEdit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route('/user/category/{categoryName}', name: 'category')]
    public function category(string $categoryName,
                             CategoryRepository $categoryRepository,BlogPostRepository $blogPostRepository ): Response
    {
        $category = $categoryRepository->findOneBy(['name' => $categoryName]);
        $blogPost = $blogPostRepository->findBy(['category' => $category]);
        return $this->render('pages/category.html.twig', ['category' => $category, 'posts' => $blogPost]);
    }

    #[Route('/user/a/{id<\d+>}', name:'link')]
    public function viewBlog(int $id, BlogPostRepository $blogPostRepository, Request $request)
    {
        $blog = $blogPostRepository->find($id);

        $form = $this->createForm(CommentType::class, null, [
            'action' => $this->generateUrl('create_blogPost_comment', ['id' => $blog->getId()]),
        ]);

        if ($request->query->get('view_comment')) {
            // Render the catalogue page with the article and its comments
            return $this->render('pages/catalogue.html.twig', ['link' => $blog, 'commentForm' => $form->createView()]);
        }

        return $this->render('pages/catalogue.html.twig', ['link' => $blog, 'commentForm' => $form->createView()]);
    }

    #[Route('/user/a/{id<\d+>}/comments', name:'get_blogPost_comment')]
    public function getBlogPostComment(BlogPost $blogPost,SerializerInterface $serializer)
    {
        return new JsonResponse($serializer->serialize($blogPost->getComments(), 'json', [
            'circular_reference_handler' => function ($object) {
                return $object->getId();
            }]));
    }


    #[Route('/user/comment/{id<\d+>}', name:'create_blogPost_comment')]
    public function createBlogPostComment($id, Request $request,
                                          EntityManagerInterface $em,
                                          BlogPostRepository $blogPostRepository,
                                          UserRepository $userRepository,
                                          SerializerInterface $serializer,
                                          Security $security)
    {

        $post = $blogPostRepository->find($id);
        if ($post === null) {
            throw new NotFoundHttpException();
        }
        $user = $security->getUser();

        $comment = new Comment();
        $comment->setBlogPost($post);
        $comment->setAuthor($user);

        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($comment);
            $em->flush();
            return new JsonResponse( $serializer->serialize($comment, 'json', [
                'circular_reference_handler' => function($object) {
                return $object->getId();
            }]));
        }

    }


    #[Route('/contact', name :'app_contact')]
    public function showContactPage(): Response
    {
        return $this->render('pages/contact.html.twig'); // Assurez-vous de remplacer 'your_template_file' par le nom réel de votre fichier Twig.
    }

}
