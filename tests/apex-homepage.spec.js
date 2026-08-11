const { test, expect } = require('@playwright/test');

test.beforeEach(async ({ page }) => {
  await page.route('**/*', async (route) => {
    const url = new URL(route.request().url());
    if (url.hostname === 'localhost' || url.hostname === '127.0.0.1') {
      await route.continue();
      return;
    }
    await route.abort();
  });
  await page.goto('/apex-home/', { waitUntil: 'domcontentloaded' });
});

test('renders the homepage template structure', async ({ page }) => {
  await expect(page.locator('header.hero h1')).toHaveCount(1);
  await expect(page.locator('#services')).toBeVisible();
  await expect(page.locator('#industries')).toBeVisible();
  await expect(page.locator('#terms')).toBeVisible();
  await expect(page.locator('#pricing')).toBeVisible();
  await expect(page.locator('#founder')).toBeVisible();
  await expect(page.locator('#book')).toBeVisible();
  await expect(page.locator('footer.foot')).toBeVisible();
});

test('keeps approved section order', async ({ page }) => {
  const pairs = [
    ['top', 'ascent'],
    ['ascent', 'services'],
    ['services', 'industries'],
    ['industries', 'terms'],
    ['terms', 'pricing'],
    ['pricing', 'founder'],
    ['founder', 'book'],
  ];
  for (const [before, after] of pairs) {
    const follows = await page.evaluate(({ before, after }) => {
      const first = document.getElementById(before);
      const second = document.getElementById(after);
      return Boolean(first.compareDocumentPosition(second) & Node.DOCUMENT_POSITION_FOLLOWING);
    }, { before, after });
    expect(follows, `${after} should follow ${before}`).toBe(true);
  }
});

test('pricing tiers and phone/CTA content are present', async ({ page }) => {
  await expect(page.locator('.tier')).toHaveCount(3);
  await expect(page.locator('.tier--hot')).toContainText('Growth');
  // Hero CTA row + footer contact — both intentionally link the same number.
  await expect(page.locator('a[href="tel:+18557409608"]')).toHaveCount(2);
});

test('self-hosts its own GSAP/ScrollTrigger, independent of the landing template', async ({ page }) => {
  const scriptSrcs = await page.evaluate(() =>
    Array.from(document.querySelectorAll('script[src]')).map((s) => s.getAttribute('src'))
  );
  expect(scriptSrcs.some((src) => src.includes('gsap-3.13.0.min.js'))).toBe(true);
  expect(scriptSrcs.some((src) => src.includes('ScrollTrigger-3.13.0.min.js'))).toBe(true);
  expect(scriptSrcs.some((src) => src.includes('/homepage.js'))).toBe(true);
  // Never pull in the landing template's own bundle on this page.
  expect(scriptSrcs.some((src) => src.includes('apex-lp'))).toBe(false);
});

test('no heavy editable-originals assets ship to this template', async ({ page }) => {
  const imgSrcs = await page.evaluate(() =>
    Array.from(document.querySelectorAll('img, source')).map((el) => el.getAttribute('src') || el.getAttribute('srcset') || '')
  );
  expect(imgSrcs.some((src) => src.includes('editable-originals'))).toBe(false);
});

test('mobile layout has no horizontal document overflow', async ({ page }, testInfo) => {
  test.skip(testInfo.project.name !== 'mobile');
  const dimensions = await page.evaluate(() => ({
    scroll: document.body.scrollWidth,
    client: document.documentElement.clientWidth,
  }));
  expect(dimensions.scroll).toBeLessThanOrEqual(dimensions.client + 1);
});

test('Landing template remains available and unaffected', async ({ page }) => {
  await page.goto('/apex-landing/', { waitUntil: 'domcontentloaded' });
  await expect(page.locator('main#top h1')).toHaveCount(1);
  await expect(page.locator('#pricing')).toBeVisible();
});
