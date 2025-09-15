<?php

namespace App\Controller;

use App\Entity\Task;
use App\Entity\User;
use App\Entity\Group;
use App\Form\TaskType;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Cookie;

class TaskController extends AbstractController
{

    #[Route('/user/history', name: 'task_history')]
    public function history(TaskRepository $taskRepo, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ACCESS_SITE');
        $user = $this->getUser();

        // 🔐 helper consent
        $consent = $request->cookies->get('cookie_consent') === '1';

        // 🔎 lire paramètres + fallback cookie
        $searchTitle    = $request->query->get('searchTitle', $request->cookies->get('h_title', ''));
        $searchDate     = $request->query->get('searchDate', $request->cookies->get('h_date', ''));
        $searchPriority = $request->query->get('searchPriority', $request->cookies->get('h_prio', ''));
        $searchGroup    = $request->query->get('searchGroup', $request->cookies->get('h_group', ''));

        $qb = $taskRepo->createQueryBuilder('t')
            ->where('t.completed = :completed')
            ->andWhere('t.user = :user')
            ->setParameter('completed', true)
            ->setParameter('user', $user);

        if ($searchTitle !== '') {
            $qb->andWhere('t.title LIKE :title')->setParameter('title', '%'.$searchTitle.'%');
        }
        if ($searchDate !== '') {
            $qb->andWhere('t.dueDate = :date')->setParameter('date', $searchDate);
        }
        if ($searchPriority !== '') {
            $qb->andWhere('t.priority = :priority')->setParameter('priority', $searchPriority);
        }
        if ($searchGroup !== '') {
            $qb->andWhere('t.group = :gid')->setParameter('gid', $searchGroup);
        }

        $qb->orderBy('t.dueDate', 'ASC')
            ->addOrderBy(
                'CASE t.priority WHEN :high THEN 1 WHEN :medium THEN 2 WHEN :low THEN 3 ELSE 4 END', 'ASC'
            )
            ->addOrderBy('t.title', 'ASC')
            ->setParameter('high', 'élevée')
            ->setParameter('medium', 'moyenne')
            ->setParameter('low', 'faible');

        $tasks = $qb->getQuery()->getResult();

        // Pour le select "Groupe"
        $groups = $em->getRepository(\App\Entity\Group::class)->findBy(['user' => $user], ['name' => 'ASC']);

        // 🎯 on renvoie les valeurs résolues au template
        $response = $this->render('task/history.html.twig', [
            'tasks'   => $tasks,
            'groups'  => $groups,
            'filters' => [
                'searchTitle'    => $searchTitle,
                'searchDate'     => $searchDate,
                'searchPriority' => $searchPriority,
                'searchGroup'    => $searchGroup,
            ],
        ]);

        // 🍪 écrire cookies (30 jours) si consentement
        if ($consent) {
            $in30d = time() + 60*60*24*30;
            $response->headers->setCookie(new \Symfony\Component\HttpFoundation\Cookie('h_title', $searchTitle, $in30d, '/', null, $request->isSecure(), false, false, 'lax'));
            $response->headers->setCookie(new \Symfony\Component\HttpFoundation\Cookie('h_date', $searchDate, $in30d, '/', null, $request->isSecure(), false, false, 'lax'));
            $response->headers->setCookie(new \Symfony\Component\HttpFoundation\Cookie('h_prio', $searchPriority, $in30d, '/', null, $request->isSecure(), false, false, 'lax'));
            $response->headers->setCookie(new \Symfony\Component\HttpFoundation\Cookie('h_group', $searchGroup, $in30d, '/', null, $request->isSecure(), false, false, 'lax'));
        }

        return $response;
    }

    #[Route('/admin', name: 'task_admin')]
    public function admin(Request $request, EntityManagerInterface $em): Response
    {
        $userRepository = $em->getRepository(User::class);

        $searchUsername = $request->query->get('searchUsername');
        $searchEmail = $request->query->get('searchEmail');
        $searchRole = $request->query->get('searchRole');

        $queryBuilder = $userRepository->createQueryBuilder('u');

        if ($searchUsername) {
            $queryBuilder->andWhere('u.username LIKE :username')
                ->setParameter('username', "%$searchUsername%");
        }

        if ($searchEmail) {
            $queryBuilder->andWhere('u.email LIKE :email')
                ->setParameter('email', "%$searchEmail%");
        }

        if ($searchRole) {
            switch ($searchRole) {
                case 'Utilisateur':
                    $queryBuilder->andWhere("u.roles NOT LIKE '%ROLE_ADMIN%'")
                        ->andWhere("u.roles NOT LIKE '%ROLE_SUPER_ADMIN%'")
                        ->andWhere("u.roles NOT LIKE '%ROLE_BANNED%'");
                    break;
                case 'Admin':
                    $queryBuilder->andWhere("u.roles LIKE '%ROLE_ADMIN%'")
                        ->andWhere("u.roles NOT LIKE '%ROLE_SUPER_ADMIN%'")
                        ->andWhere("u.roles NOT LIKE '%ROLE_BANNED%'");
                    break;
                case 'Super Admin':
                    $queryBuilder->andWhere("u.roles LIKE '%ROLE_SUPER_ADMIN%'")
                        ->andWhere("u.roles NOT LIKE '%ROLE_BANNED%'");
                    break;
                case 'Banni':
                    $queryBuilder->andWhere("u.roles LIKE '%ROLE_BANNED%'");
                    break;
            }
        }

        $users = $queryBuilder->getQuery()->getResult();

        return $this->render('task/admin.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/admin/user/{id}/tasks', name: 'admin_user_tasks')]
    public function userTasks(int $id, EntityManagerInterface $em): Response
    {
        $user = $em->getRepository(User::class)->find($id);

        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }

        // 🔁 On affiche les groupes de cet utilisateur (triés par nom)
        $groups = $em->getRepository(Group::class)->findBy(
            ['user' => $user],
            ['name' => 'ASC']
        );

        return $this->render('task/user_tasks.html.twig', [
            'user'   => $user,
            'groups' => $groups, // ⬅️ on envoie les groupes au template
        ]);
    }


    #[Route('/admin/user/{id}', name: 'admin_user_delete', methods: ['POST'])]
    public function deleteUser(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete-user-' . $user->getId(), $request->request->get('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
            $this->addFlash('success', 'Utilisateur supprimé avec succès.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('task_admin');
    }

    #[Route('/admin/user/{id}/promote', name: 'admin_user_promote', methods: ['POST'])]
    public function promoteUser(User $user, EntityManagerInterface $em, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('promote-user-' . $user->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $roles = $user->getRoles();
        if (!in_array('ROLE_ADMIN', $roles)) {
            $roles[] = 'ROLE_ADMIN';
            $user->setRoles($roles);
            $em->flush();
            $this->addFlash('success', 'Utilisateur promu admin.');
        }

        return $this->redirectToRoute('task_admin');
    }

    #[Route('/admin/user/{id}/demote', name: 'admin_user_demote', methods: ['POST'])]
    public function demoteUser(User $user, EntityManagerInterface $em, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('demote-user-' . $user->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $user->setRoles(array_filter($user->getRoles(), fn($role) => $role !== 'ROLE_ADMIN'));
        $em->flush();

        $this->addFlash('success', 'Utilisateur rétrogradé en simple utilisateur.');
        return $this->redirectToRoute('task_admin');
    }

    #[Route('/admin/user/{id}/ban', name: 'admin_user_ban', methods: ['POST'])]
    public function banUser(User $user, EntityManagerInterface $em, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('ban-user-' . $user->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $roles = $user->getRoles();
        if (!in_array('ROLE_BANNED', $roles)) {
            $roles[] = 'ROLE_BANNED';
            $user->setRoles(array_unique($roles));
            $em->flush();
            $this->addFlash('success', 'Utilisateur banni.');
        }

        return $this->redirectToRoute('task_admin');
    }

    #[Route('/admin/user/{id}/unban', name: 'admin_user_unban', methods: ['POST'])]
    public function unbanUser(User $user, EntityManagerInterface $em, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('unban-user-' . $user->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $user->setRoles(array_filter($user->getRoles(), fn($role) => $role !== 'ROLE_BANNED'));
        $em->flush();

        $this->addFlash('success', 'Utilisateur débanni.');
        return $this->redirectToRoute('task_admin');
    }

//    #[Route('/user/task/new', name: 'task_new')]
//    public function new(Request $request, EntityManagerInterface $em, Response $response): Response
//    {
//        $task = new Task();
//        $task->setCreatedAt(new \DateTime());
//
//        $form = $this->createForm(TaskType::class, $task);
//        $form->handleRequest($request);
//
//        if ($form->isSubmitted() && $form->isValid()) {
//            $em->persist($task);
//            $em->flush();
//
//            $response = new Response();
//            $response->headers->setCookie(new Cookie('task_success_message', 'La tâche a été ajoutée avec succès !', time() + 3600));
//
//            return $this->redirectToRoute('task_index', [], 302, $response);
//        }
//
//        return $this->render('task/form.html.twig', [
//            'form' => $form->createView(),
//            'editMode' => false,
//        ]);
//    }
    #[Route('/user/task/new', name: 'task_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ACCESS_SITE');

        $task = new Task();

        // Pré-sélection via query ?group=ID
        if ($gid = $request->query->get('group')) {
            $g = $em->getRepository(Group::class)->find($gid);
            if ($g && $g->getUser() === $this->getUser()) {
                $task->setGroup($g);
            }
        }

        $form = $this->createForm(TaskType::class, $task, [
            'user' => $this->getUser(),
            'hide_default_group' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $task->setUser($this->getUser());

            // ranger dans "Sans groupe" si vide
            if (!$task->getGroup()) {
                $default = $em->getRepository(Group::class)->findOneBy([
                    'user' => $this->getUser(),
                    'name' => 'Sans groupe',
                ]);
                if (!$default) {
                    $default = (new Group())
                        ->setUser($this->getUser())
                        ->setName('Sans groupe')
                        ->setCreatedAt(new \DateTimeImmutable());
                    $em->persist($default);
                }
                $task->setGroup($default);
            }

            $em->persist($task);
            $em->flush();

            $this->addFlash('success', 'La tâche a été ajoutée avec succès !');
            return $this->redirectToRoute('task_index');
        }

        return $this->render('task/form.html.twig', [
            'form' => $form->createView(),
            'editMode' => false,
        ]);
    }

// changer 09/09
//    #[Route('/user/task/new', name: 'task_new')]
//    public function new(Request $request, EntityManagerInterface $em): Response
//    {
//        $this->denyAccessUnlessGranted('ACCESS_SITE');
//
//        $task = new Task();
//        // $task->setCreatedAt(new \DateTime()); // déjà fait dans le __construct()
//
//        $form = $this->createForm(TaskType::class, $task, ['user' => $this->getUser()]);
//        $form->handleRequest($request);
//
//        if ($form->isSubmitted() && $form->isValid()) {
//            $task->setUser($this->getUser()); // 🔐 associer au user connecté
//            $em->persist($task);
//            $em->flush();
//
//            // ✅ Remplace le cookie par un flash
//            $this->addFlash('success', 'La tâche a été ajoutée avec succès !');
//
//            return $this->redirectToRoute('task_index');
//        }
//
//        return $this->render('task/form.html.twig', [
//            'form' => $form->createView(),
//            'editMode' => false,
//        ]);
//    }

//    #[Route('/user/task', name: 'task_index')]
//    public function index(TaskRepository $taskRepo, Request $request, EntityManagerInterface $em): Response
//    {
//        $task = new Task();
//        $form = $this->createForm(TaskType::class, $task);
//        $form->handleRequest($request);
//
//        if ($form->isSubmitted() && $form->isValid()) {
//            $task->setUser($this->getUser());
//            $em->persist($task);
//            $em->flush();
//            return $this->redirectToRoute('task_index');
//        }
//
//        $searchTitle = $request->query->get('searchTitle');
//        $searchDate = $request->query->get('searchDate');
//        $searchPriority = $request->query->get('searchPriority');
//
//        $queryBuilder = $taskRepo->createQueryBuilder('t')
//            ->where('t.completed = :completed')
//            ->andWhere('t.user = :user')
//            ->setParameter('completed', false)
//            ->setParameter('user', $this->getUser());
//
//        if ($searchTitle) {
//            $queryBuilder->andWhere('t.title LIKE :title')
//                ->setParameter('title', '%' . $searchTitle . '%');
//        }
//
//        if ($searchDate) {
//            $queryBuilder->andWhere('t.dueDate = :date')
//                ->setParameter('date', $searchDate);
//        }
//
//        if ($searchPriority) {
//            $queryBuilder->andWhere('t.priority = :priority')
//                ->setParameter('priority', $searchPriority);
//        }
//
//        $queryBuilder
//            ->orderBy('t.dueDate', 'ASC')
//            ->addOrderBy(
//                'CASE t.priority
//            WHEN :highPriority THEN 1
//            WHEN :mediumPriority THEN 2
//            WHEN :lowPriority THEN 3
//            ELSE 4 END', 'ASC'
//            )
//            ->addOrderBy('t.title', 'ASC')
//            ->setParameter('highPriority', 'élevée')
//            ->setParameter('mediumPriority', 'moyenne')
//            ->setParameter('lowPriority', 'faible');
//
//        $tasks = $queryBuilder->getQuery()->getResult();
//
//        return $this->render('task/task.html.twig', [
//            'form'  => $form->createView(),
//            'tasks' => $tasks,
//        ]);
//    }

// src/Controller/TaskController.php

    #[Route('/user/task', name: 'task_index')]
    public function index(Request $request, ManagerRegistry $doctrine): Response
    {
        $this->denyAccessUnlessGranted('ACCESS_SITE');
        $em = $doctrine->getManager();
        $user = $this->getUser();

        // ✅ garantir l’existence du groupe « Sans groupe » pour l’utilisateur
        $default = $em->getRepository(Group::class)->findOneBy([
            'user' => $user,
            'name' => 'Sans groupe',
        ]);
        if (!$default) {
            $default = (new Group())
                ->setUser($user)
                ->setName('Sans groupe')
                ->setCreatedAt(new \DateTimeImmutable());
            $em->persist($default);
            $em->flush();
        }

        // 🔎 filtres simples (par nom)
        $searchName = trim((string) $request->query->get('searchGroup', ''));

        $qb = $em->getRepository(Group::class)->createQueryBuilder('g')
            ->where('g.user = :u')
            ->setParameter('u', $user)
            ->orderBy('g.name', 'ASC');

        if ($searchName !== '') {
            $qb->andWhere('g.name LIKE :n')->setParameter('n', '%'.$searchName.'%');
        }

        $groups = $qb->getQuery()->getResult();

        return $this->render('task/list.html.twig', [
            'groups' => $groups,
            'searchName' => $searchName,
        ]);
    }
// changer le 09/09
//    #[Route('/user/task', name: 'task_index')]
//    public function index(TaskRepository $taskRepo, Request $request, EntityManagerInterface $em): Response
//    {
//        $this->denyAccessUnlessGranted('ACCESS_SITE');
//
//        $searchTitle = $request->query->get('searchTitle');
//        $searchDate = $request->query->get('searchDate');
//        $searchPriority = $request->query->get('searchPriority');
//        $searchGroup = $request->query->get('searchGroup'); // ✅
//
//        $qb = $taskRepo->createQueryBuilder('t')
//            ->where('t.completed = :completed')
//            ->andWhere('t.user = :user')
//            ->setParameter('completed', false)
//            ->setParameter('user', $this->getUser());
//
//        if ($searchTitle) {
//            $qb->andWhere('t.title LIKE :title')->setParameter('title', '%'.$searchTitle.'%');
//        }
//        if ($searchDate) {
//            $qb->andWhere('t.dueDate = :date')->setParameter('date', $searchDate);
//        }
//        if ($searchPriority) {
//            $qb->andWhere('t.priority = :priority')->setParameter('priority', $searchPriority);
//        }
//        if ($searchGroup) { // ✅ filtre par groupe
//            $qb->andWhere('t.group = :gid')->setParameter('gid', $searchGroup);
//        }
//
//        $qb->orderBy('t.dueDate', 'ASC')
//            ->addOrderBy("CASE WHEN t.priority = :high THEN 1 WHEN t.priority = :medium THEN 2 WHEN t.priority = :low THEN 3 ELSE 4 END", 'ASC')
//            ->addOrderBy('t.title', 'ASC')
//            ->setParameter('high', 'élevée')
//            ->setParameter('medium', 'moyenne')
//            ->setParameter('low', 'faible');
//
//        $tasks = $qb->getQuery()->getResult();
//
//        // Pour alimenter le select dans le template
//        $groups = $em->getRepository(Group::class)->findBy(
//            ['user' => $this->getUser()],
//            ['name' => 'ASC']
//        );
//
//        return $this->render('task/list.html.twig', [
//            'tasks'  => $tasks,
//            'groups' => $groups,
//        ]);
//    }


    #[Route('/user/tasks/{id}/complete', name: 'task_complete', methods: ['POST'])]
    public function complete(Task $task, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ACCESS_SITE');
        if ($task->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }
        if (!$this->isCsrfTokenValid('complete-task-' . $task->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $task->setCompleted(true); // sync -> status = 'terminee'
        $em->flush();

        return $request->headers->get('referer')
            ? $this->redirect($request->headers->get('referer'))
            : $this->redirectToRoute('task_index');
    }


    #[Route('/user/tasks/{id}/edit', name: 'task_edit')]
    public function edit(Request $request, Task $task, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ACCESS_SITE');
        if ($task->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(TaskType::class, $task, ['user' => $this->getUser()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('task_index');
        }

        return $this->render('task/edit.html.twig', [
            'form' => $form,
            'task' => $task,
        ]);
    }

    #[Route('/user/tasks/{id}/delete', name: 'task_delete', methods: ['POST'])]
    public function delete(Request $request, Task $task, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ACCESS_SITE');
        if ($task->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $referer     = $request->headers->get('referer');
        $groupId     = $task->getGroup()?->getId();
        $wasCompleted = $task->isCompleted();

        if ($this->isCsrfTokenValid('delete-task-' . $task->getId(), $request->request->get('_token'))) {
            $em->remove($task);
            $em->flush();
        }

        if ($referer) {
            return $this->redirect($referer);
        }

        if ($groupId) {
            return $this->redirectToRoute('group_tasks', ['id' => $groupId]);
        }

        if ($wasCompleted) {
            return $this->redirectToRoute('task_history');
        }

        return $this->redirectToRoute('task_index');
    }

    #[Route('/user/history/task/{id}/delete', name: 'task_history_delete', methods: ['POST'])]
    public function deleteFromHistory(Request $request, Task $task, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ACCESS_SITE');
        if ($task->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }
        if ($this->isCsrfTokenValid('delete-task-' . $task->getId(), $request->request->get('_token'))) {
            $em->remove($task);
            $em->flush();
        }

        return $this->redirectToRoute('task_history');
    }

    #[Route('/user/group/{id}/tasks', name: 'group_tasks')]
    public function groupTasks(
        int $id,
        EntityManagerInterface $em,
        TaskRepository $taskRepo,
        Request $request
    ): Response {
        $this->denyAccessUnlessGranted('ACCESS_SITE');
        $user  = $this->getUser();
        $group = $em->getRepository(\App\Entity\Group::class)->find($id);
        $consent = $request->cookies->get('cookie_consent') === '1';
        $prefix = 'gt_'.$group->getId().'_';

        $searchTitle    = $request->query->get('searchTitle',    $request->cookies->get($prefix.'title', ''));
        $searchDate     = $request->query->get('searchDate',     $request->cookies->get($prefix.'date', ''));
        $searchPriority = $request->query->get('searchPriority', $request->cookies->get($prefix.'prio', ''));
        $filterStatus   = $request->query->get('status',         $request->cookies->get($prefix.'status', ''));


        if (!$group || $group->getUser() !== $user) {
            throw $this->createNotFoundException('Groupe introuvable.');
        }

        $searchTitle    = $request->query->get('searchTitle');
        $searchDate     = $request->query->get('searchDate');
        $searchPriority = $request->query->get('searchPriority');
        $filterStatus   = $request->query->get('status'); // 'en_cours' | 'non_commence' (optionnel)

        $qb = $taskRepo->createQueryBuilder('t')
            ->andWhere('t.user = :user')
            ->andWhere('t.group = :grp')
            ->andWhere('t.completed = :c') // ici on n’affiche que les non-terminées
            ->setParameter('user', $user)
            ->setParameter('grp', $group)
            ->setParameter('c', false);

        if ($searchTitle) {
            $qb->andWhere('t.title LIKE :title')->setParameter('title', "%$searchTitle%");
        }
        if ($searchDate) {
            $qb->andWhere('t.dueDate = :date')->setParameter('date', $searchDate);
        }
        if ($searchPriority) {
            $qb->andWhere('t.priority = :p')->setParameter('p', $searchPriority);
        }
        if (\in_array($filterStatus, ['en_cours', 'non_commence'], true)) {
            $qb->andWhere('t.status = :s')->setParameter('s', $filterStatus);
        }

        $qb->orderBy('t.dueDate', 'ASC')
            ->addOrderBy("CASE WHEN t.priority = 'élevée' THEN 1 WHEN t.priority = 'moyenne' THEN 2 WHEN t.priority = 'faible' THEN 3 ELSE 4 END", 'ASC')
            ->addOrderBy('t.title', 'ASC');

        $tasks = $qb->getQuery()->getResult();

        $response = $this->render('task/group_tasks.html.twig', [
            'group'   => $group,
            'tasks'   => $tasks,
            'filters' => [
                'searchTitle'    => $searchTitle,
                'searchDate'     => $searchDate,
                'searchPriority' => $searchPriority,
                'status'         => $filterStatus,
            ],
        ]);

        if ($consent) {
            $in30d = time() + 60*60*24*30;
            $response->headers->setCookie(new Cookie($prefix.'title',  $searchTitle,    $in30d, '/', null, $request->isSecure(), false, false, 'lax'));
            $response->headers->setCookie(new Cookie($prefix.'date',   $searchDate,     $in30d, '/', null, $request->isSecure(), false, false, 'lax'));
            $response->headers->setCookie(new Cookie($prefix.'prio',   $searchPriority, $in30d, '/', null, $request->isSecure(), false, false, 'lax'));
            $response->headers->setCookie(new Cookie($prefix.'status', $filterStatus,   $in30d, '/', null, $request->isSecure(), false, false, 'lax'));
        }

        return $response;

    }

    #[Route('/user/tasks/{id}/toggle', name: 'task_toggle', methods: ['POST'])]
    public function toggle(Task $task, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ACCESS_SITE');
        if ($task->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }
        if (!$this->isCsrfTokenValid('toggle-task-' . $task->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        if ($task->getStatus() === 'en_cours') {
            $task->setStatus('terminee');
        } elseif ($task->getStatus() === 'terminee') {
            $task->setStatus('en_cours');
        } else {
            $task->setStatus('en_cours');
        }

        $em->flush();

        return $this->redirectToRoute('group_tasks', ['id' => $task->getGroup()->getId()]);
    }

    #[Route('/user/tasks/{id}/start', name: 'task_start', methods: ['POST'])]
    public function start(Task $task, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ACCESS_SITE');
        if ($task->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }
        if (!$this->isCsrfTokenValid('start-task-' . $task->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        if ($task->getStatus() === 'non_commence' || !$task->getStatus()) {
            $task->setStatus('en_cours'); // completed = false
            $em->flush();
        }

        return $this->redirectToRoute('group_tasks', ['id' => $task->getGroup()->getId()]);
    }

}