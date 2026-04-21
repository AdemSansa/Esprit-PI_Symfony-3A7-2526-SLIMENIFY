<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class LocaleController extends AbstractController
{
    private const SUPPORTED_LOCALES = ['en', 'fr', 'ar'];

    #[Route('/locale/{locale}', name: 'app_locale_switch', methods: ['GET'])]
    public function switchLocale(string $locale, Request $request): RedirectResponse
    {
        if (\in_array($locale, self::SUPPORTED_LOCALES, true)) {
            $request->getSession()->set('_locale', $locale);
        }

        $referer = $request->headers->get('referer');
        if ($referer) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('app_start');
    }
}
