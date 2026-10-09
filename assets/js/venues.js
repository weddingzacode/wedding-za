(() => {
  'use strict';

  const dialog = document.querySelector('#venuePhotoDialog');

  if (dialog && typeof dialog.showModal === 'function') {
    const thumbnails = Array.from(dialog.querySelectorAll('[data-venue-gallery-index]'));
    const image = dialog.querySelector('[data-venue-gallery-image]');
    const caption = dialog.querySelector('[data-venue-gallery-caption]');
    const count = dialog.querySelector('[data-venue-gallery-count]');
    let photoIndex = 0;
    let previousFocus = null;

    const showPhoto = (index) => {
      if (!thumbnails.length) {
        return;
      }

      photoIndex = (index + thumbnails.length) % thumbnails.length;
      const selected = thumbnails[photoIndex];
      image.src = selected.dataset.src;
      image.alt = selected.dataset.caption;
      caption.textContent = selected.dataset.caption;
      count.textContent = (photoIndex + 1) + ' / ' + thumbnails.length;

      thumbnails.forEach((thumbnail, index) => {
        thumbnail.setAttribute('aria-current', String(index === photoIndex));
      });
    };

    document.querySelectorAll('[data-venue-photo]').forEach((link) => {
      link.addEventListener('click', (event) => {
        event.preventDefault();
        previousFocus = link;
        showPhoto(Number(link.dataset.venuePhoto) || 0);
        dialog.showModal();
      });
    });

    dialog.querySelector('[data-venue-gallery-close]').addEventListener('click', () => {
      dialog.close();
    });

    dialog.querySelector('[data-venue-gallery-prev]').addEventListener('click', () => {
      showPhoto(photoIndex - 1);
    });

    dialog.querySelector('[data-venue-gallery-next]').addEventListener('click', () => {
      showPhoto(photoIndex + 1);
    });

    thumbnails.forEach((thumbnail, index) => {
      thumbnail.addEventListener('click', () => {
        showPhoto(index);
      });
    });

    dialog.addEventListener('keydown', (event) => {
      if (event.key === 'ArrowRight') {
        event.preventDefault();
        showPhoto(photoIndex + 1);
      } else if (event.key === 'ArrowLeft') {
        event.preventDefault();
        showPhoto(photoIndex - 1);
      }
    });

    dialog.addEventListener('close', () => {
      previousFocus?.focus({ preventScroll: true });
    });
  }

  document.querySelector('[data-venue-sort]')?.addEventListener('change', (event) => {
    event.target.form.requestSubmit();
  });

  const compareButtons = Array.from(document.querySelectorAll('.venue-compare'));

  const paintCompare = () => {
    compareButtons.forEach((button) => {
      const selected = button.classList.contains('active');
      button.setAttribute('aria-pressed', String(selected));
      button.textContent = selected ? 'Added to compare ✓' : 'Compare +';
    });
  };

  if (compareButtons.length) {
    const observer = new MutationObserver(paintCompare);

    compareButtons.forEach((button) => {
      observer.observe(button, { attributes: true, attributeFilter: ['class'] });
    });

    paintCompare();
  }

  const motion = window.matchMedia('(prefers-reduced-motion: reduce)');
  const revealElements = Array.from(document.querySelectorAll('[data-venue-reveal]'));
  let revealObserver = null;

  const applyMotionPreference = () => {
    revealObserver?.disconnect();
    revealElements.forEach((element) => element.classList.remove('is-visible'));

    if (motion.matches || !('IntersectionObserver' in window)) {
      return;
    }

    revealObserver = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        entry.target.classList.toggle('is-visible', entry.isIntersecting);
      });
    }, { threshold: 0.08 });

    revealElements.forEach((element) => revealObserver.observe(element));
  };

  motion.addEventListener('change', applyMotionPreference);
  applyMotionPreference();
})();
