<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class BannedUserListener
{
    private Security $security;
    private RouterInterface $router;
    private TokenStorageInterface $tokenStorage;

    public function __construct(Security $security, RouterInterface $router, TokenStorageInterface $tokenStorage)
    {
        $this->security = $security;
        $this->router = $router;
        $this->tokenStorage = $tokenStorage;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $user = $this->security->getUser();

        if ($user instanceof UserInterface && in_array('ROLE_BANNED', $user->getRoles(), true)) {
            $this->tokenStorage->setToken(null);
            $event->setResponse(new RedirectResponse($this->router->generate('login')));
        }
    }
}