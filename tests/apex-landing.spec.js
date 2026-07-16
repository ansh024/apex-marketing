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

test('mobile CTA, footer links, logo, and popular guarantee use production styles', async ({ page }) => {
  await page.setViewportSize({ width: 379, height: 863 });
  await page.reload({ waitUntil: 'domcontentloaded' });

  const styles = await page.evaluate(() => {
    const teaser = getComputedStyle(document.querySelector('.form--teaser .btn--signal'));
    const footerLink = getComputedStyle(document.querySelector('.footer nav a'));
    const logo = getComputedStyle(document.querySelector('.nav__logo-img'));
    const guarantee = getComputedStyle(document.querySelector('.plan--popular .plan__guarantee'));
    return {
      teaserBackground: teaser.backgroundColor,
      teaserColor: teaser.color,
      footerColor: footerLink.color,
      footerWeight: footerLink.fontWeight,
      logoWidth: logo.width,
      guaranteeColor: guarantee.color,
    };
  });

  expect(styles.teaserBackground).toBe('rgb(242, 70, 0)');
  expect(styles.teaserColor).toBe('rgb(255, 255, 255)');
  expect(styles.footerColor).toBe('rgb(255, 255, 255)');
  expect(Number(styles.footerWeight)).toBeGreaterThanOrEqual(700);
  expect(parseFloat(styles.logoWidth)).toBeLessThanOrEqual(101);
  expect(styles.guaranteeColor).toBe('rgb(184, 255, 207)');
});

test('guarantee and counters animate only when scrolled into view', async ({ page }) => {
  await expect(page.locator('html')).toHaveClass(/motion-ready/);

  const signature = page.locator('#certSignature');
  await expect(signature).toHaveCSS('clip-path', 'inset(0px 100% 0px 0px)');

  await page.locator('#cert').scrollIntoViewIfNeeded();
  await page.waitForTimeout(3200);
  await expect(signature).toHaveCSS('clip-path', 'inset(0px 0% 0px 0px)');

  const founderStat = page.locator('#founder .stat[data-count="200"]');
  await founderStat.scrollIntoViewIfNeeded();
  await expect(founderStat).toHaveText('200+', { timeout: 3000 });
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
  const pricingColumns = await page.locator('.pricing__grid').evaluate((grid) => getComputedStyle(grid).gridTemplateColumns.split(' ').length);
  expect(pricingColumns).toBe(page.viewportSize().width > 1020 ? 3 : 1);
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
  await expect(page.locator('#faq .faq__item').first().locator('summary')).toHaveText('How fast until we see results?');
  await expect(page.locator('body')).not.toContainText(/\bconsults\b/i);
  await expect(page.locator('#sigPath')).toHaveCount(0);
  await expect(page.locator('#certSignature')).toHaveAttribute('src', /nathan-signature\.svg/);
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

test('process steps use cards and swipe horizontally on mobile', async ({ page }, testInfo) => {
  const list = page.locator('.steps__list');
  const firstCard = list.locator('.step').first();
  const styles = await firstCard.evaluate((element) => {
    const css = getComputedStyle(element);
    return { background: css.backgroundColor, radius: css.borderRadius };
  });
  expect(styles.background).not.toBe('rgba(0, 0, 0, 0)');
  expect(parseFloat(styles.radius)).toBeGreaterThan(0);
  if (testInfo.project.name === 'mobile') {
    const scroll = await list.evaluate((element) => ({ overflowX: getComputedStyle(element).overflowX, scrollWidth: element.scrollWidth, clientWidth: element.clientWidth }));
    expect(scroll.overflowX).toBe('auto');
    expect(scroll.scrollWidth).toBeGreaterThan(scroll.clientWidth);
  }
});

test('pain cards use native horizontal scrolling without desktop pinning', async ({ page }) => {
  const rail = page.locator('#painsPin');
  const track = page.locator('#painsTrack');
  const state = await rail.evaluate((element) => ({
    overflowX: getComputedStyle(element).overflowX,
    scrollWidth: element.scrollWidth,
    clientWidth: element.clientWidth,
  }));
  expect(state.overflowX).toBe('auto');
  expect(state.scrollWidth).toBeGreaterThan(state.clientWidth);
  await expect(track).toHaveCSS('transform', 'none');
});

test('Thank You template remains available', async ({ page }) => {
  await page.goto('/thank-you/', { waitUntil: 'domcontentloaded' });
  await expect(page.locator('.thanks h1')).toContainText("You're booked in");
  await expect(page.locator('.thanks__logo')).toBeVisible();
});
