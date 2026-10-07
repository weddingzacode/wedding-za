const { test, expect } = require('@playwright/test');

test('homepage has compact featured cities and links to the full list', async ({ page }, testInfo) => {
  await page.goto('/');
  await expect(page.locator('#preloader')).toBeHidden();
  const section = page.locator('#cities');
  await section.scrollIntoViewIfNeeded();
  await expect(section.locator('.vision-city-card')).toHaveCount(4);
  await expect(section.getByRole('link', { name: /View all \d+ cities/ })).toHaveAttribute('href', 'cities.php');
  await expect(section.locator('.vision-city-card').first()).toBeVisible();
  const columns = await section.locator('.vision-city-rail').evaluate(el => getComputedStyle(el).gridTemplateColumns.split(' ').length);
  expect(columns).toBe(testInfo.project.name.startsWith('mobile') ? 2 : 4);
  expect(await section.evaluate(el => el.scrollWidth <= window.innerWidth)).toBeTruthy();
  await section.screenshot({ path: testInfo.outputPath('featured-cities.png'), animations: 'disabled' });
  await section.getByLabel('Where are you celebrating?').fill('Mumbai');
  await section.getByRole('button', { name: 'Find a city' }).click();
  await expect(page.locator('.city-directory-card')).toHaveCount(1);
  await expect(page.locator('.city-directory-card h3')).toHaveText('Mumbai');
});

test('city directory searches, filters, clears and opens the selected guide', async ({ page }, testInfo) => {
  await page.goto('/cities.php');
  await expect(page.locator('#preloader')).toBeHidden();
  const cards = page.locator('.city-directory-card');
  expect(await cards.count()).toBe(8);
  await expect(page.locator('.city-result-count')).toContainText('Showing 1–8 of 8 cities');
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1)).toBeTruthy();
  await page.screenshot({ path: testInfo.outputPath('city-directory.png'), fullPage: true });
  await page.getByLabel('Search by city name').fill('dElHi');
  await page.getByRole('button', { name: 'Find a city' }).click();
  await expect(cards).toHaveCount(1);
  await expect(cards.locator('h3')).toHaveText('Delhi NCR');
  await page.getByRole('link', { name: 'Clear filters' }).click();
  // Internal links use a short page-wipe animation before navigation.
  await expect(page).toHaveURL(/\/cities\.php#city-results$/);
  await expect(page.getByLabel('Search by city name')).toHaveValue('');
  await expect(cards).toHaveCount(8);
  await page.getByLabel('Starts with').selectOption('M');
  await page.getByRole('button', { name: 'Find a city' }).click();
  await expect(cards).toHaveCount(1);
  await expect(cards.locator('h3')).toHaveText('Mumbai');
  await cards.first().click();
  await expect(page).toHaveURL(/city\.php\?city=Mumbai$/);
  await expect(page.getByRole('heading', { level: 1 })).toContainText('Mumbai');
  await expect(page.getByRole('link', { name: 'All cities', exact: false }).first()).toHaveAttribute('href', 'cities.php');
});

test('missing cities have a useful empty state and escaped search text', async ({ page }) => {
  await page.goto('/cities.php?q=' + encodeURIComponent('<script>alert(1)</script>'));
  await expect(page.locator('.city-directory-card')).toHaveCount(0);
  await expect(page.locator('.city-directory-empty')).toBeVisible();
  await expect(page.locator('.city-result-count')).toContainText('<script>alert(1)</script>');
  await expect(page.locator('.city-directory-empty').getByRole('link', { name: 'Ask about your city' })).toHaveAttribute('href', 'contact.php');
  await page.goto('/cities.php?q[]=invalid&letter[]=invalid&page[]=invalid');
  await expect(page.locator('.city-directory-card')).toHaveCount(8);
});

test('city search works with JavaScript disabled', async ({ browser }) => {
  const context = await browser.newContext({ javaScriptEnabled: false });
  const page = await context.newPage();
  await page.goto('http://127.0.0.1:8088/cities.php');
  await page.getByLabel('Search by city name').fill('Udaipur');
  await page.getByRole('button', { name: 'Find a city' }).click();
  await expect(page.locator('.city-directory-card')).toHaveCount(1);
  await expect(page.locator('.city-directory-card h3')).toHaveText('Udaipur');
  await context.close();
});
