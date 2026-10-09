const { test, expect } = require('@playwright/test');
const site = require('../assets/data/site.json');

async function openHome(page) {
  await page.goto('/', { waitUntil: 'domcontentloaded' });
  await expect(page.locator('#preloader')).toBeHidden();
}

async function openShortlist(page) {
  const headerLink = page.locator('#siteHeader').getByRole('link', { name: 'Open shortlist', exact: true });
  if (await headerLink.isVisible()) {
    await headerLink.click();
  } else {
    await page.getByRole('button', { name: 'Open menu', exact: true }).click();
    await page.locator('#mobileMenu').getByRole('link', { name: 'Shortlist', exact: true }).click();
  }
  await expect(page).toHaveURL(/\/shortlist\.php$/);
}

test('each homepage link reaches a working public page or account entry screen', async ({ page }) => {
  test.setTimeout(120000);
  await openHome(page);
  const destinations = await page.locator('a[href]').evaluateAll(links => {
    return [...new Set(links.map(link => link.href))].filter(href => {
      return href.startsWith(location.origin)
        && !href.includes('compare.php?ids=');
    });
  });
  expect(destinations.length).toBeGreaterThanOrEqual(47);
  for (const destination of destinations) {
    const response = await page.goto(destination, { waitUntil: 'domcontentloaded' });
    if (response.status() === 503 && new URL(page.url()).pathname === '/login.php') {
      // The local browser fixture has no database. Production entry pages were
      // checked separately; the fixture must retain the safe disabled state.
      await expect(page.getByText('Sign-in is temporarily unavailable. Please try again later.')).toBeVisible();
    } else {
      expect(response.status(), destination).toBeLessThan(400);
    }
    await expect(page.getByRole('heading', { level: 1 }), destination).toBeVisible();
    await expect(page, destination).not.toHaveURL(/\/404\.php/);
  }
});

test('every featured business opens its own profile', async ({ page }) => {
  test.setTimeout(60000);
  await openHome(page);
  const businesses = await page.locator('.vision-featured-vendors .vendor-card').evaluateAll(cards => {
    return cards.map(card => ({
      id: card.getAttribute('data-vendor-id').trim(),
      name: card.querySelector('h3').textContent.trim(),
    }));
  });
  expect(businesses).toHaveLength(4);
  for (const business of businesses) {
    await openHome(page);
    await page.getByRole('link', { name: 'Open ' + business.name, exact: true }).click();
    await expect(page).toHaveURL(new RegExp('/vendor\\.php\\?id=' + business.id + '$'));
    await expect(page.getByRole('heading', { level: 1 })).toHaveText(business.name);
  }
});

test('shortlist cards are visible and stay saved when their profile opens', async ({ page }) => {
  await openHome(page);
  await page.getByRole('button', { name: 'Save Amber Courtyard', exact: true }).click();
  await page.getByRole('button', { name: 'Save Frame Story Co.', exact: true }).click();
  await openShortlist(page);
  const savedCards = page.locator('#shortlistGrid .vendor-card:not(.hidden)');
  await expect(savedCards).toHaveCount(2);
  await expect(savedCards.getByRole('heading', { level: 3 })).toHaveText([
    'Amber Courtyard', 'Frame Story Co.',
  ]);
  await savedCards.getByRole('link', { name: 'Open Amber Courtyard', exact: true }).click();
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Amber Courtyard');
  const saveButton = page.locator('.heart-btn-static');
  await expect(saveButton).toHaveAttribute('aria-pressed', 'true');
  await saveButton.click();
  await openShortlist(page);
  await expect(savedCards).toHaveCount(1);
  await expect(savedCards.getByRole('heading', { level: 3 })).toHaveText('Frame Story Co.');
});

test('older saved IDs are preserved, normalized and deduplicated', async ({ page }) => {
  await page.addInitScript(() => {
    localStorage.setItem('wz_shortlist', JSON.stringify([
      'amber-courtyard    ', 'amber-courtyard                    ', 'frame-story', '',
    ]));
  });
  await page.goto('/shortlist.php');
  await expect(page.locator('#shortlistGrid .vendor-card:not(.hidden)')).toHaveCount(2);
  await expect(page.locator('#shortlistHeroCount')).toHaveText('2');
  expect(await page.evaluate(() => JSON.parse(localStorage.getItem('wz_shortlist')))).toEqual([
    'amber-courtyard', 'frame-story',
  ]);
});

test('occasion links preserve their image, description and optional city', async ({ page }) => {
  test.setTimeout(60000);
  for (const event of site.event_types) {
    await openHome(page);
    await page.locator('#visionExperience').getByRole('link', {
      name: new RegExp('^' + event.name + ' celebration inspiration'),
    }).click();
    await expect(page.getByRole('heading', { level: 1 })).toContainText(event.name);
    await expect(page.locator('.landing-hero-content > p')).toContainText(event.sub);
    expect(await page.locator('.landing-hero-image img').evaluate(image => image.src)).toBe(event.image);
    await expect(page.locator('.landing-hero-content > .eyebrow')).not.toContainText('CHANDIGARH');
    const vendorLink = page.locator('.landing-hero-actions a').first();
    const params = new URL(await vendorLink.getAttribute('href'), page.url()).searchParams;
    expect(params.get('event').trim()).toBe(event.name);
    expect(params.has('city')).toBeFalsy();
  }
  await page.goto('/event.php?type=Wedding&city=Jaipur');
  await expect(page.locator('.landing-hero-content > .eyebrow')).toContainText('JAIPUR');
  const href = await page.locator('.landing-hero-actions a').first().getAttribute('href');
  expect(new URL(href, page.url()).searchParams.get('city').trim()).toBe('Jaipur');
});

test('header rendering preserves venue filters and compare opens selected venues', async ({ page }) => {
  await page.goto('/venues.php');
  await expect(page.locator('main select[name="city"]')).toHaveValue('');
  await page.goto('/venues.php?city=Udaipur');
  await expect(page.locator('main select[name="city"]')).toHaveValue('Udaipur');
  await openHome(page);
  await page.getByRole('button', { name: 'Compare Amber Courtyard', exact: true }).click();
  await page.getByRole('button', { name: 'Compare Raas by the Lake', exact: true }).click();
  await page.getByRole('link', { name: 'Compare ↗', exact: true }).click();
  await expect(page).toHaveURL(/\/compare\.php\?ids=amber-courtyard%2Craas-udaipur$/);
  await expect(page.locator('table').getByRole('heading', { level: 3 })).toHaveText([
    'Amber Courtyard', 'Raas by the Lake',
  ]);
});
