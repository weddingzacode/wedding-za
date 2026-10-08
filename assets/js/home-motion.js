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
    const sectionHeads = [...home.querySelectorAll('.vision-section-head, .home-steps-head')];
    const magneticButtons = [...document.querySelectorAll(
      '.home-refreshed .hero-plan-dock > button, .home-refreshed .home-help-actions a, .vision-finale a'
    )];
    const photoCurtains = new Map();
    const revealedPhotos = new Set();
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

    cards.forEach(card => {
      card.classList.add('home-motion-card');
      const light = document.createElement('i');
      light.className = 'home-card-light';
      light.setAttribute('aria-hidden', 'true');
      card.append(light);
    });
    magneticButtons.forEach(button => button.classList.add('home-magnetic-button'));

    sectionHeads.forEach(header => {
      header.classList.add('home-section-motion');
      const divider = document.createElement('div');
      divider.className = 'home-motion-divider';
      divider.setAttribute('aria-hidden', 'true');
      ['line', 'diamond', 'line'].forEach(part => {
        const element = document.createElement('i');
        element.className = `home-motion-divider-${part}`;
        divider.append(element);
      });
      header.append(divider);
    });

    home.querySelectorAll(
      '.vision-category-image, .vision-city-card figure, .vendor-media, .home-idea-image, .journal-media'
    ).forEach((box, index) => {
      box.classList.add('home-motion-photo');
      const curtain = document.createElement('div');
      curtain.className = 'home-photo-curtain';
      curtain.classList.toggle('home-photo-curtain-wine', index % 2 === 0);
      curtain.setAttribute('aria-hidden', 'true');
      curtain.append(document.createElement('i'), document.createElement('i'));
      box.append(curtain);
      photoCurtains.set(box, curtain);
    });

    const svgNamespace = 'http://www.w3.org/2000/svg';
    const portraitTrace = document.createElementNS(svgNamespace, 'svg');
    portraitTrace.classList.add('home-portrait-trace');
    portraitTrace.setAttribute('viewBox', '0 0 300 400');
    portraitTrace.setAttribute('preserveAspectRatio', 'none');
    portraitTrace.setAttribute('aria-hidden', 'true');
    portraitTrace.setAttribute('focusable', 'false');
    const portraitOutline = document.createElementNS(svgNamespace, 'path');
    portraitOutline.setAttribute('d', 'M14 399 H286 Q299 399 299 385 V111 Q299 1 189 1 H111 Q1 1 1 111 V385 Q1 399 14 399 Z');
    portraitOutline.setAttribute('pathLength', '1');
    portraitTrace.append(portraitOutline);
    hero.querySelector('.home-hero-portrait').append(portraitTrace);

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
        card.style.setProperty('--spot-x', `${(x + 1) * 50}%`);
        card.style.setProperty('--spot-y', `${(y + 1) * 50}%`);
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
          card.style.removeProperty('--spot-x');
          card.style.removeProperty('--spot-y');
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

    function magneticMotion() {
      if (!finePointer.matches) {
        return () => {};
      }

      const listeners = [];
      magneticButtons.forEach(button => {
        let frame = null;
        let position = null;

        const move = event => {
          if (event.pointerType !== 'mouse') {
            return;
          }
          const bounds = button.getBoundingClientRect();
          position = {
            x: Math.max(-3, Math.min(3, (event.clientX - bounds.left - bounds.width / 2) * .04)),
            y: Math.max(-3, Math.min(3, (event.clientY - bounds.top - bounds.height / 2) * .06))
          };
          if (frame === null) {
            frame = requestAnimationFrame(() => {
              frame = null;
              button.style.setProperty('--magnetic-x', `${position.x}px`);
              button.style.setProperty('--magnetic-y', `${position.y}px`);
            });
          }
        };

        const leave = () => {
          if (frame !== null) {
            cancelAnimationFrame(frame);
            frame = null;
          }
          button.style.removeProperty('--magnetic-x');
          button.style.removeProperty('--magnetic-y');
        };

        button.addEventListener('pointermove', move, { passive: true });
        button.addEventListener('pointerleave', leave);
        button.addEventListener('blur', leave);
        listeners.push({ button, move, leave });
      });

      return () => {
        listeners.forEach(({ button, move, leave }) => {
          button.removeEventListener('pointermove', move);
          button.removeEventListener('pointerleave', leave);
          button.removeEventListener('blur', leave);
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
        opening.fromTo(portraitOutline, { strokeDashoffset: 1 }, {
          strokeDashoffset: 0,
          duration: 2.1,
          ease: 'power2.inOut'
        }, .25);
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

        sectionHeads.forEach(header => {
          const divider = header.querySelector('.home-motion-divider');
          const details = [header.querySelector('small, .home-kicker'), header.querySelector(':scope > p')].filter(Boolean);
          const reveal = gsap.timeline({
            scrollTrigger: { trigger: header, start: 'top 92%', once: true }
          });
          reveal.fromTo(details, { y: 10, opacity: .55 }, {
            y: 0,
            opacity: 1,
            duration: .65,
            stagger: .1,
            ease: 'power3.out',
            clearProps: 'transform,opacity'
          }, 0);
          reveal.fromTo(divider.querySelectorAll('.home-motion-divider-line'), { scaleX: 0 }, {
            scaleX: 1,
            duration: 1.05,
            stagger: .1,
            ease: 'power3.inOut'
          }, .15);
          reveal.fromTo(divider.querySelector('.home-motion-divider-diamond'), { scale: .2, rotation: -135 }, {
            scale: 1,
            rotation: 45,
            duration: .9,
            ease: 'power3.out'
          }, .35);
        });

        home.querySelectorAll(
          '.vision-category-image img, .vision-city-card figure img, .vendor-media img, .vision-real-panel img, .home-idea-image img, .journal-media img'
        ).forEach(photo => {
          if (revealedPhotos.has(photo)) {
            return;
          }
          const curtain = photoCurtains.get(photo.parentElement);
          const reveal = gsap.timeline({
            onComplete: () => {
              revealedPhotos.add(photo);
              if (curtain) {
                curtain.hidden = true;
              }
            },
            scrollTrigger: { trigger: photo.parentElement, start: 'top 94%', once: true }
          });
          reveal.fromTo(photo, { scale: 1.14, opacity: .45, yPercent: 2, clipPath: 'inset(0% 0% 22% 0%)' }, {
            scale: 1.035,
            opacity: 1,
            yPercent: 0,
            clipPath: 'inset(0% 0% 0% 0%)',
            duration: .95,
            ease: 'power3.out',
            clearProps: 'clipPath'
          }, 0);
          if (curtain) {
            reveal.fromTo(curtain.children, { yPercent: 0 }, {
              yPercent: index => index === 0 ? -101 : 101,
              duration: .85,
              stagger: .06,
              ease: 'power3.inOut'
            }, 0);
          }
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

      const stopCards = pointerMotion();
      const stopButtons = magneticMotion();
      cleanupPointer = () => {
        stopCards();
        stopButtons();
      };
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
