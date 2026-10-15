const { test, expect } = require('@playwright/test');

const rudra = 'hotel-rudra-vilas-jaipur';
const gopal = 'the-gopal-bagh-resort-jaipur';

async function openVenues(page, query = '') {
  await page.goto('/venues.php' + query, { waitUntil: 'domcontentloaded' });
  await expect(page.locator('#preloader')).toBeHidden();
}

test('the venue collection shows supplied photos, capacities and rooms without invented prices or ratings', async ({ page }) => {
  await openVenues(page, '?city=Jaipur');
  const cards = page.locator('.vc-card');
  await expect(cards).toHaveCount(11);
  const rudraCard = cards.filter({ has: page.getByRole('heading', { name: 'Hotel Rudra Vilas', exact: true }) });
  const gopalCard = cards.filter({ has: page.getByRole('heading', { name: 'The Gopal Bagh & Resort', exact: true }) });
  await expect(cards.first()).toHaveAttribute('data-vendor-id', 'urban-suites-blusalzz-jaipur');
  await expect(rudraCard.locator('.vc-facts strong')).toHaveText(['Up to 700', '45', 'Explore']);
  await expect(gopalCard.locator('.vc-facts strong')).toHaveText(['Up to 800', '63', 'Explore']);
  for (const card of [rudraCard, gopalCard]) {
    await expect(card).toContainText('Request pricing');
    await expect(card).not.toContainText('₹');
    await expect(card).not.toContainText('Verified');
    await expect(card.locator('.vc-review-summary')).toHaveCount(0);
    await card.scrollIntoViewIfNeeded();
    await expect.poll(() => card.locator('img').evaluate(image => image.complete && image.naturalWidth > 0)).toBe(true);
  }
  await expect(page.locator('body')).toHaveAttribute('data-page', 'venues');
});

test('venue search and room filters preserve selections and return visible matching properties', async ({ page }) => {
  await openVenues(page);
  const form = page.locator('#vc-search-form');
  await form.getByLabel('VENUE OR NEIGHBOURHOOD', { exact: true }).fill('Rudra');
  await form.getByLabel('CITY', { exact: true }).selectOption('Jaipur');
  await form.getByLabel('YOUR GUEST LIST', { exact: true }).fill('650');
  await form.getByRole('button', { name: 'Explore venues' }).click();
  await expect(page.locator('.vc-card')).toHaveCount(1);
  await expect(page.locator('.vc-card h3')).toHaveText('Hotel Rudra Vilas');
  await expect(form.getByLabel('CITY', { exact: true })).toHaveValue('Jaipur');
  await expect(form.getByLabel('YOUR GUEST LIST', { exact: true })).toHaveValue('650');
  await form.getByLabel('VENUE OR NEIGHBOURHOOD', { exact: true }).fill('Mansarovar');
  await page.locator('.vc-filter-details summary').click();
  await form.getByLabel('MINIMUM ROOMS', { exact: true }).fill('50');
  await form.getByRole('button', { name: 'Explore venues' }).click();
  await expect(page.locator('.vc-card')).toHaveCount(2);
  await expect(page.locator('.vc-card h3')).toHaveText(['Barsana Hotel & Resorts', 'The Gopal Bagh & Resort']);
  await expect(form.getByLabel('MINIMUM ROOMS', { exact: true })).toHaveValue('50');
  await page.getByRole('link', { name: 'Clear all filters ×' }).click();
  await expect(page.locator('.vc-card')).toHaveCount(12);
});

test('capacity, unknown-price filtering, sorting and empty states are truthful', async ({ page }) => {
  await openVenues(page, '?city=Jaipur&guests=750');
  await expect(page.locator('.vc-card')).toHaveCount(8);
  await expect(page.locator('.vc-card h3')).not.toContainText(['Hotel Rudra Vilas']);
  await openVenues(page, '?city=Jaipur&max_price=100');
  await expect(page.locator('.vc-card')).toHaveCount(0);
  await expect(page.getByRole('heading', { name: 'No venues match these filters.' })).toBeVisible();
  await page.getByRole('link', { name: 'Reset filters' }).click();
  await expect(page.locator('.vc-card')).toHaveCount(12);
  await openVenues(page, '?city=Jaipur');
  await page.locator('.vc-sort select').selectOption('capacity');
  await page.locator('.vc-sort button').click();
  await expect(page).toHaveURL(/city=Jaipur.*sort=capacity/);
  await expect(page.locator('#vc-search-form select[name="city"]')).toHaveValue('Jaipur');
  await expect(page.locator('.vc-card')).toHaveCount(11);
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
  await openVenues(page, '?city=Jaipur');
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
  await openVenues(page, '?city=Jaipur');
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
    await expect(page.locator('[data-vc-reveal].vc-visible')).toHaveCount(0);
  }
  await page.setViewportSize({ width: 320, height: 740 });
  await openVenues(page);
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1)).toBe(true);
});

test('server rendered venue discovery and photo links work without JavaScript', async ({ browser }) => {
  const context = await browser.newContext({ javaScriptEnabled: false });
  const page = await context.newPage();
  await page.goto('http://127.0.0.1:8088/venues.php?q=Rudra&rooms=40');
  await expect(page.locator('.vc-card')).toHaveCount(1);
  await expect(page.getByRole('heading', { name: 'Hotel Rudra Vilas', exact: true })).toBeVisible();
  await page.locator('.vc-card-media').click();
  await expect(page.locator('#gallery a')).toHaveCount(8);
  await expect(page.locator('#gallery a').first()).toHaveAttribute('href', /assets\/images\/venues\/rudra-vilas-lawn.webp/);
  await context.close();
});

test('venue motion replays when scrolling back while forms and galleries remain usable', async ({ page }) => {
  await openVenues(page);
  const first = page.locator('.vc-card').first();
  await first.scrollIntoViewIfNeeded();
  await expect(first).toHaveClass(/vc-visible/);
  await page.locator('.vc-concierge').scrollIntoViewIfNeeded();
  await expect(first).not.toHaveClass(/vc-visible/);
  await first.scrollIntoViewIfNeeded();
  await expect(first).toHaveClass(/vc-visible/);
  await page.emulateMedia({ reducedMotion: 'reduce' });
  await expect(first).not.toHaveClass(/vc-visible/);
});

const collectionProperties = [
  { id: 'urban-suites-blusalzz-jaipur', name: 'Urban Suites Blusalzz', rooms: '350', capacity: 'Up to 1,500', spaces: ['250 guests', '1,500 guests', 'Enquire for capacity'] },
  { id: 'barsana-hotel-resorts-jaipur', name: 'Barsana Hotel & Resorts', rooms: '80', capacity: 'Up to 1,000', spaces: ['Gokul Banquet Hall', '200 guests', 'Vrindavan Banquet Hall', '400 guests', '1,000 guests'] },
  { id: 'golden-eagle-wizowll-jaipur', name: 'Golden Eagle by Wizowll Group of Hotels & Banquets', rooms: '33', capacity: 'Up to 500', spaces: ['150 guests', '250 guests', '500 guests'] },
  { id: 'hari-van-jaipur', name: 'Hari Van – A Royal Wedding Destination', rooms: '40', capacity: 'Up to 1,500', spaces: ['250 guests with sofa seating', '1,500 guests; seating up to 1,000'], quoted: true },
  { id: 'caelum-atharva-palace-jaipur', name: 'Caelum Atharva Palace', rooms: '70', capacity: '2,000+', spaces: ['1,000+ guests', '2,000+ guests', '350+ guests', '250+ guests', '125+ guests', '50–60 guests', '350–400 guests', '150–200 guests', '25–30 guests'] },
  { id: 'jisaa-eco-resort-jaipur', name: 'JISAA Eco Resort', rooms: '50', capacity: 'Up to 1,500', spaces: ['150 guests', '200 guests', '1,500 guests'], quoted: true },
  { id: 'crown-heaven-hotel-resort-jaipur', name: 'Crown Heaven Hotel & Resort', rooms: '95', capacity: 'Up to 1,500', spaces: ['450 guests', '600 guests', '1,500 guests', 'VIP Lounge'] },
  { id: 'shubhagman-banquets-resort-jaipur', name: 'Shubhagman Banquets & Resort', rooms: '60', capacity: 'Up to 600', spaces: ['Indoor Banquet Spaces', 'Outdoor Lawns', 'Poolside Setting', 'Confirm individual space capacity'] },
];

for (const property of collectionProperties) {
  test(property.name + ' shows the supplied spaces, accommodation and phone links', async ({ page }) => {
    await page.goto('/venue-detail.php?id=' + property.id);
    await expect(page.locator('#preloader')).toBeHidden();
    await expect(page.getByRole('heading', { level: 1 })).toHaveText(property.name);
    await expect(page.locator('.vc-detail-hero .vc-facts strong').nth(0)).toHaveText(property.capacity);
    await expect(page.locator('.vc-detail-hero .vc-facts strong').nth(1)).toHaveText(property.rooms);
    for (const text of property.spaces) await expect(page.locator('#spaces')).toContainText(text);
    await expect(page.locator('#enquire input[name="category"]')).toHaveValue('Venues');
    await expect(page.locator('#enquire input[name="city"]')).toHaveValue('Jaipur');
    await expect(page.locator('#enquire a[href="tel:+919785005549"]')).toBeVisible();
    await expect(page.locator('.vc-detail-heading a[href="tel:+919785005549"]')).toBeVisible();
    await expect(page.locator('.vc-detail-hero')).toContainText('Illustrated venue cover');
    expect(await page.locator('.vc-detail-hero img').evaluate(img => img.complete && img.naturalWidth > 0)).toBe(true);
    if (property.quoted) await expect(page.locator('#enquire')).toContainText('₹11.50 lakh / function');
    else await expect(page.locator('#enquire')).toContainText('Request pricing');
    await page.setViewportSize({ width: 320, height: 740 });
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1)).toBe(true);
  });
}

test('pagination covers all thirteen venues and each listing has the shared call button', async ({ page }) => {
  await openVenues(page);
  const firstIds = await page.locator('.vc-card').evaluateAll(cards => cards.map(card => card.dataset.vendorId));
  expect(firstIds).toHaveLength(12);
  await expect(page.locator('.vc-card .vc-call[href="tel:+919785005549"]')).toHaveCount(12);
  await page.getByRole('navigation', { name: 'Venue results pages' }).getByRole('link', { name: '2', exact: true }).click();
  await expect(page.locator('.vc-card')).toHaveCount(1);
  const lastId = await page.locator('.vc-card').getAttribute('data-vendor-id');
  expect(new Set([...firstIds, lastId]).size).toBe(13);
  await expect(page.locator('.vc-card .vc-call')).toHaveAttribute('href', 'tel:+919785005549');
});

test('published prices filter by function or plate without mixing the units', async ({ page }) => {
  await openVenues(page, '?city=Jaipur&max_price=1150000&price_unit=function');
  await expect(page.locator('.vc-card h3')).toHaveText(['Hari Van – A Royal Wedding Destination', 'JISAA Eco Resort']);
  await openVenues(page, '?city=Jaipur&max_price=5000&price_unit=plate');
  await expect(page.locator('.vc-card h3')).toHaveText(['Amber Courtyard']);
  await openVenues(page, '?city=Jaipur&rooms=300&event=Haldi');
  await expect(page.locator('.vc-card h3')).toHaveText(['Urban Suites Blusalzz']);
});

test('a new venue can be saved, reopened and compared with its correct capacity', async ({ page }) => {
  await openVenues(page, '?q=Urban');
  await page.getByRole('button', { name: 'Save Urban Suites Blusalzz', exact: true }).click();
  await page.goto('/shortlist.php');
  const saved = page.locator('#shortlistGrid .vendor-card:not(.hidden)');
  await expect(saved).toHaveCount(1);
  await saved.getByRole('link', { name: 'Open Urban Suites Blusalzz', exact: true }).click();
  await expect(page).toHaveURL(/venue-detail.php\?id=urban-suites-blusalzz-jaipur/);
  await expect(page.locator('[data-shortlist="urban-suites-blusalzz-jaipur"]')).toHaveAttribute('aria-pressed', 'true');
  await page.getByRole('button', { name: 'Compare Urban Suites Blusalzz', exact: true }).click();
  await page.goto('/venue-detail.php?id=caelum-atharva-palace-jaipur');
  await page.getByRole('button', { name: 'Compare Caelum Atharva Palace', exact: true }).click();
  await page.getByRole('link', { name: 'Compare ↗', exact: true }).click();
  await expect(page.locator('.marketplace-compare-table')).toContainText('350');
  await expect(page.locator('.marketplace-compare-table')).toContainText('2,000+ guests');
});

test('new venue enquiries submit the venue category, identity and chosen function', async ({ page }) => {
  let fields;
  await page.route('**/api/lead.php', async route => {
    const request = route.request();
    const response = new Response(request.postDataBuffer(), {
      headers: { 'content-type': request.headers()['content-type'] },
    });
    fields = Object.fromEntries(await response.formData());
    await route.fulfill({ json: { ok: true, message: 'Thanks — your details were received.' } });
  });
  await page.goto('/venue-detail.php?id=urban-suites-blusalzz-jaipur#enquire');
  await expect(page.locator('#preloader')).toBeHidden();
  const form = page.locator('#enquire form');
  await form.getByLabel('YOUR NAME', { exact: true }).fill('Venue QA');
  await form.getByLabel('PHONE / WHATSAPP', { exact: true }).fill('9000000000');
  await form.getByLabel('YOUR OCCASION', { exact: true }).selectOption('Haldi');
  await form.getByLabel('EVENT DATE', { exact: true }).fill('2027-02-15');
  await form.getByRole('button', { name: 'Request pricing & availability' }).click();
  await expect(form.locator('.success-box')).toContainText('your details were received');
  expect(fields.category).toBe('Venues');
  expect(fields.vendor).toBe('Urban Suites Blusalzz');
  expect(fields.topic).toBe('Haldi');
});

test('the fixed header leaves the venue introduction and profile breadcrumb visible', async ({ page }) => {
  for (const [route, selector] of [
    ['/venues.php', '.vc-hero-copy .vc-eyebrow'],
    ['/venue-detail.php?id=golden-eagle-wizowll-jaipur', '.vc-breadcrumb'],
  ]) {
    await page.goto(route);
    await expect(page.locator('#preloader')).toBeHidden();
    const header = await page.locator('#siteHeader .vision-nav').boundingBox();
    const content = await page.locator(selector).boundingBox();
    expect(content.y).toBeGreaterThanOrEqual(header.y + header.height);
  }
});
