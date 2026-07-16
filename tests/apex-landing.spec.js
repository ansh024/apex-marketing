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
  await page.goto('/', { waitUntil: 'domcontentloaded' });
});

test('renders the production landing-page structure', async ({ page }) => {
  await expect(page.locator('main#top h1')).toHaveCount(1);
  await expect(page.locator('main#top h1 > .hero__line, main#top h1 > br')).toHaveCount(0);
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

test('loads local animation dependencies and initializes motion', async ({ page }) => {
  const scripts = await page.locator('script#apex-lp-gsap-js, script#apex-lp-scrolltrigger-js, script#apex-lp-lenis-js, script#apex-lp-motion-js').evaluateAll((elements) => elements
    .map((element) => ({
      src: element.src,
      noOptimize: element.getAttribute('data-no-optimize'),
      cfAsync: element.getAttribute('data-cfasync')
    })));

  expect(scripts).toHaveLength(4);
  for (const script of scripts) {
    expect(new URL(script.src).origin).toBe('http://localhost:8892');
    expect(script.noOptimize).toBe('1');
    expect(script.cfAsync).toBe('false');
  }

  await expect(page.locator('html')).toHaveClass(/motion-ready/);
  expect(await page.evaluate(() => window.__motionErrors)).toEqual([]);
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

  const ghlFrame = page.locator('#inline-PV33s1v3pTF8y2bzSIIs');
  await expect(ghlFrame).not.toHaveAttribute('src', /leadconnectorhq/);
  await expect(page.locator('script[data-apex-ghl-embed]')).toHaveCount(0);

  const visibleCta = page.locator('.hero__primary.cta-book');
  await visibleCta.click();
  await expect(page.locator('#bookModalOverlay')).toHaveClass(/is-open/);
  await expect(ghlFrame).toHaveAttribute('src', /leadconnectorhq\.com\/widget\/form/);
  await expect(page.locator('script[data-apex-ghl-embed]')).toHaveCount(1);
  await page.locator('#bookModalClose').click();
});

test('renders pricing guarantees and filled benefit icons', async ({ page }) => {
  await expect(page.locator('.plan__guarantee')).toHaveCount(3);
  await expect(page.locator('.pricing__chips li svg')).toHaveCount(6);
  const background = await page.locator('.pricing__chips li svg').first().evaluate((icon) => getComputedStyle(icon).backgroundColor);
  expect(background).not.toBe('rgba(0, 0, 0, 0)');
});

test('renders the approved revenue-driven content edits', async ({ page }) => {
  await expect(page.locator('.hero__eyebrow')).toHaveText('Revenue Driven Marketing');
  await expect(page.locator('.hero__h1')).toContainText('Engineered To Deliver Results');
  await expect(page.locator('.hero__sub')).toContainText('Omni-Channel Marketing Campaigns Exclusively For Plastic Surgeons & Med Spas');
  await expect(page.locator('.hero__trust')).toContainText('No Long-Term Contracts');
  await expect(page.locator('.steps__list .step')).toHaveCount(4);
  await expect(page.locator('.steps__list')).toContainText('Transparent reporting & communications');
  await expect(page.locator('#faq')).not.toContainText('Who owns the ad accounts, the website, and the data?');
  await expect(page.locator('#faq')).not.toContainText('[PLACEHOLDER');
  await expect(page.locator('#faq')).toContainText('How fast until we see results?');
  await expect(page.locator('body')).not.toContainText(/consults booked/i);
  await expect(page.locator('#sigPath')).toHaveCount(0);
});

test('phone CTAs use the approved number', async ({ page }) => {
  const phoneLinks = page.locator('a[href="tel:+18557409608"]');
  await expect(phoneLinks).toHaveCount(2);
  await expect(phoneLinks.first()).toContainText('(855) 740-9608');
  await expect(phoneLinks.last()).toContainText('(855) 740-9608');
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

test('closed mobile menu is fully hidden and opens from the nav', async ({ page }, testInfo) => {
  test.skip(testInfo.project.name !== 'mobile', 'mobile-only navigation behavior');
  const menu = page.locator('#mobileMenu');
  await expect(menu).toBeHidden();
  await page.locator('#burger').click();
  await expect(menu).toBeVisible();
  await expect(menu).toHaveClass(/open/);
});

test('Thank You template remains available', async ({ page }) => {
  await page.goto('/thank-you/', { waitUntil: 'domcontentloaded' });
  await expect(page.locator('.thanks h1')).toContainText("You're booked in");
  await expect(page.locator('.thanks__logo')).toBeVisible();
});
