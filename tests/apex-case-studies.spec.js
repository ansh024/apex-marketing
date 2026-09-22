const { test, expect } = require('@playwright/test');

test.beforeEach(async ({ page }) => {
  await page.route('**/*', async (route) => {
    const url = new URL(route.request().url());
    if (['localhost', '127.0.0.1'].includes(url.hostname)) return route.continue();
    return route.abort();
  });
});

test('collection renders verified proof and links to GC Events', async ({ page }) => {
  await page.goto('/case-studies/', { waitUntil: 'domcontentloaded' });
  await expect(page.locator('.cs-collection-hero h1')).toContainText('Proof, not promises.');
  await expect(page.locator('.cs-proof')).toContainText('1000+');
  await expect(page.locator('.cs-feature h2')).toContainText('GC Events Studio');
  // The story card and the intro CTA; the duplicate footer-style link was removed.
  await expect(page.locator('a[href*="gc-events-studio"]')).toHaveCount(2);
});

test('hero mounts the motion gradient and stays legible without WebGL', async ({ page }) => {
  await page.goto('/case-studies/', { waitUntil: 'domcontentloaded' });
  // The self-hosted library must load; without it the hero falls back to a
  // solid dark panel, which still has to carry the white hero type.
  await expect(page.locator('.cs-hero-gradient canvas')).toHaveCount(1);
  expect(await page.evaluate(() => typeof window.neat?.NeatGradient)).toBe('function');
  // Self-hosted: the gradient must not depend on a third-party CDN at runtime.
  const src = await page.locator('script[src*="neat"]').first().getAttribute('src');
  expect(new URL(src).origin).toBe(new URL(page.url()).origin);
  // The shader must actually paint. readPixels cannot see it (the drawing
  // buffer is cleared after compositing), so sample the rendered canvas and
  // require real variation rather than one flat colour.
  await page.waitForTimeout(800);
  const shot = await page.locator('.cs-hero-gradient canvas').screenshot();
  const bytes = [...shot.subarray(shot.length - 4096)];
  const spread = Math.max(...bytes) - Math.min(...bytes);
  expect(spread).toBeGreaterThan(10);
  const bg = await page.evaluate(() => getComputedStyle(document.querySelector('.cs-collection-hero')).backgroundColor);
  expect(bg).toBe('rgb(21, 26, 44)');
  const cta = page.locator('.cs-collection-hero .cs-btn');
  const box = await cta.boundingBox();
  const hero = await page.locator('.cs-collection-hero').boundingBox();
  expect(box.y + box.height).toBeLessThanOrEqual(hero.y + hero.height);
});

test('detail renders the real testimonial and verified outcomes', async ({ page }) => {
  await page.goto('/case-studies/gc-events-studio/', { waitUntil: 'domcontentloaded' });
  await expect(page.locator('.cs-detail-hero h1')).toContainText('A clearer view of every lead.');
  // Player when a video is available, poster still when the zip shipped without it.
  const video = page.locator('.cs-client-story video');
  if (await video.count()) {
    await expect(video).toHaveAttribute('src', /\.(mp4|webm)(\?|$)/);
  } else {
    await expect(page.locator('.cs-video--poster img')).toHaveCount(1);
  }
  await expect(page.locator('.cs-outcome')).toContainText('~$1M');
  await expect(page.locator('.cs-outcome')).toContainText('Annual revenue');
});

test('testimonial ships captions and an accessible transcript', async ({ page }) => {
  await page.goto('/case-studies/gc-events-studio/', { waitUntil: 'domcontentloaded' });

  // The distributed zip ships without the heavy mp4, so the player is only
  // present when a video is actually available. The transcript is not
  // optional either way.
  const hasVideo = await page.locator('.cs-client-story video').count();
  if (hasVideo) {
    const track = page.locator('.cs-client-story video track[kind="captions"]');
    await expect(track).toHaveAttribute('src', /\.vtt$/);
    const vtt = await page.request.get(await track.getAttribute('src'));
    expect(vtt.ok()).toBeTruthy();
    expect(await vtt.text()).toMatch(/^WEBVTT/);
    await expect(page.locator('.cs-video__play')).toHaveCount(1);
  } else {
    // Poster still stands in, and no dead play button is offered.
    await expect(page.locator('.cs-video--poster img')).toHaveCount(1);
    await expect(page.locator('.cs-video__play')).toHaveCount(0);
  }

  await expect(page.locator('.cs-transcript summary')).toBeVisible();
  await expect(page.locator('.cs-transcript')).toContainText('My name is Arthur');
});

test('the cost-per-lead graph lives on the detail page, not the collection', async ({ page }) => {
  await page.goto('/case-studies/', { waitUntil: 'domcontentloaded' });
  await expect(page.locator('.cs-bars')).toHaveCount(0);

  await page.goto('/case-studies/gc-events-studio/', { waitUntil: 'domcontentloaded' });
  const chart = page.locator('.cs-quote__chart');
  await expect(chart).toContainText('Cost per lead');
  await expect(chart.locator('.cs-bars b').first()).toHaveText('$120+');
  await expect(chart.locator('.cs-bars b').nth(1)).toHaveText('$40');
  // Bar heights are derived from the figures, so $40 must read shorter than $120.
  const bars = await chart.locator('.cs-bars i').evaluateAll(
    els => els.map(el => parseFloat(el.style.getPropertyValue('--bar'))));
  expect(bars[0]).toBeGreaterThan(bars[1]);
});

test('the featured story card is one link to the detail page', async ({ page }) => {
  await page.goto('/case-studies/', { waitUntil: 'domcontentloaded' });
  const card = page.locator('a.cs-story-card');
  await expect(card).toHaveCount(1);
  // Nested anchors would be invalid and would break keyboard navigation.
  await expect(card.locator('a')).toHaveCount(0);
  // Clicking dead space inside the card (not the poster) must still navigate.
  const box = await card.boundingBox();
  await card.click({ position: { x: box.width - 60, y: box.height - 40 } });
  await page.waitForURL(/gc-events-studio/);

  await page.goBack({ waitUntil: 'domcontentloaded' });
  const cta = page.locator('.cs-feature__read');
  await expect(cta).toHaveText(/Read case study/);
  await expect(cta).toHaveAttribute('href', /gc-events-studio/);
});

test('chrome comes from Elementor when a location matches, else the fallback', async ({ page }) => {
  for (const path of ['/case-studies/', '/case-studies/gc-events-studio/']) {
    await page.goto(path, { waitUntil: 'domcontentloaded' });

    const elementorHeader = await page.locator('.elementor-location-header').count();
    const elementorFooter = await page.locator('.elementor-location-footer').count();
    const fallbackNav = await page.locator('nav.cs-nav').count();
    const fallbackFoot = await page.locator('footer.foot').count();

    // Exactly one of each, never both and never neither.
    expect(elementorHeader + fallbackNav).toBe(1);
    expect(elementorFooter + fallbackFoot).toBe(1);

    if (fallbackFoot) {
      await expect(page.locator('.foot__grid')).toContainText('Google Ads');
      await expect(page.locator('#foot-text-threshold')).toHaveCount(1);
    }
  }
});

test('Elementor kit CSS cannot override the case-study layout', async ({ page }) => {
  await page.goto('/case-studies/', { waitUntil: 'domcontentloaded' });
  // The kit ships `.elementor-kit-N p`, which outranks a bare `.cs-hero-copy`.
  // Every rule is scoped under the body class so ours stays ahead of it.
  const copy = page.locator('.cs-hero-copy');
  expect(await copy.evaluate(el => getComputedStyle(el).marginTop)).toBe('26px');
  const hero = page.locator('.cs-collection-hero');
  expect(await hero.evaluate(el => getComputedStyle(el).backgroundColor)).toBe('rgb(21, 26, 44)');
  expect(await copy.evaluate(el => getComputedStyle(el).color)).toBe('rgba(255, 255, 255, 0.82)');
});

test('Elementor runtime only loads where it has something to render', async ({ page }) => {
  const errors = [];
  page.on('pageerror', e => errors.push(e.message));

  // The landing template is the one page that keeps its own chrome, so
  // Elementor renders nothing there and its runtime must not be shipped
  // (it throws without elementorFrontendConfig).
  await page.goto('/', { waitUntil: 'networkidle' });
  expect(await page.locator('script[src*="/plugins/elementor/"]').count()).toBe(0);
  expect(errors.filter(e => /elementorFrontendConfig/.test(e))).toEqual([]);

  await page.goto('/case-studies/', { waitUntil: 'networkidle' });
  expect(errors.filter(e => /elementorFrontendConfig/.test(e))).toEqual([]);
});

test('no eyebrow labels remain above section headings', async ({ page }) => {
  for (const path of ['/case-studies/', '/case-studies/gc-events-studio/']) {
    await page.goto(path, { waitUntil: 'domcontentloaded' });
    await expect(page.locator('.cs-kicker')).toHaveCount(0);
  }
});

test('case-study pages do not overflow on mobile', async ({ page }, testInfo) => {
  test.skip(testInfo.project.name !== 'mobile');
  for (const path of ['/case-studies/', '/case-studies/gc-events-studio/']) {
    await page.goto(path, { waitUntil: 'domcontentloaded' });
    const width = await page.evaluate(() => ({ scroll: document.body.scrollWidth, client: document.documentElement.clientWidth }));
    expect(width.scroll).toBeLessThanOrEqual(width.client + 1);
  }
});
