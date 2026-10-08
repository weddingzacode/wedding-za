(() => {
  'use strict';

  function initializeHomeMotion() {
    const home = document.querySelector('.home-refreshed');

    if (!home) {
      return;
    }

    if (typeof gsap === 'undefined' || typeof ScrollTrigger === 'undefined') {
      home.dataset.homeMotion = 'unavailable';
      return;
    }

    const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)');
    const finePointer = matchMedia('(hover: hover) and (pointer: fine)');
    const hero = home.querySelector('.vision-hero');
    const cards = [...home.querySelectorAll(
      '.vision-category-panel, .vision-city-card, .vendor-card, .vision-real-panel, .vision-idea, .vision-journal-card'
    )];
    const headings = [...home.querySelectorAll('h1, .vision-section-head h2, .home-steps h2, .vision-concierge-grid h2')];
    const finaleHeading = document.querySelector('.vision-finale h2');
    let userPaused = false;
    let context = null;
    let cleanupPointer = () => {};
    let heroObserver = null;
    let lenis = null;
    let tick = null;

    if (finaleHeading) {
      headings.push(finaleHeading);
    }

    try {
      userPaused = localStorage.getItem('wz_home_motion') === 'off';
    } catch {
      // The preference is optional when browser storage is unavailable.
    }

    cards.forEach(card => card.classList.add('home-motion-card'));

    const glow = document.createElement('div');
    glow.className = 'home-motion-glow';
    glow.setAttribute('aria-hidden', 'true');
    hero.append(glow);

    const progress = document.createElement('div');
    progress.className = 'home-motion-progress';
    progress.setAttribute('aria-hidden', 'true');
    document.body.append(progress);

    const toggle = document.createElement('button');
    toggle.className = 'home-motion-toggle';
    toggle.type = 'button';
    document.body.append(toggle);

    function splitWords(heading) {
      if (heading.dataset.motionWords === 'ready') {
        return [...heading.querySelectorAll('.home-motion-word')];
      }

      const walker = document.createTreeWalker(heading, NodeFilter.SHOW_TEXT);
      const nodes = [];

      while (walker.nextNode()) {
        nodes.push(walker.currentNode);
      }

      nodes.forEach(node => {
        const fragment = document.createDocumentFragment();

        node.textContent.split(/(\s+)/).forEach(part => {
          if (!part || /^\s+$/.test(part)) {
            fragment.append(document.createTextNode(part));
            return;
          }

          const word = document.createElement('span');
          word.className = 'home-motion-word';
          word.textContent = part;
          fragment.append(word);
        });

        node.replaceWith(fragment);
      });

      heading.dataset.motionWords = 'ready';
      return [...heading.querySelectorAll('.home-motion-word')];
    }

    function pointerMotion() {
      if (!finePointer.matches) {
        return () => {};
      }

      const listeners = [];
      let frame = null;
      let pending = null;

      function paint() {
        frame = null;

        if (!pending) {
          return;
        }

        const { card, x, y } = pending;
        card.style.setProperty('--card-x', `${y * -2.2}deg`);
        card.style.setProperty('--card-y', `${x * 2.2}deg`);
        pending = null;
      }

      cards.forEach(card => {
        const move = event => {
          if (event.pointerType !== 'mouse' || event.target.closest('button, input, select')) {
            return;
          }

          const bounds = card.getBoundingClientRect();
          pending = {
            card,
            x: Math.max(-1, Math.min(1, (event.clientX - bounds.left) / bounds.width * 2 - 1)),
            y: Math.max(-1, Math.min(1, (event.clientY - bounds.top) / bounds.height * 2 - 1))
          };

          if (frame === null) {
            frame = requestAnimationFrame(paint);
          }
        };

        const leave = () => {
          if (pending?.card === card) {
            pending = null;
          }
          card.style.removeProperty('--card-x');
          card.style.removeProperty('--card-y');
        };

        card.addEventListener('pointermove', move, { passive: true });
        card.addEventListener('pointerleave', leave);
        listeners.push({ card, move, leave });
      });

      return () => {
        if (frame !== null) {
          cancelAnimationFrame(frame);
        }
        listeners.forEach(({ card, move, leave }) => {
          card.removeEventListener('pointermove', move);
          card.removeEventListener('pointerleave', leave);
          leave();
        });
      };
    }

    function stop() {
      cleanupPointer();
      cleanupPointer = () => {};
      heroObserver?.disconnect();
      heroObserver = null;
      context?.revert();
      context = null;

      if (tick) {
        gsap.ticker.remove(tick);
        tick = null;
      }

      lenis?.destroy();
      lenis = null;
      window.WZ_LENIS = null;
      hero.classList.remove('motion-visible');
      document.body.classList.remove('home-motion-on');
      progress.style.transform = 'scaleX(0)';
    }

    function updatePreference() {
      stop();
      document.body.classList.toggle('home-motion-paused', reducedMotion.matches || userPaused);
      toggle.hidden = reducedMotion.matches;
      progress.hidden = reducedMotion.matches || userPaused;
      toggle.textContent = userPaused ? 'Resume animations' : 'Pause animations';
      toggle.setAttribute('aria-pressed', String(userPaused));

      if (reducedMotion.matches || userPaused) {
        home.dataset.homeMotion = reducedMotion.matches ? 'reduced' : 'paused';
        return;
      }

      home.dataset.homeMotion = 'running';
      document.body.classList.add('home-motion-on');
      gsap.registerPlugin(ScrollTrigger);

      context = gsap.context(() => {
        const opening = gsap.timeline({ defaults: { ease: 'power3.out' } });
        const words = splitWords(home.querySelector('h1'));
        const background = hero.querySelector('.vision-hero-bg img');
        const portrait = hero.querySelector('.home-hero-portrait img');

        opening.fromTo(background, { scale: 1.14 }, { scale: 1.04, duration: 1.8 }, 0);
        opening.fromTo(words, {
          yPercent: 65,
          rotation: 3,
          opacity: 0
        }, {
          yPercent: 0,
          rotation: 0,
          opacity: 1,
          duration: .85,
          stagger: .065,
          clearProps: 'transform,opacity'
        }, .12);
        opening.fromTo(portrait, { scale: 1.15, opacity: .5 }, {
          scale: 1.06,
          opacity: 1,
          duration: 1.25
        }, .2);
        opening.fromTo(hero.querySelector('.vision-hero-copy'), { y: 12, opacity: .4 }, {
          y: 0,
          opacity: 1,
          duration: .8,
          clearProps: 'transform,opacity'
        }, .3);

        if (finePointer.matches) {
          gsap.fromTo(background, { yPercent: -2 }, {
            yPercent: 4,
            ease: 'none',
            scrollTrigger: { trigger: hero, start: 'top top', end: 'bottom top', scrub: .6 }
          });
        }

        headings.filter(heading => heading.tagName !== 'H1').forEach(heading => {
          const words = splitWords(heading);
          gsap.fromTo(words, { yPercent: 55, opacity: .15 }, {
            yPercent: 0,
            opacity: 1,
            duration: .72,
            stagger: .045,
            ease: 'power3.out',
            clearProps: 'transform,opacity',
            scrollTrigger: { trigger: heading, start: 'top 92%', once: true }
          });

          const accent = heading.querySelector('em');
          if (accent) {
            gsap.fromTo(accent, { '--heading-line': 0 }, {
              '--heading-line': 1,
              duration: 1,
              ease: 'power3.out',
              scrollTrigger: { trigger: heading, start: 'top 90%', once: true }
            });
          }
        });

        home.querySelectorAll(
          '.vision-category-image img, .vision-city-card figure img, .vendor-media img, .vision-real-panel img, .home-idea-image img, .journal-media img'
        ).forEach(photo => {
          gsap.fromTo(photo, { scale: 1.14, opacity: .45, yPercent: 2 }, {
            scale: 1.035,
            opacity: 1,
            yPercent: 0,
            duration: .95,
            ease: 'power3.out',
            scrollTrigger: { trigger: photo.parentElement, start: 'top 94%', once: true }
          });
        });

        home.querySelectorAll('.vision-featured-vendors, .vision-real-stage, .vision-idea-collage, .vision-journal-grid').forEach(group => {
          gsap.fromTo([...group.children], { opacity: .4 }, {
            opacity: 1,
            duration: .65,
            stagger: .075,
            clearProps: 'opacity',
            scrollTrigger: { trigger: group, start: 'top 94%', once: true }
          });
        });

        const steps = [...home.querySelectorAll('.home-step-grid li')];
        gsap.fromTo(steps, { '--step-line': 0 }, {
          '--step-line': 1,
          duration: .8,
          stagger: .16,
          scrollTrigger: { trigger: '.home-step-grid', start: 'top 90%', once: true }
        });
        gsap.fromTo(home.querySelectorAll('.home-step-number'), { scale: .8, rotation: -12 }, {
          scale: 1,
          rotation: 0,
          duration: .8,
          stagger: .16,
          ease: 'back.out(1.5)',
          clearProps: 'transform',
          scrollTrigger: { trigger: '.home-step-grid', start: 'top 90%', once: true }
        });

        gsap.fromTo(progress, { scaleX: 0 }, {
          scaleX: 1,
          ease: 'none',
          scrollTrigger: { trigger: document.body, start: 'top top', end: 'bottom bottom', scrub: .2 }
        });

        if (finePointer.matches) {
          gsap.fromTo(home.querySelector('.vision-concierge-image img'), { yPercent: -3, scale: 1.08 }, {
            yPercent: 3,
            scale: 1.04,
            ease: 'none',
            scrollTrigger: { trigger: '.vision-concierge', start: 'top bottom', end: 'bottom top', scrub: .6 }
          });
        }
      }, document.body);

      if ('IntersectionObserver' in window) {
        heroObserver = new IntersectionObserver(entries => {
          hero.classList.toggle('motion-visible', entries[0].isIntersecting);
        });
        heroObserver.observe(hero);
      }

      if (typeof Lenis !== 'undefined' && finePointer.matches) {
        lenis = new Lenis({ duration: .7, smoothWheel: true, wheelMultiplier: 1 });
        lenis.on('scroll', ScrollTrigger.update);
        tick = time => lenis.raf(time * 1000);
        gsap.ticker.add(tick);
        window.WZ_LENIS = lenis;
      }

      cleanupPointer = pointerMotion();
      ScrollTrigger.refresh();
    }

    toggle.addEventListener('click', () => {
      userPaused = !userPaused;
      try {
        localStorage.setItem('wz_home_motion', userPaused ? 'off' : 'on');
      } catch {
        // The pause control still works for this visit.
      }
      updatePreference();
    });

    reducedMotion.addEventListener('change', updatePreference);
    finePointer.addEventListener('change', updatePreference);
    document.addEventListener('visibilitychange', () => {
      document.body.classList.toggle('home-motion-hidden', document.hidden);
    });
    window.addEventListener('load', () => ScrollTrigger.refresh(), { once: true });
    document.fonts?.ready.then(() => ScrollTrigger.refresh());
    updatePreference();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeHomeMotion, { once: true });
  } else {
    initializeHomeMotion();
  }
})();
