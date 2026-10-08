const { test, expect } = require('@playwright/test');

async function openHome(page, testInfo) {
  await page.setViewportSize(testInfo.project.name.startsWith('mobile')
    ? { width: 390, height: 844 }
    : { width: 1600, height: 900 });
  await page.goto('/', { waitUntil: 'domcontentloaded' });
  await expect(page.locator('#preloader')).toBeHidden();
  await page.evaluate(() => document.fonts.ready);
}

test('homepage search is immediately reachable and opens the selected results', async ({ page }, testInfo) => {
  await openHome(page, testInfo);
  const form = page.getByRole('search', { name: 'Find venues and vendors' });
  await expect(form).toBeVisible();
  const bounds = await form.boundingBox();
  const header = await page.locator('#siteHeader').boundingBox();
  expect(bounds.y).toBeGreaterThan(header.y + header.height);
  expect(bounds.y + bounds.height).toBeLessThan(page.viewportSize().height);
  await page.waitForTimeout(1400);
  await page.screenshot({
    path: testInfo.outputPath('homepage-hero.png'),
    style: '#pageWipe:not(.active) { visibility: hidden !important; }',
  });
  await form.getByLabel('City', { exact: true }).selectOption('Jaipur');
  await form.getByLabel('Occasion').selectOption('Wedding');
  await form.getByLabel('Looking for').selectOption('Venues');
  await form.getByRole('button', { name: 'Find venues & vendors' }).click();
  await expect(page).toHaveURL(/\/vendors\.php\?/);
  const query = new URL(page.url()).searchParams;
  expect(query.get('city')).toBe('Jaipur');
  expect(query.get('event')).toBe('Wedding');
  expect(query.get('category')).toBe('Venues');
  await expect(page.locator('#filterCity')).toHaveValue('Jaipur');
  await expect(page.locator('#filterEvent')).toHaveValue('Wedding');
  await expect(page.locator('#filterCategory')).toHaveValue('Venues');
});

test('occasion photos load and birthday navigation preserves the selected occasion', async ({ page }, testInfo) => {
  await openHome(page, testInfo);
  const events = page.locator('#visionExperience');
  await events.scrollIntoViewIfNeeded();
  await expect(events.locator('.vision-category-panel')).toHaveCount(8);
  for (const image of await events.locator('img').all()) {
    await image.scrollIntoViewIfNeeded();
    await expect.poll(() => image.evaluate(img => img.complete && img.naturalWidth > 0)).toBeTruthy();
    await expect(image).not.toHaveAttribute('data-wz-fallback', '1');
    const caption = image.locator('xpath=ancestor::a').locator('.vision-category-copy b');
    await expect.poll(() => caption.evaluate(el => Number(getComputedStyle(el).opacity))).toBe(1);
    await expect.poll(() => caption.evaluate(el => {
      const link = el.getBoundingClientRect();
      const panel = el.closest('.vision-category-panel').getBoundingClientRect();
      return link.top >= panel.top && link.bottom <= panel.bottom - 8;
    })).toBeTruthy();
  }
  await events.screenshot({
    path: testInfo.outputPath('homepage-events.png'),
    style: '#pageWipe:not(.active), #siteHeader { visibility: hidden !important; }',
  });
  await events.getByRole('link', { name: /Birthday celebration inspiration/ }).click();
  await expect(page).toHaveURL(/\/event\.php\?type=Birthday$/);
  await expect(page.getByRole('heading', { level: 1 })).toContainText('Birthday');
});

test('customer steps lead to cities, browsing and a working shortlist', async ({ page }, testInfo) => {
  await openHome(page, testInfo);
  const steps = page.locator('#how-it-works');
  await expect(steps.getByRole('heading', { level: 3 })).toHaveText([
    'Choose your city', 'Build a shortlist', 'Send an enquiry',
  ]);
  await expect(steps.getByRole('link', { name: 'Explore cities' })).toHaveAttribute('href', 'cities.php');
  await expect(steps.getByRole('link', { name: 'Browse venues & vendors' })).toHaveAttribute('href', 'vendors.php');
  const vendor = page.locator('.vision-featured-vendors .vendor-card').first();
  const vendorName = (await vendor.locator('h3').textContent()).trim();
  await vendor.locator('[data-shortlist]').click();
  await expect(vendor.locator('[data-shortlist]')).toHaveAttribute('aria-pressed', 'true');
  await steps.getByRole('link', { name: 'Open your shortlist' }).click();
  await expect(page).toHaveURL(/\/shortlist\.php$/);
  await expect(page.locator('#shortlistGrid')).toContainText(vendorName);
});

test('compact cards fit different screens and make the page shorter', async ({ page }, testInfo) => {
  await openHome(page, testInfo);
  for (const width of [320, 360, 390, 720, 768, 1024, 1366, 1600]) {
    await page.setViewportSize({ width, height: 900 });
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), `No overflow at ${width}px`).toBeTruthy();
    const search = await page.locator('#heroPlanDock').boundingBox();
    expect(search.x).toBeGreaterThanOrEqual(0);
    expect(search.x + search.width).toBeLessThanOrEqual(width + 1);
  }
  await page.setViewportSize({ width: 1366, height: 900 });
  expect(await page.evaluate(() => document.documentElement.scrollHeight)).toBeLessThan(8500);
  const journalHeights = await page.locator('.vision-journal-card .journal-media').evaluateAll(elements => elements.map(el => el.getBoundingClientRect().height));
  expect(Math.max(...journalHeights) - Math.min(...journalHeights)).toBeLessThan(1);
  const stories = page.locator('.vision-real-panel');
  await expect(stories).toHaveCount(3);
  for (const story of await stories.all()) {
    expect(await story.evaluate(el => getComputedStyle(el).position)).toBe('relative');
  }
  await page.setViewportSize(testInfo.project.name.startsWith('mobile')
    ? { width: 390, height: 844 }
    : { width: 1600, height: 900 });
  await page.locator('.vision-real').scrollIntoViewIfNeeded();
  await expect.poll(() => stories.last().evaluate(el => Number(getComputedStyle(el).opacity))).toBe(1);
  await page.locator('.vision-real').screenshot({
    path: testInfo.outputPath('homepage-stories.png'),
    style: '#pageWipe:not(.active), #siteHeader { visibility: hidden !important; }',
  });
  for (const section of await page.locator('main > section, .vision-footer').all()) {
    await section.scrollIntoViewIfNeeded();
    await page.waitForTimeout(400);
  }
  await page.screenshot({
    path: testInfo.outputPath('homepage-overview.png'),
    fullPage: true,
    style: '#pageWipe:not(.active), #siteHeader { visibility: hidden !important; }',
  });
});

test('key descriptions are readable and reduced motion keeps content visible', async ({ page }, testInfo) => {
  await page.emulateMedia({ reducedMotion: 'reduce' });
  await openHome(page, testInfo);
  for (const selector of ['.vision-hero-copy', '.vision-section-head > p', '.home-step-grid p', '.vision-footer a']) {
    for (const element of await page.locator(selector).all()) {
      expect(await element.evaluate(el => parseFloat(getComputedStyle(el).fontSize)), selector).toBeGreaterThanOrEqual(14);
    }
  }
  const headingContrast = await page.locator('.vision-vendors h2 em').evaluate(el => {
    const rgb = value => value.match(/[\d.]+/g).slice(0, 3).map(Number);
    const luminance = values => values.map(value => {
      const s = value / 255;
      return s <= .04045 ? s / 12.92 : ((s + .055) / 1.055) ** 2.4;
    }).reduce((sum, value, index) => sum + value * [.2126, .7152, .0722][index], 0);
    const a = luminance(rgb(getComputedStyle(el).color));
    const b = luminance(rgb(getComputedStyle(el.closest('section')).backgroundColor));
    return (Math.max(a, b) + .05) / (Math.min(a, b) + .05);
  });
  expect(headingContrast).toBeGreaterThan(4.5);
  await expect(page.locator('#heroPlanDock')).toBeVisible();
  expect(await page.locator('.hero-line').first().evaluate(el => getComputedStyle(el).opacity)).toBe('1');
});

test('homepage search works without JavaScript on desktop and phones', async ({ browser }, testInfo) => {
  const context = await browser.newContext({
    javaScriptEnabled: false,
    viewport: testInfo.project.name.startsWith('mobile')
      ? { width: 390, height: 844 }
      : { width: 1600, height: 900 },
  });
  try {
    const page = await context.newPage();
    await page.goto('http://127.0.0.1:8088/', { waitUntil: 'domcontentloaded' });
    const form = page.getByRole('search', { name: 'Find venues and vendors' });
    await expect(form).toBeVisible();
    await form.getByLabel('City', { exact: true }).selectOption('Udaipur');
    await form.getByRole('button', { name: 'Find venues & vendors' }).click();
    expect(new URL(page.url()).searchParams.get('city')).toBe('Udaipur');
    await expect(page.locator('#filterCity')).toHaveValue('Udaipur');
  } finally {
    await context.close();
  }
});
