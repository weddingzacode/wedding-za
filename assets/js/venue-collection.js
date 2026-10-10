/* Native scrolling and small, one-time reveals keep the directory responsive. */
(() => {
    'use strict';
    const page = document.querySelector('.vc-page');
    if (!page) return;

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const cards = [...page.querySelectorAll('[data-vc-reveal]')];
    let observer;
    const revealEverything = () => {
        page.classList.remove('vc-motion');
        cards.forEach(card => card.classList.remove('vc-pending'));
        observer?.disconnect();
    };

    if (!reduceMotion.matches && 'IntersectionObserver' in window) {
        try {
            observer = new IntersectionObserver(entries => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.remove('vc-pending');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.05, rootMargin: '0px 0px 70px 0px' });
            page.classList.add('vc-motion');
            cards.forEach(card => {
                /* Keep cards already in view visible, avoiding flashes on load. */
                if (card.getBoundingClientRect().top > window.innerHeight) {
                    card.classList.add('vc-pending');
                    observer.observe(card);
                }
            });
            window.setTimeout(revealEverything, 7000);
        } catch (_) {
            revealEverything();
        }
    }
    reduceMotion.addEventListener?.('change', revealEverything);

    page.querySelectorAll('img').forEach(img => {
        img.addEventListener('error', () => {
            if (img.dataset.fallbackApplied) return;
            img.dataset.fallbackApplied = '1';
            img.src = 'assets/images/venues/collection-fallback.svg';
            img.alt = 'Illustrated venue cover';
        });
    });
})();
