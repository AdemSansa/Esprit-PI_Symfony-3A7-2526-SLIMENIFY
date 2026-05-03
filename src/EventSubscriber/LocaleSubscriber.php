<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LocaleSubscriber implements EventSubscriberInterface
{
    private const SUPPORTED_LOCALES = ['en', 'fr', 'ar'];
    private const LOCALE_SESSION_KEY = '_locale';

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $session = $request->getSession();

        $locale = $session->get(self::LOCALE_SESSION_KEY, $request->getDefaultLocale());
        if (\in_array($locale, self::SUPPORTED_LOCALES, true)) {
            $request->setLocale($locale);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 20],
        ];
    }
}
