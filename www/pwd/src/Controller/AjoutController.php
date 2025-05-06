<?php

namespace App\Controller;

use App\Entity\BlogPost;
use App\Entity\Photo;
use App\Entity\User;
use App\Form\AjoutType;
use App\Form\PhotoType;
use App\Form\SearchFormType;
use App\Form\UpdateUserType;
use App\Repository\BlogPostRepository;
use App\Repository\CategoryRepository;
use App\Repository\UserRepository;
use App\Service\PhotoFormHandler;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


class AjoutController extends AbstractController
{
    private $entityManager;


    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;

    }

    #[Route('/utili/ajout/new', name: 'ajout_create')]
    public function createAjout(EntityManagerInterface $em, \Symfony\Component\HttpFoundation\Request $request, PhotoFormHandler $photoFormHandler)
    {
        $ajout = new BlogPost();
        $ajout->setUser($this->getUser());
        $form =$this->createForm(AjoutType::class,$ajout);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $photoFormHandler->uploadFilesFromForm($form->get('photo'));
            $em->persist($ajout);
            $em->flush();
            return $this->redirectToRoute('ajout_create', ["id" => $ajout->getId()]);
        }

        return $this->render('pages/admin/ajout.html.twig',['myAjoutForm' => $form->createView()]);
    }

    #[Route('/utili/ajout/{id}', name: 'ajout_edit')]
    public function editAjout(int $id, BlogPostRepository $blogPostRepository, EntityManagerInterface $em, \Symfony\Component\HttpFoundation\Request $request)
    {
        $ajout = $blogPostRepository->find($id);

        if ($ajout == null) {
            return $this->redirectToRoute('ajout_create');
        }

        if (!$this->isGranted('ROLE_ADMIN', $this->getUser()) && $ajout->getUser() !== $this->getUser()) {
            throw new AccessDeniedException("Not your adverst");
        }

        $form = $this->createForm(AjoutType::class, $ajout);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Check if a new photo has been uploaded
            if ($form->get('photo')->getData() !== null) {
                $newPhoto = $form->get('photo')->getData();
                $ajout->getPhoto()->setPath($newPhoto->getPath());
            }

            $em->persist($ajout);
            $em->flush();

            return $this->redirectToRoute('app_home', ["id" => $ajout->getId()]);
        }

        return $this->render('pages/admin/ajout.html.twig', ['myAjoutForm' => $form->createView()]);
    }


    #[Route('/utili/photo', name: 'createPhoto')]
    public function createPhotoForDemo(EntityManagerInterface $em,\Symfony\Component\HttpFoundation\Request $request, PhotoFormHandler $photoFormHandler){

        $photo = new Photo();
        $form = $this->createForm(PhotoType::class, $photo);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $photoFormHandler->uploadFilesFromForm($form);
            $em->persist($photo);
            $em->flush();
        }
        return $this->render('pages/admin/photo.html.twig', ['photoForm' => $form->createView()]);
    }

    #[Route('/utili/blogpost/delete/{id}', name: 'delete_blog_post')]
    public function deleteBlogPost($id, EntityManagerInterface $em, BlogPostRepository $blogPostRepository): \Symfony\Component\HttpFoundation\Response
    {
        $blogPost = $blogPostRepository->find($id);

        if (!$blogPost) {
            throw $this->createNotFoundException('L\'article avec l\'ID ' . $id . ' n\'existe pas.');
        }

        $em->remove($blogPost);
        $em->flush();

        // Redirection vers une page appropriée après la suppression
        return $this->redirectToRoute('app_home');
    }


    /*----------------------------------------*/

    #[Route('/utili/liste', name: 'list_users')]
    public function listUsers(UserRepository $userRepository, Request $request): Response
    {
        $search = $request->query->get('search');

        // Si aucune recherche n'est effectuée, récupérer tous les utilisateurs
        if (!$search) {
            $users = $userRepository->findAll();
        } else {
            // Sinon, rechercher les utilisateurs par email
            $users = $userRepository->findByEmail($search);
        }

        return $this->render('pages/admin/crudUser.html.twig', ['users' => $users, 'search' => $search]);
    }

    #[Route('/utili/modifier-role/{id}', name: 'modify_user_role')]
    public function modifyUserRole(
        Request $request,
        EntityManagerInterface $entityManager,
        TokenStorageInterface $tokenStorage,
        User $user
    ): Response {
        $newRole = $request->request->get('new_role');

        if ($newRole === 'ROLE_NOT') {
            // Cas spécial pour ROLE_NOT, videz le champ des rôles
            $user->setRoles(['ROLE_NOT']);
        } else {
            // Ajoutez ou mettez à jour le rôle
            $userRoles = [];

            // Ajoutez tous les rôles actuels, sauf ROLE_NOT
//            foreach ($user->getRoles() as $role) {
//                if ($role !== 'ROLE_NOT') {
//                    $userRoles[] = $role;
//                }
//            }

            // Ajoutez le nouveau rôle si ce n'est pas ROLE_NOT
            if ($newRole === 'ROLE_USER' || $newRole === 'ROLE_ADMIN') {
                $userRoles[] = $newRole;
            }

            // Assurez-vous que l'utilisateur a au moins un rôle
//            if (empty($userRoles)) {
//                // Par exemple, attribuez le rôle 'ROLE_USER' par défaut
//                $userRoles[] = 'ROLE_USER';
//            }

            $user->setRoles(array_unique($userRoles));
        }

        // Enregistrez les modifications en base de données
        $entityManager->flush();

        // Videz le contexte de sécurité pour forcer le rechargement des rôles
        $tokenStorage->setToken(null);

        // Redirigez vers la liste des utilisateurs
        return $this->redirectToRoute('list_users');
    }

    #[Route('/utili/delete/{id}', name: 'delete_user')]
    public function deleteUser($id, EntityManagerInterface $em, UserRepository $userRepository): Response
    {
        $userRepository->deleteUserWithComments($id);
        $user = $userRepository->find($id);

        if (!$user) {
            return $this->redirectToRoute('list_users');
        }

        $em->remove($user);
        $em->flush();

        // Redirection vers une page appropriée après la suppression
        return $this->redirectToRoute('list_users');
    }
    #[Route('/utili/edit/{id}', name: 'edit_user')]
    public function editUser(Request $request, EntityManagerInterface $entityManager, User $user,UserPasswordHasherInterface $userPasswordHasher): Response
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
            $entityManager->flush();

            // Redirection vers une page appropriée après la modification
            return $this->redirectToRoute('list_users');
        }

        return $this->render('pages/admin/edit_user.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route('/utili/articles', name: 'article_list')]
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

        return $this->render('pages/admin/crudArticle.html.twig', [
            'posts' => $articles,
            'categories' => $categories,
        ]);
    }

    #[Route('/utili/blogpost/delete/crud/{id}', name: 'delete_blog_post_crud')]
    public function deleteBlogPostCrud($id, EntityManagerInterface $em, BlogPostRepository $blogPostRepository): \Symfony\Component\HttpFoundation\Response
    {
        $blogPost = $blogPostRepository->find($id);

        if (!$blogPost) {
            throw $this->createNotFoundException('L\'article avec l\'ID ' . $id . ' n\'existe pas.');
        }

        $em->remove($blogPost);
        $em->flush();

        // Redirection vers une page appropriée après la suppression
        return $this->redirectToRoute('article_list');
    }

    #[Route('/utili/ajout/crud/{id}', name: 'ajout_edit_crud')]
    public function editAjoutCrud(int $id, BlogPostRepository $blogPostRepository, EntityManagerInterface $em, \Symfony\Component\HttpFoundation\Request $request)
    {
        $ajout = $blogPostRepository->find($id);

        if ($ajout == null) {
            return $this->redirectToRoute('ajout_create');
        }

        if (!$this->isGranted('ROLE_ADMIN', $this->getUser()) && $ajout->getUser() !== $this->getUser()) {
            throw new AccessDeniedException("Not your adverst");
        }

        $form = $this->createForm(AjoutType::class, $ajout);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Check if a new photo has been uploaded
            if ($form->get('photo')->getData() !== null) {
                $newPhoto = $form->get('photo')->getData();
                $ajout->getPhoto()->setPath($newPhoto->getPath());
            }

            $em->persist($ajout);
            $em->flush();

            return $this->redirectToRoute('article_list', ["id" => $ajout->getId()]);
        }

        return $this->render('pages/admin/ajout.html.twig', ['myAjoutForm' => $form->createView()]);
    }

}