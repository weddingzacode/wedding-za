const { test, expect } = require('@playwright/test');

async function openHome(page, testInfo) {
  await page.setViewportSize(testInfo.project.name.startsWith('mobile')
    ? { width: 390, height: 844 }
    : { width: 1600, height: 900 });
  await page.goto('/', { waitUntil: 'domcontentloaded' });
  await expect(page.locator('#preloader')).toBeHidden();
  await page.evaluate(() => document.fonts.ready);
  await expect(page.locator('main')).toHaveAttribute('data-home-motion', 'running');
}

async function scrollHomeTo(page, position) {
  await page.evaluate(position => {
    if (window.WZ_LENIS) {
      window.WZ_LENIS.scrollTo(position, { immediate: true, force: true });
    } else {
      window.scrollTo(0, position);
    }
    ScrollTrigger.update();
  }, position);
}

test('homepage motion reveals readable content and responds to scrolling and pointer movement', async ({ page }, testInfo) => {
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  await openHome(page, testInfo);
  const title = page.getByRole('heading', { level: 1 });
  await expect(title).toContainText('Whatever the occasion.');
  await expect.poll(() => title.locator('.home-motion-word').evaluateAll(words =>
    words.every(word => getComputedStyle(word).opacity === '1')
  )).toBeTruthy();
  expect(await page.locator('h1 .home-motion-word, h2 .home-motion-word').evaluateAll(words =>
    words.every(word => {
      const style = getComputedStyle(word);
      const parent = getComputedStyle(word.parentElement);
      return style.fontSize === parent.fontSize && style.letterSpacing === parent.letterSpacing;
    })
  )).toBeTruthy();
  await expect(page.getByRole('search', { name: 'Find venues and vendors' })).toBeInViewport();

  const card = page.locator('.vision-category-panel').first();
  await card.scrollIntoViewIfNeeded();
  await expect.poll(() => card.locator('img').evaluate(image =>
    image.complete && image.naturalWidth > 0 && getComputedStyle(image).opacity === '1'
  )).toBeTruthy();
  await expect.poll(() => page.locator('.home-motion-progress').evaluate(element =>
    new DOMMatrixReadOnly(getComputedStyle(element).transform).a
  )).toBeGreaterThan(0);

  const supportsPointer = await page.evaluate(() => matchMedia('(hover: hover) and (pointer: fine)').matches);
  if (supportsPointer) {
    const bounds = await card.boundingBox();
    await page.mouse.move(bounds.x + bounds.width * .8, bounds.y + bounds.height * .4);
    await expect.poll(() => card.evaluate(element => parseFloat(element.style.getPropertyValue('--card-y')))).toBeGreaterThan(0);
    await page.screenshot({
      path: testInfo.outputPath('homepage-motion-hover.png'),
      style: '#pageWipe:not(.active) { visibility: hidden !important; }',
    });
    await page.mouse.move(1, page.viewportSize().height - 1);
    await expect.poll(() => card.evaluate(element => element.style.getPropertyValue('--card-y'))).toBe('');
  } else {
    expect(await card.evaluate(element => element.style.getPropertyValue('--card-y'))).toBe('');
  }
  expect(errors).toEqual([]);
});

test('homepage has no pause control and old saved pause settings do not stop animations', async ({ page }, testInfo) => {
  await page.addInitScript(() => localStorage.setItem('wz_home_motion', 'off'));
  await openHome(page, testInfo);
  await expect(page.getByRole('button', { name: /^(Pause|Resume) animations$/ })).toHaveCount(0);
  await expect(page.locator('.home-motion-toggle')).toHaveCount(0);
  const card = page.locator('.vision-category-panel').first();
  await card.scrollIntoViewIfNeeded();
  await expect(page.locator('.home-motion-progress')).toBeVisible();
  await expect(card.locator('.home-photo-curtain')).toBeHidden();
  expect(await card.locator('img').evaluate(image => getComputedStyle(image).opacity)).toBe('1');

  await page.reload({ waitUntil: 'domcontentloaded' });
  await expect(page.locator('#preloader')).toBeHidden();
  await expect(page.locator('main')).toHaveAttribute('data-home-motion', 'running');
  await expect(page.locator('.home-motion-toggle')).toHaveCount(0);
  const form = page.getByRole('search', { name: 'Find venues and vendors' });
  await form.scrollIntoViewIfNeeded();
  await expect(form).toBeInViewport();
  await form.getByLabel('City', { exact: true }).selectOption('Goa');
  await expect(form.getByLabel('City', { exact: true })).toHaveValue('Goa');
  await form.getByRole('button', { name: 'Find venues & vendors' }).click();
  await expect(page.locator('#filterCity')).toHaveValue('Goa');
});

test('changing reduced motion preferences stops effects and restores visible text', async ({ page }, testInfo) => {
  await openHome(page, testInfo);
  await page.emulateMedia({ reducedMotion: 'reduce' });
  await expect(page.locator('main')).toHaveAttribute('data-home-motion', 'reduced');
  await expect(page.locator('.home-motion-toggle')).toHaveCount(0);
  await expect(page.locator('.home-motion-progress')).toBeHidden();
  expect(await page.locator('.home-motion-word').evaluateAll(words =>
    words.every(word => getComputedStyle(word).opacity === '1' && getComputedStyle(word).transform === 'none')
  )).toBeTruthy();
  await expect(page.getByRole('search', { name: 'Find venues and vendors' })).toBeInViewport();
  await page.emulateMedia({ reducedMotion: 'no-preference' });
  await expect(page.locator('main')).toHaveAttribute('data-home-motion', 'running');
  await expect(page.locator('.home-motion-toggle')).toHaveCount(0);
});

test('photo panels open fully and decorative dividers do not block browsing', async ({ page }, testInfo) => {
  await openHome(page, testInfo);
  const card = page.locator('.vision-category-panel').first();
  const curtain = card.locator('.home-photo-curtain');
  await expect(curtain).toHaveAttribute('aria-hidden', 'true');
  expect(await curtain.locator('i').first().evaluate(panel => panel.offsetHeight)).toBeGreaterThan(20);
  await card.scrollIntoViewIfNeeded();
  await expect(curtain).toBeHidden();
  await expect.poll(() => curtain.evaluate(element => element.hidden)).toBeTruthy();
  expect(await curtain.evaluate(element => getComputedStyle(element).pointerEvents)).toBe('none');
  expect(await card.locator('img').evaluate(image => getComputedStyle(image).clipPath)).toBe('none');
  if (page.viewportSize().width <= 720) {
    const title = await page.locator('#homeEventsHeading').boundingBox();
    const description = await page.locator('#visionExperience .vision-section-head > p').boundingBox();
    expect(title.width).toBeGreaterThan(page.viewportSize().width * .8);
    expect(description.y).toBeGreaterThanOrEqual(title.y + title.height);
  }
  const divider = page.locator('#visionExperience .home-motion-divider');
  await expect(divider).toHaveAttribute('aria-hidden', 'true');
  await expect.poll(() => divider.locator('.home-motion-divider-line').evaluateAll(lines =>
    lines.every(line => Math.abs(new DOMMatrixReadOnly(getComputedStyle(line).transform).a - 1) < .01)
  )).toBeTruthy();
  await page.locator('#visionExperience').screenshot({
    path: testInfo.outputPath('homepage-motion-photo-panels.png'),
    style: '#pageWipe:not(.active), #siteHeader { visibility: hidden !important; }',
  });
  await expect(curtain).toBeHidden();
  await card.click();
  await expect(page).toHaveURL(/\/event\.php\?type=Wedding$/);
});

test('mouse highlights and magnetic actions reset, and reduced motion clears decorations', async ({ page }, testInfo) => {
  await openHome(page, testInfo);
  const button = page.getByRole('button', { name: 'Find venues & vendors' });
  const card = page.locator('.vision-category-panel').first();
  const supportsPointer = await page.evaluate(() => matchMedia('(hover: hover) and (pointer: fine)').matches);
  if (supportsPointer) {
    const bounds = await button.boundingBox();
    await button.hover({ position: { x: bounds.width - 25, y: bounds.height / 2 } });
    await expect.poll(() => button.evaluate(element => parseFloat(element.style.getPropertyValue('--magnetic-x')))).toBeGreaterThan(0);
    expect(await button.evaluate(element => parseFloat(element.style.getPropertyValue('--magnetic-x')))).toBeLessThanOrEqual(3);
    await page.mouse.move(5, page.viewportSize().height - 5);
    await expect.poll(() => button.evaluate(element => element.style.getPropertyValue('--magnetic-x'))).toBe('');
    await card.scrollIntoViewIfNeeded();
    const cardBounds = await card.boundingBox();
    await page.mouse.move(cardBounds.x + cardBounds.width * .75, cardBounds.y + cardBounds.height * .25);
    await expect.poll(() => card.evaluate(element => parseFloat(element.style.getPropertyValue('--spot-x')))).toBeGreaterThan(60);
    await expect.poll(() => card.locator('.home-card-light').evaluate(element => getComputedStyle(element).opacity)).toBe('1');
  } else {
    expect(await button.evaluate(element => getComputedStyle(element).translate)).toBe('none');
    await expect(card.locator('.home-card-light')).toBeHidden();
  }
  await page.emulateMedia({ reducedMotion: 'reduce' });
  await expect(page.locator('main')).toHaveAttribute('data-home-motion', 'reduced');
  for (const decoration of await page.locator('.home-photo-curtain, .home-card-light, .home-motion-divider, .home-portrait-trace').all()) {
    await expect(decoration).toBeHidden();
  }
  expect(await button.evaluate(element => getComputedStyle(element).translate)).toBe('none');
  expect(await card.locator('img').evaluate(image => getComputedStyle(image).clipPath)).toBe('none');
  await expect(page.locator('.home-motion-toggle')).toHaveCount(0);
});

test('headings, photos and dividers replay when revisited from both scroll directions', async ({ page }, testInfo) => {
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  await openHome(page, testInfo);
  const heading = page.locator('#homeEventsHeading');
  const words = heading.locator('.home-motion-word');
  const card = page.locator('.vision-category-panel').first();
  const curtain = card.locator('.home-photo-curtain');
  const divider = page.locator('#visionExperience .home-motion-divider-line').first();
  const position = await heading.evaluate(element => element.getBoundingClientRect().top + window.scrollY);
  const belowCard = await card.evaluate(element => element.getBoundingClientRect().bottom + window.scrollY);
  const height = page.viewportSize().height;
  const viewingPosition = Math.max(0, position - height * .25);

  async function expectReplay() {
    await expect(curtain).toBeVisible();
    await expect.poll(() => words.evaluateAll(elements =>
      elements.some(element => Number(getComputedStyle(element).opacity) < .99)
    )).toBeTruthy();
    await expect.poll(() => divider.evaluate(element =>
      new DOMMatrixReadOnly(getComputedStyle(element).transform).a
    )).toBeLessThan(.99);
    await expect(curtain).toBeHidden();
    await expect.poll(() => words.evaluateAll(elements =>
      elements.every(element => getComputedStyle(element).opacity === '1')
    )).toBeTruthy();
    await expect.poll(() => divider.evaluate(element =>
      new DOMMatrixReadOnly(getComputedStyle(element).transform).a
    )).toBeGreaterThan(.99);
    expect(await card.locator('img').evaluate(image => getComputedStyle(image).clipPath)).toBe('none');
  }

  await scrollHomeTo(page, viewingPosition);
  await expectReplay();
  await scrollHomeTo(page, belowCard + height * .5);
  await expect(card).not.toBeInViewport();
  await scrollHomeTo(page, viewingPosition);
  await expectReplay();
  await scrollHomeTo(page, 0);
  await expect(card).not.toBeInViewport();
  await scrollHomeTo(page, viewingPosition);
  await expectReplay();

  await page.evaluate(() => ScrollTrigger.refresh());
  await expect(curtain).toBeHidden();
  await card.click();
  await expect(page).toHaveURL(/\/event\.php\?type=Wedding$/);
  expect(errors).toEqual([]);
});

test('hero and planning steps replay on back scroll and respect reduced motion', async ({ page }, testInfo) => {
  await openHome(page, testInfo);
  const heroWords = page.locator('h1 .home-motion-word');
  await expect.poll(() => heroWords.evaluateAll(words =>
    words.every(word => getComputedStyle(word).opacity === '1')
  )).toBeTruthy();
  const steps = page.locator('.home-step-grid');
  const position = await steps.evaluate(element => element.getBoundingClientRect().top + window.scrollY);
  const bottom = await steps.evaluate(element => element.getBoundingClientRect().bottom + window.scrollY);
  const height = page.viewportSize().height;
  const viewingPosition = Math.max(0, position - height * .25);
  const firstStep = steps.locator('li').first();
  await scrollHomeTo(page, viewingPosition);
  await expect.poll(() => firstStep.evaluate(element =>
    Number(getComputedStyle(element).getPropertyValue('--step-line'))
  )).toBeGreaterThan(.99);
  await scrollHomeTo(page, bottom + height * .5);
  await expect(steps).not.toBeInViewport();
  await scrollHomeTo(page, viewingPosition);
  await expect.poll(() => firstStep.evaluate(element =>
    Number(getComputedStyle(element).getPropertyValue('--step-line'))
  )).toBeLessThan(.99);
  await expect.poll(() => firstStep.evaluate(element =>
    Number(getComputedStyle(element).getPropertyValue('--step-line'))
  )).toBeGreaterThan(.99);

  await scrollHomeTo(page, 0);
  await expect.poll(() => heroWords.evaluateAll(words =>
    words.some(word => Number(getComputedStyle(word).opacity) < .99)
  )).toBeTruthy();
  await page.emulateMedia({ reducedMotion: 'reduce' });
  await expect(page.locator('main')).toHaveAttribute('data-home-motion', 'reduced');
  await expect.poll(() => heroWords.evaluateAll(words =>
    words.every(word => getComputedStyle(word).opacity === '1')
  )).toBeTruthy();
  await scrollHomeTo(page, viewingPosition);
  expect(await firstStep.evaluate(element => getComputedStyle(element).transform)).toBe('none');
  await expect(page.getByRole('search', { name: 'Find venues and vendors' })).toBeVisible();
});
