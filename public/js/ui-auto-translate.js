(() => {
    const AUTO_TRANSLATE_BATCH = 40;
    const MAX_SOURCE_TEXTS_PER_PASS = 80;
    const TRANSLATABLE_ATTRS = ['placeholder', 'title', 'aria-label', 'value'];
    const PROCESSED_TEXT_NODES = new WeakMap();
    const PROCESSED_ELEMENTS = new WeakMap();
    let observer = null;
    let scheduled = false;
    let translating = false;
    let suppressObserverUntil = 0;
    const recentTranslateRuns = [];

    function hasLatinLetters(text) {
        return /[A-Za-z]/.test(text);
    }

    function looksTranslatable(text) {
        const trimmed = text.trim();
        if (!trimmed || trimmed.length < 2 || trimmed.length > 220) {
            return false;
        }
        if (!hasLatinLetters(trimmed)) {
            return false;
        }
        if (trimmed.includes('@') || trimmed.includes('http://') || trimmed.includes('https://')) {
            return false;
        }
        const digits = (trimmed.match(/\d/g) || []).length;
        if (digits > 6) {
            return false;
        }
        return true;
    }

    function shouldTranslateNode(node, locale) {
        if (!node || !node.nodeValue) {
            return false;
        }

        const trimmed = node.nodeValue.trim();
        if (!looksTranslatable(trimmed)) {
            return false;
        }

        const parent = node.parentElement;
        if (!parent || parent.closest('[data-no-auto-translate]')) {
            return false;
        }

        const tagName = parent.tagName.toLowerCase();
        if (['script', 'style', 'code', 'pre', 'svg', 'path', 'textarea'].includes(tagName)) {
            return false;
        }

        return PROCESSED_TEXT_NODES.get(node) !== locale;
    }

    function shouldTranslateAttribute(el, attr, locale) {
        if (!el || el.closest('[data-no-auto-translate]')) {
            return false;
        }

        const value = el.getAttribute(attr);
        if (!value) {
            return false;
        }

        const trimmed = value.trim();
        if (!looksTranslatable(trimmed)) {
            return false;
        }

        const processed = PROCESSED_ELEMENTS.get(el) || {};
        return processed[attr] !== locale;
    }

    function getCacheKey(locale, text) {
        return `ui-translate:${locale}:${text}`;
    }

    async function translateBatch(locale, texts) {
        try {
            const response = await fetch(window.APP_UI_TRANSLATE_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ to: locale, texts }),
            });

            if (!response.ok) {
                return texts;
            }

            const payload = await response.json();
            return Array.isArray(payload.translations) ? payload.translations : texts;
        } catch (error) {
            console.error('Translate batch request failed', error);
            return texts;
        }
    }

    async function resolveTranslations(locale, sourceTexts) {
        const map = new Map();
        const missing = [];

        sourceTexts.forEach((text) => {
            const cached = localStorage.getItem(getCacheKey(locale, text));
            if (cached) {
                map.set(text, cached);
            } else {
                missing.push(text);
            }
        });

        for (let i = 0; i < missing.length; i += AUTO_TRANSLATE_BATCH) {
            const chunk = missing.slice(i, i + AUTO_TRANSLATE_BATCH);
            const translated = await translateBatch(locale, chunk);
            chunk.forEach((source, index) => {
                const target = translated[index] || source;
                map.set(source, target);
                localStorage.setItem(getCacheKey(locale, source), target);
            });
        }

        return map;
    }

    async function translateDocumentNow() {
        const locale = window.APP_LOCALE || 'en';
        if (locale === 'en' || !window.APP_UI_TRANSLATE_URL || !document.body || translating) {
            return;
        }
        translating = true;

        try {
            const now = Date.now();
            while (recentTranslateRuns.length && now - recentTranslateRuns[0] > 60_000) {
                recentTranslateRuns.shift();
            }
            if (recentTranslateRuns.length > 20) {
                return;
            }
            recentTranslateRuns.push(now);

            const textNodes = [];
            const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
            while (walker.nextNode()) {
                const node = walker.currentNode;
                if (shouldTranslateNode(node, locale)) {
                    textNodes.push(node);
                }
            }

            const attrTargets = [];
            document.querySelectorAll('*').forEach((el) => {
                TRANSLATABLE_ATTRS.forEach((attr) => {
                    if (shouldTranslateAttribute(el, attr, locale)) {
                        attrTargets.push({ el, attr, value: el.getAttribute(attr).trim() });
                    }
                });
            });

            let sourceTexts = [...new Set([
                ...textNodes.map((node) => node.nodeValue.trim()),
                ...attrTargets.map((item) => item.value),
            ])];

            // Prioritize shorter/common UI labels first for fast visible switch.
            sourceTexts = sourceTexts
                .sort((a, b) => a.length - b.length)
                .slice(0, MAX_SOURCE_TEXTS_PER_PASS);

            if (!sourceTexts.length) {
                return;
            }

            const translations = await resolveTranslations(locale, sourceTexts);

            // Ignore self-inflicted observer events while mutating the DOM.
            suppressObserverUntil = Date.now() + 600;

            textNodes.forEach((node) => {
                const original = node.nodeValue.trim();
                const translated = translations.get(original);
                if (translated && translated !== original) {
                    const prefix = node.nodeValue.match(/^\s*/)?.[0] ?? '';
                    const suffix = node.nodeValue.match(/\s*$/)?.[0] ?? '';
                    node.nodeValue = `${prefix}${translated}${suffix}`;
                }
                PROCESSED_TEXT_NODES.set(node, locale);
            });

            attrTargets.forEach(({ el, attr, value }) => {
                const translated = translations.get(value);
                if (translated && translated !== value) {
                    el.setAttribute(attr, translated);
                }
                const processed = PROCESSED_ELEMENTS.get(el) || {};
                processed[attr] = locale;
                PROCESSED_ELEMENTS.set(el, processed);
            });
        } finally {
            translating = false;
        }
    }

    function scheduleTranslate() {
        if (scheduled) {
            return;
        }
        scheduled = true;
        setTimeout(() => {
            scheduled = false;
            translateDocumentNow().catch((error) => {
                console.error('UI auto-translation failed', error);
            });
        }, 350);
    }

    function startObserver() {
        if (!document.body) {
            return;
        }

        if (observer) {
            observer.disconnect();
        }

        observer = new MutationObserver((mutations) => {
            if (Date.now() < suppressObserverUntil) {
                return;
            }

            const hasRelevantChange = mutations.some((mutation) => mutation.type === 'childList');

            if (hasRelevantChange) {
                scheduleTranslate();
            }
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true,
        });
    }

    function bootAutoTranslate() {
        scheduleTranslate();
        if ((window.APP_LOCALE || 'en') !== 'en') {
            startObserver();
        }
    }

    document.addEventListener('DOMContentLoaded', bootAutoTranslate);
    document.addEventListener('turbo:load', bootAutoTranslate);
})();
