<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SessionTimeoutSubscriber implements EventSubscriberInterface
{
    private RequestStack $requestStack;
    private UrlGeneratorInterface $urlGenerator;
    
    private const PUBLIC_ROUTES = [
        'home', 'login', 'register', 'logout'
    ];
    
    public function __construct(RequestStack $requestStack, UrlGeneratorInterface $urlGenerator)
    {
        $this->requestStack = $requestStack;
        $this->urlGenerator = $urlGenerator;
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
        
        $userId = $session->get('user_id');
        if (!$userId) {
            $event->setResponse(new RedirectResponse($this->urlGenerator->generate('home')));
            return;
        }
    }
} 