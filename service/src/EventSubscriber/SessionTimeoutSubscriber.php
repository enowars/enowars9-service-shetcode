<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

class SessionTimeoutSubscriber implements EventSubscriberInterface
{
    private RequestStack $requestStack;
    private UrlGeneratorInterface $urlGenerator;
    private EntityManagerInterface $entityManager;

    private const PUBLIC_ROUTES = ['home', 'login', 'register', 'logout', 'admin_challenge', 'admin_challenge_submit'];

    public function __construct(
        RequestStack $requestStack,
        UrlGeneratorInterface $urlGenerator,
        EntityManagerInterface $entityManager
    ) {
        $this->requestStack = $requestStack;
        $this->urlGenerator = $urlGenerator;
        $this->entityManager = $entityManager;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        if (in_array($route, self::PUBLIC_ROUTES)) {
            return;
        }

        $session = $this->requestStack->getSession();

        if (!$session->has('user_id')) {
            $this->invalidateAndRedirect($session, $event);
            return;
        }

        $userId = $session->get('user_id');
        $user = $this->entityManager->getRepository(User::class)->find($userId);

        if (!$user) {
            $this->invalidateAndRedirect($session, $event);
            return;
        }
    }

    private function invalidateAndRedirect($session, RequestEvent $event): void
    {
        $session->invalidate();
        $event->setResponse(
            new RedirectResponse($this->urlGenerator->generate('home'))
        );
    }
}