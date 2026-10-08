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

test('visitors can pause homepage motion, keep content visible and save the preference', async ({ page }, testInfo) => {
  await openHome(page, testInfo);
  const titleText = await page.getByRole('heading', { level: 1 }).textContent();
  await page.getByRole('button', { name: 'Pause animations', exact: true }).click();
  await expect(page.locator('main')).toHaveAttribute('data-home-motion', 'paused');
  await expect(page.getByRole('button', { name: 'Resume animations', exact: true })).toHaveAttribute('aria-pressed', 'true');
  await expect(page.locator('.home-motion-progress')).toBeHidden();
  expect(await page.locator('.home-hero-portrait img').evaluate(image => getComputedStyle(image).animationName)).toBe('none');
  expect(await page.locator('.vision-category-image img').first().evaluate(image => getComputedStyle(image).opacity)).toBe('1');
  await expect(page.getByRole('heading', { level: 1 })).toHaveText(titleText);
  const card = page.locator('.vision-category-panel').first();
  await card.scrollIntoViewIfNeeded();
  if (await page.evaluate(() => matchMedia('(hover: hover) and (pointer: fine)').matches)) {
    await card.hover();
    expect(await card.evaluate(element => getComputedStyle(element).transform)).toBe('none');
    expect(await card.locator('img').evaluate(image => getComputedStyle(image).transform)).toBe('none');
  }

  await page.reload({ waitUntil: 'domcontentloaded' });
  await expect(page.locator('#preloader')).toBeHidden();
  await expect(page.locator('main')).toHaveAttribute('data-home-motion', 'paused');
  await expect(page.getByRole('button', { name: 'Resume animations', exact: true })).toBeInViewport();
  const form = page.getByRole('search', { name: 'Find venues and vendors' });
  await form.scrollIntoViewIfNeeded();
  await expect(form).toBeInViewport();
  await form.getByLabel('City', { exact: true }).selectOption('Goa');
  await page.getByRole('button', { name: 'Resume animations', exact: true }).click();
  await expect(page.locator('main')).toHaveAttribute('data-home-motion', 'running');
  await expect(form.getByLabel('City', { exact: true })).toHaveValue('Goa');
  await form.getByRole('button', { name: 'Find venues & vendors' }).click();
  await expect(page.locator('#filterCity')).toHaveValue('Goa');
});

test('changing reduced motion preferences stops effects and restores visible text', async ({ page }, testInfo) => {
  await openHome(page, testInfo);
  await page.emulateMedia({ reducedMotion: 'reduce' });
  await expect(page.locator('main')).toHaveAttribute('data-home-motion', 'reduced');
  await expect(page.locator('.home-motion-toggle')).toBeHidden();
  await expect(page.locator('.home-motion-progress')).toBeHidden();
  expect(await page.locator('.home-motion-word').evaluateAll(words =>
    words.every(word => getComputedStyle(word).opacity === '1' && getComputedStyle(word).transform === 'none')
  )).toBeTruthy();
  await expect(page.getByRole('search', { name: 'Find venues and vendors' })).toBeInViewport();
  await page.emulateMedia({ reducedMotion: 'no-preference' });
  await expect(page.locator('main')).toHaveAttribute('data-home-motion', 'running');
  await expect(page.getByRole('button', { name: 'Pause animations', exact: true })).toBeVisible();
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

test('mouse highlights and magnetic actions reset, and pausing clears decorative movement', async ({ page }, testInfo) => {
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
  await page.getByRole('button', { name: 'Pause animations', exact: true }).click();
  await expect(page.locator('main')).toHaveAttribute('data-home-motion', 'paused');
  for (const decoration of await page.locator('.home-photo-curtain, .home-card-light, .home-motion-divider, .home-portrait-trace').all()) {
    await expect(decoration).toBeHidden();
  }
  expect(await button.evaluate(element => getComputedStyle(element).translate)).toBe('none');
  expect(await card.locator('img').evaluate(image => getComputedStyle(image).clipPath)).toBe('none');
  await page.emulateMedia({ reducedMotion: 'reduce' });
  await expect(page.locator('main')).toHaveAttribute('data-home-motion', 'reduced');
  await expect(page.locator('.home-motion-toggle')).toBeHidden();
});
