/* Native scrolling and small, one-time reveals keep the directory responsive. */
(() => {
    'use strict';
    const page = document.querySelector('.vc-page');
    if (!page) return;


    const galleryDialog = page.querySelector('.vc-photo-dialog');
    if (galleryDialog && typeof galleryDialog.showModal === 'function') {
        let photos=[], current=0, venueName='', opener=null;
        const mainPhoto=galleryDialog.querySelector('[data-vc-photo]');
        const thumbs=galleryDialog.querySelector('.vc-photo-thumbs');
        const showPhoto=index=>{
            if (!photos.length) return;
            current=(index+photos.length)%photos.length;
            mainPhoto.src=photos[current];
            mainPhoto.alt=venueName+' — photo '+(current+1);
            galleryDialog.querySelector('[data-vc-photo-count]').textContent=(current+1)+' / '+photos.length+' photos';
            [...thumbs.children].forEach((b,i)=>b.setAttribute('aria-current',String(i===current)));
            thumbs.children[current]?.scrollIntoView({block:'nearest',inline:'nearest'});
        };
        page.querySelectorAll('[data-vc-gallery-open]').forEach(button=>button.addEventListener('click',()=>{
            photos=JSON.parse(button.closest('[data-vc-gallery]').querySelector('[data-vc-images]').textContent);
            if (!photos.length) return;
            opener=button;
            venueName=button.dataset.vcName;
            galleryDialog.querySelector('#vc-photo-title').textContent=venueName;
            thumbs.replaceChildren();
            photos.forEach((src,i)=>{
                const b=document.createElement('button'); b.type='button';
                b.setAttribute('aria-label','View photo '+(i+1));
                const img=document.createElement('img');img.src=src;img.alt='';img.loading='lazy';
                b.append(img);b.addEventListener('click',()=>showPhoto(i));thumbs.append(b);
            });
            galleryDialog.showModal();showPhoto(0);
        }));
        galleryDialog.querySelector('[data-vc-close]').addEventListener('click',()=>galleryDialog.close());
        galleryDialog.querySelector('[data-vc-prev]').addEventListener('click',()=>showPhoto(current-1));
        galleryDialog.querySelector('[data-vc-next]').addEventListener('click',()=>showPhoto(current+1));
        galleryDialog.addEventListener('keydown',e=>{
            if(e.key==='ArrowLeft'||e.key==='ArrowRight'){e.preventDefault();showPhoto(current+(e.key==='ArrowRight'?1:-1));}
        });
        galleryDialog.addEventListener('close',()=>opener?.focus());
    }

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
