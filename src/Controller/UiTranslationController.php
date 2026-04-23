<?php

namespace App\Controller;

use App\Service\TranslationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class UiTranslationController extends AbstractController
{
    private const SUPPORTED_LOCALES = ['en', 'fr', 'ar'];
    private const MAX_TEXTS_PER_REQUEST = 80;
    private const MAX_TEXT_LENGTH = 220;

    #[Route('/ui/translate', name: 'app_ui_translate', methods: ['POST'])]
    public function translateUi(Request $request, TranslationService $translationService): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        $to = $payload['to'] ?? 'en';
        $texts = $payload['texts'] ?? [];

        if (!\in_array($to, self::SUPPORTED_LOCALES, true) || !\is_array($texts)) {
            return $this->json(['translations' => []], 400);
        }

        if ($to === 'en') {
            return $this->json(['translations' => $texts]);
        }

        $inputTexts = \array_slice($texts, 0, self::MAX_TEXTS_PER_REQUEST);
        $normalized = [];
        foreach ($inputTexts as $idx => $text) {
            $safeText = trim((string) $text);
            if ($safeText === '' || mb_strlen($safeText) > self::MAX_TEXT_LENGTH) {
                $normalized[$idx] = '';
                continue;
            }
            $normalized[$idx] = $safeText;
        }

        $cache = $request->getSession()->get('_ui_translation_cache', []);
        $cacheByLocale = \is_array($cache[$to] ?? null) ? $cache[$to] : [];

        $missing = [];
        $indexToKey = [];
        $translations = [];

        foreach ($normalized as $idx => $text) {
            if ($text === '') {
                $translations[$idx] = $inputTexts[$idx] ?? '';
                continue;
            }

            if (isset($cacheByLocale[$text])) {
                $translations[$idx] = $cacheByLocale[$text];
                continue;
            }

            $indexToKey[$idx] = $text;
            if (!isset($missing[$text])) {
                $missing[$text] = $text;
            }
        }

        if ($missing !== []) {
            $translatedMissing = $translationService->translateMany(array_values($missing), 'en', $to);
            foreach (array_values($missing) as $mIdx => $sourceText) {
                $cacheByLocale[$sourceText] = $translatedMissing[$mIdx] ?? $sourceText;
            }
        }

        foreach ($indexToKey as $idx => $sourceText) {
            $translations[$idx] = $cacheByLocale[$sourceText] ?? ($inputTexts[$idx] ?? '');
        }

        ksort($translations);
        $request->getSession()->set('_ui_translation_cache', array_merge($cache, [$to => $cacheByLocale]));

        return $this->json(['translations' => array_values($translations)]);
    }
}
