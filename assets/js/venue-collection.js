/* Native scrolling, repeatable reveals and the existing shortlist controls. */
(() => {
    'use strict';
    const page = document.querySelector('.vc-page');
    if (!page) return;

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const cards = [...page.querySelectorAll('[data-vc-reveal]')];
    let observer;
    const resetMotion = () => {
        observer?.disconnect();
        page.classList.remove('vc-motion');
        cards.forEach(card => card.classList.remove('vc-visible'));
    };

    const setupMotion = () => {
        resetMotion();
        if (reduceMotion.matches || !('IntersectionObserver' in window)) return;
        try {
            observer = new IntersectionObserver(entries => {
                entries.forEach(entry => {
                    entry.target.classList.toggle('vc-visible', entry.isIntersecting);
                });
            }, { threshold: 0.02, rootMargin: '0px 0px -20px 0px' });
            cards.forEach(card => {
                const box = card.getBoundingClientRect();
                card.classList.toggle('vc-visible', box.bottom > 0 && box.top < window.innerHeight);
                observer.observe(card);
            });
            page.classList.add('vc-motion');
        } catch (_) {
            resetMotion();
        }
    };
    setupMotion();
    reduceMotion.addEventListener?.('change', setupMotion);

    page.querySelectorAll('img').forEach(img => {
        const fallback = () => {
            if (img.dataset.fallbackApplied) return;
            img.dataset.fallbackApplied = '1';
            img.src = 'assets/images/venues/collection-fallback.svg';
            img.alt = 'Illustrated venue cover';
        };
        img.addEventListener('error', fallback);
        if (img.complete && img.naturalWidth === 0) fallback();
    });
})();
