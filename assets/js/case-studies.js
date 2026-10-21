(function () {
  'use strict';
  const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const palette = ['#b8ece2', '#015eff', '#0142b4', '#151a2c'];

  function noise(x, seed) {
    return Math.sin(x * 2.7 + seed) * .035 + Math.sin(x * 6.4 + seed * 1.7) * .018 + Math.sin(x * 15.1 + seed * .4) * .009;
  }

  function smoothstep(edge0, edge1, x) {
    const t = Math.min(1, Math.max(0, (x - edge0) / (edge1 - edge0)));
    return t * t * (3 - 2 * t);
  }

  /**
   * Hero ascent and footer range share one ridge function but differ in shape.
   * The hero ridge is gated on the left so it starts around the middle of the
   * canvas and leaves the headline, body copy and CTA on clear paper - the
   * selected direction puts the ascent entirely to the right of the copy.
   * The footer range spans the full width, as in the detail-page mock.
   */
  function ridge(u, layer, footer) {
    if (footer) {
      const center = [.18, .54, .78][layer];
      const spread = [.19, .23, .3][layer];
      const peak = Math.exp(-Math.pow((u - center) / spread, 2));
      return [.48, .32, .16][layer] + [.25, .29, .22][layer] * peak + noise(u * 5.2, layer * 8 + 12);
    }
    const gate = smoothstep([.24, .30, .36][layer], [.52, .56, .60][layer], u);
    if (gate <= 0) return 0;
    const center = [.71, .67, .63][layer];
    const spread = [.25, .21, .17][layer];
    const peak = Math.exp(-Math.pow((u - center) / spread, 2));
    const height = [.17, .11, .05][layer] + [.60, .44, .28][layer] * peak;
    return gate * (height + noise(u * 5.2, layer * 8 + 2));
  }

  function paint(canvas) {
    const rect = canvas.getBoundingClientRect();
    if (!rect.width || !rect.height) return;
    // Render at the device pixel grid so the halftone reads as dots rather
    // than as the 4x upscaled blocks the first pass produced.
    const dpr = Math.min(2, window.devicePixelRatio || 1);
    const width = Math.round(rect.width * dpr);
    const height = Math.round(rect.height * dpr);
    canvas.width = width;
    canvas.height = height;
    const ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, width, height);
    const footer = canvas.dataset.relief === 'footer';
    const pitch = Math.max(3, Math.round(3.4 * dpr));
    const dot = Math.max(1, pitch * .44);
    for (let layer = 0; layer < 3; layer += 1) {
      ctx.fillStyle = palette[layer];
      for (let x = 0; x < width; x += pitch) {
        const u = x / width;
        const ridgeHeight = ridge(u, layer, footer);
        if (ridgeHeight <= 0) continue;
        const top = height * (1 - ridgeHeight);
        for (let y = Math.max(0, top | 0); y < height; y += pitch) {
          const threshold = ((x * 13 + y * 7 + layer * 11) % 17) / 17;
          const depth = (y - top) / Math.max(1, height - top);
          if (threshold > .3 + depth * .82) continue;
          ctx.beginPath();
          ctx.arc(x + (((y / pitch) | 0) % 2) * (pitch / 2), y, dot, 0, Math.PI * 2);
          ctx.fill();
        }
      }
    }
  }

  /**
   * Hero motion gradient. Same @firecms/neat configuration as the location
   * pages, so the two share one signature - the palette below is the Apex
   * token set. A hand-written shader was trialled as a lighter, watermark-free
   * replacement (design-research/prototypes/) and measured far cheaper, but it
   * did not match this one's look, so Neat stays. Reduced-motion visitors get
   * the gradient held still rather than removed, so the hero keeps its
   * contrast for white type.
   */
  function initHeroGradient() {
    const mount = document.querySelector('.cs-hero-gradient canvas');
    if (!mount || !window.neat || !window.neat.NeatGradient) return;
    try {
      const gradient = new window.neat.NeatGradient({
        ref: mount,
        colors: [
          { color: '#015EFF', enabled: true },
          { color: '#0142B4', enabled: true },
          { color: '#6354C7', enabled: true },
          { color: '#E086CB', enabled: true },
          { color: '#151A2C', enabled: true }
        ],
        speed: reduce ? 0 : 1.2,
        horizontalPressure: 3, verticalPressure: 4,
        waveFrequencyX: 2, waveFrequencyY: 3, waveAmplitude: 6,
        shadows: 2, highlights: 3,
        colorBrightness: 1, colorSaturation: 3, colorBlending: 6,
        backgroundColor: '#151A2C', backgroundAlpha: 1,
        grainScale: 2, grainIntensity: .35, grainSpeed: reduce ? 0 : 1,
        resolution: 1.2,
        flowEnabled: true, flowScale: 2.6, flowEase: .36,
        shapeType: 'plane', flatShading: true
      });
      // A WebGL loop behind an off-screen hero is wasted battery.
      if ('IntersectionObserver' in window) {
        new IntersectionObserver(function (entries) {
          entries.forEach(function (entry) { gradient.speed = entry.isIntersecting && !reduce ? 1.2 : 0; });
        }, { threshold: 0 }).observe(mount);
      }
    } catch (e) {
      // Leave the solid --paper/#151a2c fallback in place.
    }
  }
  initHeroGradient();

  const canvases = Array.from(document.querySelectorAll('.cs-relief'));
  let resizeTimer;
  function paintAll() { canvases.forEach(paint); }
  paintAll();
  window.addEventListener('resize', function () {
    window.clearTimeout(resizeTimer);
    resizeTimer = window.setTimeout(paintAll, 120);
  }, { passive: true });

  document.querySelectorAll('.cs-video').forEach(function (wrap) {
    const video = wrap.querySelector('video');
    const button = wrap.querySelector('.cs-video__play');
    if (!video || !button) return;
    function sync() { wrap.classList.toggle('is-playing', !video.paused); button.textContent = video.paused ? '▶' : 'Ⅱ'; }
    button.addEventListener('click', function () { if (video.paused) video.play(); else video.pause(); });
    video.addEventListener('click', function () { if (!video.paused) video.pause(); });
    video.addEventListener('play', sync); video.addEventListener('pause', sync); video.addEventListener('ended', sync);
  });

  /* Footer wordmark: a small, self-contained adaptation of the liquid-text
     morph. It pauses off-screen and honours reduced-motion preferences. */
  (function () {
    const root = document.getElementById('footerMorph');
    if (!root) return;
    const [outgoing, incoming] = root.querySelectorAll('span');
    const words = ['ΛPEX', 'MARKETING'];

    function setStatic() {
      outgoing.textContent = words[0];
      outgoing.style.opacity = '1';
      outgoing.style.filter = 'none';
      incoming.style.opacity = '0';
      incoming.style.filter = 'none';
    }

    if (reduce) { setStatic(); return; }

    let index = 0;
    let morph = 0;
    let cooldown = .9;
    let last = performance.now();
    let active = false;
    let frame = 0;

    function render(fraction) {
      const safe = Math.max(fraction, .001);
      const inverse = Math.max(1 - fraction, .001);
      outgoing.textContent = words[index % words.length];
      incoming.textContent = words[(index + 1) % words.length];
      outgoing.style.filter = `blur(${Math.min(8 / inverse - 8, 100)}px)`;
      outgoing.style.opacity = `${Math.pow(inverse, .4)}`;
      incoming.style.filter = `blur(${Math.min(8 / safe - 8, 100)}px)`;
      incoming.style.opacity = `${Math.pow(safe, .4)}`;
    }

    function tick(now) {
      if (!active) return;
      const elapsed = Math.min((now - last) / 1000, .1);
      last = now;
      if (cooldown > 0) {
        cooldown -= elapsed;
        render(0);
      } else {
        morph += elapsed / 1.5;
        const fraction = Math.min(morph, 1);
        render(fraction);
        if (fraction === 1) { index += 1; morph = 0; cooldown = .9; }
      }
      frame = requestAnimationFrame(tick);
    }

    const observer = new IntersectionObserver(([entry]) => {
      active = entry.isIntersecting;
      if (active) { last = performance.now(); cancelAnimationFrame(frame); frame = requestAnimationFrame(tick); }
    }, { threshold:.1 });
    observer.observe(root);
    setStatic();
  })();

  if (!reduce && 'IntersectionObserver' in window) {
    const observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) { if (entry.isIntersecting) entry.target.classList.add('is-visible'); });
    }, { threshold: .12 });
    document.querySelectorAll('.cs-feature,.cs-detail-split,.cs-client-story').forEach(function (el) { observer.observe(el); });
  }
}());
