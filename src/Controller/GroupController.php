<?php

namespace App\Controller;

use App\Entity\Group;
use App\Form\GroupType;
use App\Repository\GroupRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/user/groups')]
class GroupController extends AbstractController
{
    #[Route('', name: 'group_index')]
    public function index(GroupRepository $repo): Response
    {
        $groups = $repo->findBy(['user' => $this->getUser()], ['name' => 'ASC']);
        return $this->render('group/group.html.twig', compact('groups'));
    }

    #[Route('/new', name: 'group_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $group = new Group();
        $form = $this->createForm(GroupType::class, $group);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $group->setUser($this->getUser());
            $em->persist($group);
            $em->flush();
            $this->addFlash('success', 'Groupe créé.');
            return $this->redirectToRoute('task_index');
        }

        return $this->render('group/form_group.html.twig', [
            'form' => $form->createView(),
            'edit' => false,
        ]);
    }

    #[Route('/{id}/edit', name: 'group_edit')]
    public function edit(Group $group, Request $request, EntityManagerInterface $em): Response
    {
        if ($group->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(GroupType::class, $group);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Groupe mis à jour.');
            return $this->redirectToRoute('task_index');
        }
        if ($group->getName() === 'Sans groupe') {
        $this->addFlash('warning', 'Le groupe "Sans groupe" ne peut pas être modifié.');
        return $this->redirectToRoute('task_index');
        }



        return $this->render('group/form_group.html.twig', [
            'form' => $form->createView(),
            'edit' => true,
        ]);
    }

    #[Route('/{id}/delete', name: 'group_delete', methods: ['POST'])]
    public function delete(Group $group, Request $request, EntityManagerInterface $em): Response
    {
        if ($group->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }
        if (!$this->isCsrfTokenValid('del-group-' . $group->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('CSRF invalide.');
        }

        if ($group->getName() === 'Sans groupe') {
            $this->addFlash('warning', 'Le groupe "Sans groupe" est protégé et ne peut pas être supprimé.');
            return $this->redirectToRoute('group_index');
        }

        $em->remove($group);
        $em->flush();
        $this->addFlash('success', 'Groupe supprimé.');
        return $this->redirectToRoute('task_index');
    }
}