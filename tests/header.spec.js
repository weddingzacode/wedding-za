const { test, expect } = require('@playwright/test');

test('first section has a premium header and no removed labels', async ({ page }, testInfo) => {
  if (testInfo.project.name === 'desktop-chromium') {
    await page.setViewportSize({ width: 1600, height: 900 });
  }
  await page.goto('/');
  await expect(page.locator('#preloader')).toBeHidden();
  const header = page.locator('#siteHeader');
  await expect(header.getByRole('link', { name: 'Plan an event' })).toBeVisible();
  await expect(header.getByRole('link', { name: 'Plan an event' })).toHaveAttribute('href', 'planner.php');
  await expect(header.getByRole('link', { name: 'Wedding Za home' })).toBeVisible();
  expect(await header.locator('.wz-brand-mark').evaluate(image => image.complete && image.naturalWidth > 0)).toBeTruthy();
  await expect(page.locator('.vision-hero-eyebrow, .vision-scroll-note')).toHaveCount(0);
  await expect(page.getByRole('heading', { level: 1 })).toContainText('Whatever the occasion.');
  // GSAP is driven by JavaScript, so wait for its opening sequence before capture.
  await page.waitForTimeout(2600);
  await page.screenshot({
    path: testInfo.outputPath('homepage-header.png'),
    style: '#pageWipe:not(.active) { visibility: hidden !important; }',
  });
});

test('Contact is reachable through desktop navigation and the mobile menu', async ({ page }, testInfo) => {
  const mobile = testInfo.project.name === 'mobile-chromium';
  if (!mobile) await page.setViewportSize({ width: 1600, height: 900 });
  await page.goto('/');
  await expect(page.locator('#preloader')).toBeHidden();
  if (mobile) {
    await page.getByRole('button', { name: 'Open menu' }).click();
    await expect(page.locator('main')).toHaveAttribute('inert', '');
    await page.locator('#mobileMenu').getByRole('link', { name: 'Contact', exact: true }).click();
  } else {
    await page.getByRole('navigation', { name: 'Primary navigation' }).getByRole('link', { name: 'Contact' }).click();
  }
  await expect(page).toHaveURL(/\/contact\.php$/);
  if (!mobile) {
    await expect(page.locator('.vision-nav-links a[href="contact.php"]')).toHaveClass(/is-active/);
  }
});

test('Plan an event opens the actual planning workspace', async ({ page }) => {
  await page.goto('/');
  await expect(page.locator('#preloader')).toBeHidden();
  await page.locator('#siteHeader').getByRole('link', { name: 'Plan an event' }).click();
  await expect(page).toHaveURL(/\/planner\.php$/);
  await expect(page.getByRole('heading', { level: 1 })).toContainText('Plan with');
  await expect(page.locator('#eventBriefForm')).toBeVisible();
  await expect(page.locator('#discoveryPanel')).not.toHaveClass(/open/);
});

test('navigation controls fit phones, tablets and desktops without overlap', async ({ page }) => {
  await page.goto('/');
  await expect(page.locator('#preloader')).toBeHidden();
  await page.evaluate(() => document.fonts.ready);
  for (const width of [320, 360, 393, 720, 768, 1024, 1280, 1366, 1600]) {
    await page.setViewportSize({ width, height: 900 });
    const layout = await page.locator('#siteHeader .vision-nav').evaluate(nav => {
      const outer = nav.getBoundingClientRect();
      const controls = [...nav.querySelectorAll('a, button')]
        .filter(element => element.getClientRects().length)
        .map(element => {
          const rect = element.getBoundingClientRect();
          return { left: rect.left, right: rect.right, top: rect.top, bottom: rect.bottom };
        }).sort((a, b) => a.left - b.left);
      return {
        contained: controls.every(rect => rect.left >= outer.left && rect.right <= outer.right + 1 && rect.top >= outer.top && rect.bottom <= outer.bottom),
        separated: controls.every((rect, index) => index === 0 || rect.left >= controls[index - 1].right - 1),
      };
    });
    expect(layout.contained, `Controls stay inside the header at ${width}px`).toBeTruthy();
    expect(layout.separated, `Controls do not overlap at ${width}px`).toBeTruthy();
  }
});

test('mobile menu supports Escape, focus return and resizing', async ({ page }, testInfo) => {
  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto('/');
  await expect(page.locator('#preloader')).toBeHidden();
  const button = page.getByRole('button', { name: 'Open menu' });
  const menu = page.locator('#mobileMenu');
  await button.click();
  await expect(button).toHaveAttribute('aria-expanded', 'true');
  await expect(menu.getByRole('link', { name: 'Venues', exact: true })).toBeFocused();
  await page.screenshot({
    path: testInfo.outputPath('mobile-menu.png'),
    style: '#pageWipe:not(.active) { visibility: hidden !important; }',
  });
  await page.keyboard.press('Escape');
  await expect(button).toHaveAttribute('aria-expanded', 'false');
  await expect(button).toBeFocused();
  await expect(menu).toHaveAttribute('inert', '');
  expect(await page.locator('main').evaluate(element => element.inert)).toBeFalsy();
  await button.click();
  await page.setViewportSize({ width: 1600, height: 900 });
  await expect(menu).not.toHaveClass(/open/);
  expect(await page.locator('main').evaluate(element => element.inert)).toBeFalsy();
});

test('Contact and event planning work without JavaScript', async ({ browser }, testInfo) => {
  const viewport = testInfo.project.name === 'mobile-chromium'
    ? { width: 390, height: 844 }
    : { width: 1600, height: 900 };
  const context = await browser.newContext({ javaScriptEnabled: false, viewport });
  const page = await context.newPage();
  await page.goto('http://127.0.0.1:8088/');
  await page.locator('#siteHeader').getByRole('link', { name: 'Contact', exact: true }).click();
  await expect(page).toHaveURL(/\/contact\.php$/);
  await page.locator('#siteHeader').getByRole('link', { name: 'Plan an event' }).click();
  await expect(page).toHaveURL(/\/planner\.php$/);
  await expect(page.locator('#eventBriefForm')).toBeVisible();
  await context.close();
});
