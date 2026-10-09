const { test, expect } = require('@playwright/test');

for (const [path, heading] of [
  ['/privacy.php', 'Privacy policy'],
  ['/terms.php', 'Terms of use'],
  ['/cancellation.php', 'Cancellation & refunds'],
]) {
  test(`policy is readable and linked: ${path}`, async ({ page }) => {
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    const response = await page.goto(path, { waitUntil: 'domcontentloaded' });
    expect(response.status()).toBe(200);
    await expect(page.getByRole('heading', { level: 1 })).toHaveText(heading);
    const policy = page.locator('.policy-copy');
    await expect(policy).toBeVisible();
    await expect(policy.getByRole('navigation', { name: 'Policy pages' }).getByRole('link')).toHaveCount(3);
    await expect(policy.locator('.policy-contact')).toContainText('info@weddingza.com');
    expect(await policy.locator('p').first().evaluate(el => parseFloat(getComputedStyle(el).fontSize))).toBeGreaterThanOrEqual(14);
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1)).toBeTruthy();
    expect(await policy.innerText()).not.toMatch(/demo build|demo package|placeholder policy/);
    expect(errors).toEqual([]);
  });
}
