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
