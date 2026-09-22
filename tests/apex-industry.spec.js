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
  for (const id of ['services', 'pains', 'proof', 'pricing', 'how', 'faq', 'book']) {
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
  expect(await page.locator('.pain-card').count()).toBeGreaterThan(0);
  expect(await page.locator('.faq__item').count()).toBeGreaterThan(0);
  expect(await page.locator('.step').count()).toBeGreaterThan(0);
  expect(await page.locator('.hero__trust li').count()).toBeGreaterThan(0);
  await expect(page.locator('.hero__sub')).not.toBeEmpty();
});

test('objection cards renumber themselves', async ({ page }) => {
  await page.goto('/industry/', { waitUntil: 'domcontentloaded' });
  const total = await page.locator('.pain-card').count();
  const labels = await page.locator('.pain-card__idx').allTextContents();
  expect(labels[0].trim()).toBe(`01 / ${String(total).padStart(2, '0')}`);
  expect(labels[total - 1].trim()).toBe(`${String(total).padStart(2, '0')} / ${String(total).padStart(2, '0')}`);
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
  await expect(page.locator('.pain-card').first()).toBeVisible();
});

test('mobile layout does not overflow', async ({ page }, testInfo) => {
  test.skip(testInfo.project.name !== 'mobile');
  await page.goto('/industry/', { waitUntil: 'domcontentloaded' });
  const w = await page.evaluate(() => ({ s: document.body.scrollWidth, c: document.documentElement.clientWidth }));
  expect(w.s).toBeLessThanOrEqual(w.c + 1);
});
