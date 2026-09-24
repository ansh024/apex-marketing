const { test, expect } = require('@playwright/test');

test.beforeEach(async ({ page }) => {
  await page.route('**/*', async (route) => {
    const url = new URL(route.request().url());
    if (['localhost', '127.0.0.1'].includes(url.hostname)) return route.continue();
    return route.abort();
  });
});

test('renders the landing composition with site chrome', async ({ page }) => {
  await page.goto('/industry/', { waitUntil: 'domcontentloaded' });
  await expect(page.locator('.hero__h1')).toBeVisible();
  for (const id of ['services', 'pains', 'terms', 'pricing', 'guarantee', 'founder', 'how', 'faq', 'book']) {
    await expect(page.locator(`#${id}`)).toHaveCount(1);
  }
  // Chrome is the site's, not the landing template's own.
  const elementorHeader = await page.locator('.elementor-location-header').count();
  const ownHeader = await page.locator('header.nav#nav').count();
  expect(elementorHeader + ownHeader).toBe(1);
});

test('every editable region falls back to shipped copy when unset', async ({ page }) => {
  await page.goto('/industry/', { waitUntil: 'domcontentloaded' });
  // A page with no fields filled must still render complete sections, never a
  // heading above an empty list.
  expect(await page.locator('.ix-row').count()).toBeGreaterThan(0);
  expect(await page.locator('.ix-faq__item').count()).toBeGreaterThan(0);
  expect(await page.locator('.ix-step').count()).toBeGreaterThan(0);
  expect(await page.locator('.hero__trust li').count()).toBeGreaterThan(0);
  await expect(page.locator('.hero__sub')).not.toBeEmpty();
});

test('objection rows renumber themselves', async ({ page }) => {
  await page.goto('/industry/', { waitUntil: 'domcontentloaded' });
  const total = await page.locator('.ix-row').count();
  const labels = await page.locator('.ix-row__n').allTextContents();
  expect(labels[0].trim()).toBe('01');
  expect(labels[total - 1].trim()).toBe(String(total).padStart(2, '0'));
});

test('the phone number drives every call link', async ({ page }) => {
  await page.goto('/industry/', { waitUntil: 'domcontentloaded' });
  const hrefs = await page.locator('a[href^="tel:"]').evaluateAll(els => els.map(e => e.getAttribute('href')));
  expect(hrefs.length).toBeGreaterThan(0);
  expect(new Set(hrefs).size).toBe(1);
  expect(hrefs[0]).toMatch(/^tel:\+\d{11}$/);
});

test('no ACF Pro-only field types are relied on', async ({ page }) => {
  // The site runs free ACF (or none), so repeaters must not be required.
  await page.goto('/industry/', { waitUntil: 'domcontentloaded' });
  await expect(page.locator('.ix-row').first()).toBeVisible();
});

test('uses the homepage components, keeps the guarantee certificate', async ({ page }) => {
  await page.goto('/industry/', { waitUntil: 'domcontentloaded' });
  // Everything outside the hero layout is the homepage system: Arimo, paper,
  // black pill CTAs (no orange landing-page buttons anywhere).
  expect(await page.locator('.hero__h1').evaluate(el => getComputedStyle(el).fontFamily)).toMatch(/Arimo/);
  const cta = await page.locator('.hero__primary').evaluate(el => ({ bg: getComputedStyle(el).backgroundColor, r: getComputedStyle(el).borderTopLeftRadius }));
  expect(cta.bg).toBe('rgb(0, 0, 0)');
  expect(parseFloat(cta.r)).toBeGreaterThan(100);
  expect(await page.locator('.btn--signal:not(.mobile-cta), .btn--gold, .btn--dark').filter({ visible: true }).count()).toBe(0);
  const apx = await page.locator('#services').evaluate(el => ({ bg: getComputedStyle(el).backgroundColor, font: getComputedStyle(el).fontFamily }));
  expect(apx.bg).toBe('rgb(238, 238, 238)');
  expect(apx.font).toMatch(/Arimo/);
  // Homepage components, homepage behaviour.
  await expect(page.locator('#pricing .tier')).toHaveCount(3);
  await expect(page.locator('#pricingDither')).toHaveCount(1);
  await expect(page.locator('#clauses .clause')).toHaveCount(4);
  await expect(page.locator('#strategyTicket')).toHaveCount(1);
  // The certificate stays; only its lead turns brand magenta.
  await expect(page.locator('#cert #certBorder')).toHaveCount(1);
  expect(await page.locator('.cert__lead').evaluate(el => getComputedStyle(el).color)).toBe('rgb(212, 92, 184)');
});

test('mobile layout does not overflow', async ({ page }, testInfo) => {
  test.skip(testInfo.project.name !== 'mobile');
  await page.goto('/industry/', { waitUntil: 'domcontentloaded' });
  const w = await page.evaluate(() => ({ s: document.body.scrollWidth, c: document.documentElement.clientWidth }));
  expect(w.s).toBeLessThanOrEqual(w.c + 1);
});

test('scrolling is native and stable', async ({ page }) => {
  await page.goto('/industry/', { waitUntil: 'domcontentloaded' });
  await page.waitForTimeout(800);

  // No JS scroll hijacking, and the Lenis reset that used to be silently
  // descoped (making it fight html{scroll-behavior:smooth}) cannot come back.
  expect(await page.evaluate(() => typeof window.Lenis)).toBe('undefined');
  await expect(page.locator('html')).not.toHaveClass(/lenis/);

  // Stability, not exact landing: let lazy images settle, then confirm the
  // page neither drifts under the reader nor keeps resizing itself. Drift is
  // what a JS scroll library re-driving scrollY looks like.
  await page.evaluate(() => window.scrollTo(0, 3000));
  await page.waitForLoadState('networkidle').catch(() => {});
  await page.waitForTimeout(1000);

  const first = await page.evaluate(() => ({
    y: Math.round(window.scrollY), h: document.documentElement.scrollHeight }));
  await page.waitForTimeout(1200);
  const second = await page.evaluate(() => ({
    y: Math.round(window.scrollY), h: document.documentElement.scrollHeight }));

  expect(second.y).toBe(first.y);
  expect(second.h).toBe(first.h);
});

test('every CTA opens the Home Page Organic form, and a closed modal never blocks the page', async ({ page }) => {
  await page.goto('/industry/', { waitUntil: 'domcontentloaded' });
  await expect(page.locator('.ghl-form-embed')).toHaveAttribute('data-src', /qm10beYVhkFRdzkoaYk8/);
  await expect(page.locator('.ghl-form-embed')).toHaveAttribute('data-form-name', 'Home Page Organic');
  // the site header's button has no link of its own; it must still open the form
  const headerBtn = page.locator('.elementor-location-header .apex-btn');
  const overlay = page.locator('#bookModalOverlay');
  await page.locator('.hero__primary').click();
  await expect(overlay).toHaveClass(/is-open/);
  await page.keyboard.press('Escape');
  await expect(overlay).not.toHaveClass(/is-open/);
  // GHL's embed re-enables pointer events on its wrappers; nothing inside a
  // closed overlay may take a click
  const blocked = await overlay.evaluate(o => [...o.querySelectorAll('*')].filter(e => getComputedStyle(e).pointerEvents !== 'none').length);
  expect(blocked).toBe(0);
  if (await headerBtn.count()) { await headerBtn.first().click(); await expect(overlay).toHaveClass(/is-open/); }
});

test('guarantee links jump to the certificate', async ({ page }) => {
  await page.goto('/industry/', { waitUntil: 'domcontentloaded' });
  await expect(page.locator('.price__foot-link a')).toHaveAttribute('href', '#guarantee');
  await expect(page.locator('#cl-4 .clause__go')).toHaveAttribute('href', '#guarantee');
  await expect(page.locator('#cl-4 .clause__go')).toContainText('See how it works');
  await expect(page.locator('#guarantee')).toHaveCount(1);
});
