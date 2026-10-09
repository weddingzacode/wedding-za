const { test, expect } = require('@playwright/test');

const rudra = 'hotel-rudra-vilas-jaipur';
const gopal = 'the-gopal-bagh-resort-jaipur';

async function openVenues(page, query = '') {
  await page.goto('/venues.php' + query, { waitUntil: 'domcontentloaded' });
  await expect(page.locator('#preloader')).toBeHidden();
}

test('the venue collection shows supplied photos, capacities and rooms without invented prices or ratings', async ({ page }) => {
  await openVenues(page);
  const cards = page.locator('.venue-listing-card');
  await expect(cards).toHaveCount(5);
  const rudraCard = cards.filter({ has: page.getByRole('heading', { name: 'Hotel Rudra Vilas', exact: true }) });
  const gopalCard = cards.filter({ has: page.getByRole('heading', { name: 'The Gopal Bagh & Resort', exact: true }) });
  await expect(cards.first()).toHaveAttribute('data-vendor-id', rudra);
  await expect(rudraCard.locator('.venue-card-facts strong')).toHaveText(['700', '45', '8']);
  await expect(gopalCard.locator('.venue-card-facts strong')).toHaveText(['800', '63', '8']);
  for (const card of [rudraCard, gopalCard]) {
    await expect(card).toContainText('Request pricing');
    await expect(card).not.toContainText('₹');
    await expect(card).not.toContainText('Verified');
    await expect(card.locator('.venue-review-summary')).toHaveCount(0);
    await card.scrollIntoViewIfNeeded();
    await expect.poll(() => card.locator('img').evaluate(image => image.complete && image.naturalWidth > 0)).toBe(true);
  }
  await expect(page.locator('body')).toHaveAttribute('data-page', 'venues');
});

test('venue search and room filters preserve selections and return visible matching properties', async ({ page }) => {
  await openVenues(page);
  const form = page.locator('#venueFilterForm');
  await form.getByLabel('Venue or neighbourhood', { exact: true }).fill('Rudra');
  await form.getByLabel('City', { exact: true }).selectOption('Jaipur');
  await form.getByLabel('Guests', { exact: true }).fill('650');
  await form.getByRole('button', { name: 'Find venues' }).click();
  await expect(page.locator('.venue-listing-card')).toHaveCount(1);
  await expect(page.locator('.venue-listing-card h2')).toHaveText('Hotel Rudra Vilas');
  await expect(form.getByLabel('City', { exact: true })).toHaveValue('Jaipur');
  await expect(form.getByLabel('Guests', { exact: true })).toHaveValue('650');
  await form.getByLabel('Venue or neighbourhood', { exact: true }).fill('Mansarovar');
  await page.locator('.venue-advanced-filters summary').click();
  await form.getByLabel('Rooms', { exact: true }).fill('50');
  await form.getByRole('button', { name: 'Find venues' }).click();
  await expect(page.locator('.venue-listing-card')).toHaveCount(1);
  await expect(page.locator('.venue-listing-card h2')).toHaveText('The Gopal Bagh & Resort');
  await expect(form.getByLabel('Rooms', { exact: true })).toHaveValue('50');
  await page.getByRole('link', { name: 'Clear all filters' }).click();
  await expect(page.locator('.venue-listing-card')).toHaveCount(5);
});

test('capacity, unknown-price filtering, sorting and empty states are truthful', async ({ page }) => {
  await openVenues(page, '?city=Jaipur&guests=750');
  await expect(page.locator('.venue-listing-card')).toHaveCount(2);
  await expect(page.locator('.venue-listing-card h2')).not.toContainText(['Hotel Rudra Vilas']);
  await openVenues(page, '?city=Jaipur&max_price=100');
  await expect(page.locator('.venue-listing-card')).toHaveCount(0);
  await expect(page.getByRole('heading', { name: 'No exact venue match yet.' })).toBeVisible();
  await page.getByRole('link', { name: 'See all venues' }).click();
  await expect(page.locator('.venue-listing-card')).toHaveCount(5);
  await openVenues(page, '?city=Jaipur');
  await page.getByLabel('Sort results', { exact: true }).selectOption('capacity');
  await expect(page).toHaveURL(/city=Jaipur.*sort=capacity/);
  await expect(page.locator('#venueFilterForm select[name="city"]')).toHaveValue('Jaipur');
  await expect(page.locator('.venue-listing-card')).toHaveCount(3);
});

test('property pages retain all supplied event spaces, accommodation and location details', async ({ page }) => {
  await page.goto('/vendor.php?id=' + rudra);
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Hotel Rudra Vilas');
  await expect(page.locator('#spaces')).toContainText('450 guests');
  await expect(page.locator('#spaces')).toContainText('150 guests');
  await expect(page.locator('#spaces')).toContainText('700 guests');
  await expect(page.locator('#spaces')).toContainText('Rooftop area');
  await expect(page.locator('#stay')).toContainText('45 rooms');
  await expect(page.locator('#location')).toContainText('opposite ICG College');
  await expect(page.locator('#location')).toContainText('4 km');
  await expect(page.locator('#location')).toContainText('12 km');
  await expect(page.getByRole('link', { name: 'Open in Google Maps' })).toHaveAttribute('href', /maps\/search\/\?api=1&query=/);
  await page.goto('/vendor.php?id=' + gopal);
  await expect(page.locator('#stay')).toContainText('63 rooms');
  await expect(page.locator('#spaces')).toContainText('individual hall or a combined layout');
  await expect(page.locator('#spaces')).toContainText('Poolside area');
  await expect(page.locator('#location')).toContainText('Patrakar Colony');
  await expect(page.locator('.venue-amenities-list')).toContainText('Valet service');
});

test('photo galleries open the chosen image, support navigation and restore focus', async ({ page }) => {
  await page.goto('/vendor.php?id=' + rudra);
  await expect(page.locator('#preloader')).toBeHidden();
  const galleryImages = await page.locator('#gallery img').evaluateAll(images => Promise.all(images.map(image => new Promise(resolve => {
    const check = new Image();
    check.onload = () => resolve(check.naturalWidth > 0 && check.naturalHeight > 0);
    check.onerror = () => resolve(false);
    check.src = image.src;
  }))));
  expect(galleryImages).toEqual(Array(8).fill(true));
  const opener = page.locator('.venue-gallery-open');
  await opener.click();
  const dialog = page.getByRole('dialog', { name: 'Venue photo gallery' });
  await expect(dialog).toBeVisible();
  await expect(dialog.locator('[data-venue-gallery-count]')).toHaveText('1 / 8');
  await dialog.getByRole('button', { name: 'Next photo', exact: true }).click();
  await expect(dialog.locator('[data-venue-gallery-count]')).toHaveText('2 / 8');
  await expect(dialog.locator('[data-venue-gallery-image]')).toHaveAttribute('alt', 'Heritage-style hotel exterior');
  await page.keyboard.press('ArrowLeft');
  await expect(dialog.locator('[data-venue-gallery-count]')).toHaveText('1 / 8');
  await dialog.getByRole('button', { name: 'Show Heritage-style guest room', exact: true }).click();
  await expect(dialog.locator('[data-venue-gallery-count]')).toHaveText('5 / 8');
  await page.keyboard.press('Escape');
  await expect(dialog).not.toBeVisible();
  await expect(opener).toBeFocused();
  const photo = page.locator('#gallery a[data-venue-photo="2"]');
  await photo.click();
  await expect(dialog).toBeVisible();
  await expect(dialog.locator('[data-venue-gallery-count]')).toHaveText('3 / 8');
  await dialog.getByRole('button', { name: 'Close gallery', exact: true }).click();
  await expect(dialog).not.toBeVisible();
});

test('new properties can be saved, reopened and compared with correct venue facts', async ({ page }) => {
  await openVenues(page);
  await page.getByRole('button', { name: 'Save Hotel Rudra Vilas', exact: true }).click();
  await page.getByRole('button', { name: 'Save The Gopal Bagh & Resort', exact: true }).click();
  await page.goto('/shortlist.php');
  const saved = page.locator('#shortlistGrid .vendor-card:not(.hidden)');
  await expect(saved).toHaveCount(2);
  await expect(saved.getByRole('heading', { level: 3 })).toHaveText(['Hotel Rudra Vilas', 'The Gopal Bagh & Resort']);
  await saved.getByRole('link', { name: 'Open Hotel Rudra Vilas', exact: true }).click();
  await expect(page.locator('.heart-btn-static')).toHaveAttribute('aria-pressed', 'true');
  await page.getByRole('button', { name: 'Compare Hotel Rudra Vilas', exact: true }).click();
  await expect(page.locator('.venue-compare')).toHaveAttribute('aria-pressed', 'true');
  await openVenues(page);
  await page.getByRole('button', { name: 'Compare The Gopal Bagh & Resort', exact: true }).click();
  await page.getByRole('link', { name: 'Compare ↗', exact: true }).click();
  const table = page.locator('.marketplace-compare-table');
  await expect(table).toContainText('Hotel Rudra Vilas');
  await expect(table).toContainText('The Gopal Bagh & Resort');
  await expect(table).toContainText('45');
  await expect(table).toContainText('63');
  await expect(table).toContainText('No published reviews yet');
  await expect(table).not.toContainText('0 ★');
});

test('enquiry links open the correct form and submit the selected venue and customer details', async ({ page }) => {
  let submitted;
  await page.route('**/api/lead.php', async (route) => {
    const request = route.request();
    submitted = request.postData();
    await route.fulfill({ json: { ok: true, message: 'Thanks — your details were received.' } });
  });
  await openVenues(page, '?q=Rudra');
  await page.getByRole('link', { name: 'Enquire', exact: true }).click();
  await expect(page).toHaveURL(new RegExp('id=' + rudra + '#enquire$'));
  const form = page.locator('.venue-enquiry-form');
  await expect(form.getByLabel('Your name', { exact: true })).toBeVisible();
  await form.getByLabel('Your name', { exact: true }).fill('Venue QA');
  await form.getByLabel('Phone / WhatsApp', { exact: true }).fill('9000000000');
  await form.getByLabel('Email (optional)', { exact: true }).fill('venue-qa@example.com');
  await form.getByLabel('Occasion', { exact: true }).selectOption('Wedding');
  await form.getByLabel('Event date', { exact: true }).fill('2026-12-15');
  await form.getByLabel('Your plans', { exact: true }).fill('300 guests and 40 rooms.');
  await form.getByRole('button', { name: 'Request pricing & availability' }).click();
  await expect(form.locator('.success-box')).toHaveText('Thanks — your details were received.');
  expect(submitted).toContain('Hotel Rudra Vilas');
  expect(submitted).toContain('vendor-enquiry');
  expect(submitted).toContain('Jaipur');
  expect(submitted).toContain('300 guests and 40 rooms.');
  await expect(form.getByRole('button', { name: 'Request pricing & availability' })).toBeEnabled();
});

test('venue pages work with reduced motion and fit narrow phone screens', async ({ page }) => {
  await page.emulateMedia({ reducedMotion: 'reduce' });
  for (const route of ['/venues.php', '/vendor.php?id=' + gopal]) {
    await page.goto(route);
    await expect(page.getByRole('heading', { level: 1 })).toBeVisible();
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1)).toBe(true);
    await expect(page.locator('[data-venue-reveal].is-visible')).toHaveCount(0);
  }
  await page.setViewportSize({ width: 320, height: 740 });
  await openVenues(page);
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1)).toBe(true);
});

test('server rendered venue discovery and photo links work without JavaScript', async ({ browser }) => {
  const context = await browser.newContext({ javaScriptEnabled: false });
  const page = await context.newPage();
  await page.goto('http://127.0.0.1:8088/venues.php?q=Rudra&rooms=40');
  await expect(page.locator('.venue-listing-card')).toHaveCount(1);
  await expect(page.getByRole('heading', { name: 'Hotel Rudra Vilas', exact: true })).toBeVisible();
  await page.locator('.venue-listing-image').click();
  await expect(page.locator('#gallery a')).toHaveCount(8);
  await expect(page.locator('#gallery a').first()).toHaveAttribute('href', /assets\/images\/venues\/rudra-vilas-lawn.webp/);
  await context.close();
});

test('venue motion replays when scrolling back while forms and galleries remain usable', async ({ page }) => {
  await openVenues(page);
  const first = page.locator('.venue-listing-card').first();
  await first.scrollIntoViewIfNeeded();
  await expect(first).toHaveClass(/is-visible/);
  await page.locator('.venue-help-strip').scrollIntoViewIfNeeded();
  await expect(first).not.toHaveClass(/is-visible/);
  await first.scrollIntoViewIfNeeded();
  await expect(first).toHaveClass(/is-visible/);
  await page.emulateMedia({ reducedMotion: 'reduce' });
  await expect(first).not.toHaveClass(/is-visible/);
});
