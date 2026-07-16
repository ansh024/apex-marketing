const { test, expect } = require('@playwright/test');

test.beforeEach(async ({ page }) => {
  await page.goto('/', { waitUntil: 'domcontentloaded' });
});

test('renders the production landing-page structure', async ({ page }) => {
  await expect(page.locator('main#top h1')).toHaveCount(1);
  await expect(page.locator('#services')).toBeVisible();
  await expect(page.locator('#pains')).toBeVisible();
  await expect(page.locator('#proof')).toBeVisible();
  await expect(page.locator('#pricing')).toBeVisible();
  await expect(page.locator('#guarantee')).toBeVisible();
  await expect(page.locator('#founder')).toBeVisible();
  await expect(page.locator('.proof__testimonials')).toHaveCount(0);
});

test('keeps approved section order', async ({ page }) => {
  const pairs = [
    ['hero', 'services'],
    ['services', 'pains'],
    ['pains', 'proof'],
    ['proof', 'pricing'],
    ['pricing', 'guarantee'],
    ['guarantee', 'founder']
  ];
  for (const [before, after] of pairs) {
    const follows = await page.evaluate(({ before, after }) => {
      const first = document.getElementById(before);
      const second = document.getElementById(after);
      return Boolean(first.compareDocumentPosition(second) & Node.DOCUMENT_POSITION_FOLLOWING);
    }, { before, after });
    expect(follows, `#${before} should precede #${after}`).toBe(true);
  }
});

test('every booking CTA opens the single GHL form modal', async ({ page }) => {
  const ctas = page.locator('.cta-book');
  const count = await ctas.count();
  expect(count).toBeGreaterThan(0);
  await expect(ctas).toHaveCount(count);
  const triggerContracts = await ctas.evaluateAll((elements) => elements.map((element) => ({
    tag: element.tagName,
    href: element.getAttribute('href'),
    type: element.getAttribute('type')
  })));
  for (const trigger of triggerContracts) {
    expect(
      (trigger.tag === 'A' && trigger.href === '#book') ||
      (trigger.tag === 'BUTTON' && trigger.type === 'button')
    ).toBe(true);
  }

  const visibleCta = page.locator('.hero__primary.cta-book');
  await visibleCta.click();
  await expect(page.locator('#bookModalOverlay')).toHaveClass(/is-open/);
  await expect(page.locator('#inline-PV33s1v3pTF8y2bzSIIs')).toHaveCount(1);
  await page.locator('#bookModalClose').click();
});

test('renders pricing guarantees and filled benefit icons', async ({ page }) => {
  await expect(page.locator('.plan__guarantee')).toHaveCount(3);
  await expect(page.locator('.pricing__chips li svg')).toHaveCount(6);
  const background = await page.locator('.pricing__chips li svg').first().evaluate((icon) => getComputedStyle(icon).backgroundColor);
  expect(background).not.toBe('rgba(0, 0, 0, 0)');
});

test('contains no long dashes', async ({ page }) => {
  await expect(page.locator('body')).not.toContainText(/[—–]/);
});

test('mobile layout has no horizontal document overflow', async ({ page }, testInfo) => {
  test.skip(testInfo.project.name !== 'mobile');
  const dimensions = await page.evaluate(() => ({
    scroll: document.body.scrollWidth,
    client: document.documentElement.clientWidth
  }));
  expect(dimensions.scroll).toBeLessThanOrEqual(dimensions.client + 1);
});

test('Thank You template remains available', async ({ page }) => {
  await page.goto('/thank-you/', { waitUntil: 'domcontentloaded' });
  await expect(page.locator('.thanks h1')).toContainText("You're booked in");
  await expect(page.locator('.thanks__logo')).toBeVisible();
});
