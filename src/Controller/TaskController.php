<?php

namespace App\Controller;

use App\Entity\Task;
use App\Entity\User;
use App\Form\TaskType;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TaskController extends AbstractController
{

    #[Route('/user/history', name: 'task_history')]
    public function history(TaskRepository $taskRepo, Request $request): Response
    {
        $user = $this->getUser();

        $searchTitle = $request->query->get('searchTitle');
        $searchDate = $request->query->get('searchDate');
        $searchPriority = $request->query->get('searchPriority');

        $queryBuilder = $taskRepo->createQueryBuilder('t')
            ->where('t.completed = :completed')
            ->andWhere('t.user = :user')
            ->setParameter('completed', true)
            ->setParameter('user', $user);

        if ($searchTitle) {
            $queryBuilder->andWhere('t.title LIKE :title')
                ->setParameter('title', '%' . $searchTitle . '%');
        }

        if ($searchDate) {
            $queryBuilder->andWhere('t.dueDate = :date')
                ->setParameter('date', $searchDate);
        }

        if ($searchPriority) {
            $queryBuilder->andWhere('t.priority = :priority')
                ->setParameter('priority', $searchPriority);
        }

        $queryBuilder
            ->orderBy(
                'CASE t.priority
             WHEN :highPriority THEN 1
             WHEN :mediumPriority THEN 2
             WHEN :lowPriority THEN 3
             ELSE 4 END', 'ASC'
            )
            ->addOrderBy('t.title', 'ASC')
            ->setParameter('highPriority', 'élevée')
            ->setParameter('mediumPriority', 'moyenne')
            ->setParameter('lowPriority', 'faible');

        $tasks = $queryBuilder->getQuery()->getResult();

        return $this->render('task/history.html.twig', [
            'tasks' => $tasks,
        ]);
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
    public function userTasks(int $id, EntityManagerInterface $entityManager): Response
    {
        $user = $entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }

        $tasks = $user->getTasks();

        return $this->render('task/user_tasks.html.twig', [
            'user' => $user,
            'tasks' => $tasks,
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

    #[Route('/user/task/new', name: 'task_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $task = new Task();
        $task->setCreatedAt(new \DateTime());

        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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

    #[Route('/user/task', name: 'task_index')]
    public function index(TaskRepository $taskRepo, Request $request, EntityManagerInterface $em): Response
    {
        $task = new Task();
        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $task->setUser($this->getUser());
            $em->persist($task);
            $em->flush();
            return $this->redirectToRoute('task_index');
        }

        $searchTitle = $request->query->get('searchTitle');
        $searchDate = $request->query->get('searchDate');
        $searchPriority = $request->query->get('searchPriority');

        $queryBuilder = $taskRepo->createQueryBuilder('t')
            ->where('t.completed = :completed')
            ->andWhere('t.user = :user')
            ->setParameter('completed', false)
            ->setParameter('user', $this->getUser());

        if ($searchTitle) {
            $queryBuilder->andWhere('t.title LIKE :title')
                ->setParameter('title', '%' . $searchTitle . '%');
        }

        if ($searchDate) {
            $queryBuilder->andWhere('t.dueDate = :date')
                ->setParameter('date', $searchDate);
        }

        if ($searchPriority) {
            $queryBuilder->andWhere('t.priority = :priority')
                ->setParameter('priority', $searchPriority);
        }

        $queryBuilder
            ->orderBy(
                'CASE t.priority
                WHEN :highPriority THEN 1
                WHEN :mediumPriority THEN 2
                WHEN :lowPriority THEN 3
                ELSE 4 END', 'ASC'
            )
            ->addOrderBy('t.title', 'ASC')
            ->setParameter('highPriority', 'élevée')
            ->setParameter('mediumPriority', 'moyenne')
            ->setParameter('lowPriority', 'faible');

        $tasks = $queryBuilder->getQuery()->getResult();

        return $this->render('task/index.html.twig', [
            'form'  => $form->createView(),
            'tasks' => $tasks,
        ]);
    }

    #[Route('/user/tasks/{id}/complete', name: 'task_complete')]
    public function complete(Task $task, EntityManagerInterface $em): Response
    {
        $task->setCompleted(true);
        $em->flush();

        return $this->redirectToRoute('task_index');
    }

    #[Route('/user/tasks/{id}/edit', name: 'task_edit')]
    public function edit(Request $request, Task $task, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(TaskType::class, $task);
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
        if ($this->isCsrfTokenValid('delete-task-' . $task->getId(), $request->request->get('_token'))) {
            $em->remove($task);
            $em->flush();
        }

        return $this->redirectToRoute('task_index');
    }

    #[Route('/user/history/task/{id}/delete', name: 'task_history_delete', methods: ['POST'])]
    public function deleteFromHistory(Request $request, Task $task, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete-task-' . $task->getId(), $request->request->get('_token'))) {
            $em->remove($task);
            $em->flush();
        }

        return $this->redirectToRoute('task_history');
    }

}