/* Apex Marketing — Homepage script
   Ported verbatim (concatenated in original document order) from the seven
   inline <script> blocks in design/prototypes/index.html in apx-page.
   Only the last block depends on gsap/ScrollTrigger — enqueued as deps below,
   so load order is safe regardless of concatenation order. */

/* ============================================================
   APEX — shared render module  (plain script, window.APX)
   file:// blocks ES-module imports, so this attaches to a global.
   Dithered relief imagery. Riso separation, blue-noise ordered
   dither. Accepts EITHER procedural terrain OR an <img> source,
   so Higgsfield-generated plates drop straight in later.
   ============================================================ */
const PALETTE = {
  paper: [238, 238, 238],
  mint:  [184, 236, 226],
  blue:  [  1,  94, 255],
  night: [ 21,  26,  44],
};

/* 4×4 Bayer — parallelisable, therefore portable to a fragment
   shader for production. Floyd–Steinberg is sequential and cannot
   run on a GPU. (T004) */
const BAYER = [0,8,2,10, 12,4,14,6, 3,11,1,9, 15,7,13,5].map(v => v / 16);

/* ---- value noise / ridged fBm ---------------------------------- */
function hash(x, y, s) {
  const n = Math.sin(x * 127.1 + y * 311.7 + s * 74.7) * 43758.5453;
  return n - Math.floor(n);
}
function vnoise(x, y, s) {
  const xi = Math.floor(x), yi = Math.floor(y);
  const xf = x - xi, yf = y - yi;
  const u = xf * xf * (3 - 2 * xf), v = yf * yf * (3 - 2 * yf);
  const a = hash(xi, yi, s),     b = hash(xi + 1, yi, s);
  const c = hash(xi, yi + 1, s), d = hash(xi + 1, yi + 1, s);
  return a + (b - a) * u + (c - a) * v + (a - b - c + d) * u * v;
}
function ridged(x, y, s, oct = 5) {
  let sum = 0, amp = 0.5, f = 1, norm = 0;
  for (let i = 0; i < oct; i++) {
    const n = 1 - Math.abs(vnoise(x * f, y * f, s) * 2 - 1);
    sum += n * n * amp; norm += amp; amp *= 0.5; f *= 2;
  }
  return sum / norm;
}
function fbm(x, y, s, oct = 4) {
  let sum = 0, amp = 0.5, f = 1, norm = 0;
  for (let i = 0; i < oct; i++) {
    sum += vnoise(x * f, y * f, s) * amp; norm += amp; amp *= 0.5; f *= 2;
  }
  return sum / norm;
}

/* ---- ridge silhouette ------------------------------------------
   Returns the top y (0..1 of height) for column u (0..1).      */
function ridgeline(u, opts) {
  const { seed, amp, base, freq, peakU, peakAmp,
          peakW = 0.30, peakSharp = 2, peakSkew = 1, peakCalm = 0 } = opts;
  const n = ridged(u * freq, seed * 3.1, seed, 5);
  /* A dominant summit so the range has an unmistakable APEX.
     peakSharp is the falloff exponent: 2 is a Gaussian — a soft swell that
     reads as a hill. Below ~1.5 the tip resolves into an actual point, which
     is the whole concept. peakSkew widens the lee side only, so the mountain
     has a face and a shoulder instead of mirror symmetry. peakCalm flattens
     the noise toward the tip — a summit still wobbling at full amplitude
     reads as more ridge, not as a peak.
     The defaults are exactly the original Gaussian, so every static plate
     relief on the page is byte-for-byte unchanged. */
  const w = peakW * (u < peakU ? 1 : peakSkew);
  const d = Math.abs(u - peakU) / w;
  const bell = Math.exp(-Math.pow(d, peakSharp));
  return 1 - (base + n * amp * (1 - peakCalm * bell) + bell * peakAmp);
}

/* ---- the relief ------------------------------------------------
   Three ranges, far → near, each denser than the last. Depth reads
   as tone, exactly as it does in a printed separation.           */
function relief(dens, W, H, t, ranges) {
  dens.fill(0);
  for (let r = 0; r < ranges.length; r++) {
    const R = ranges[r];
    const drift = Math.sin(t * 0.00008 * (r + 1)) * R.drift;
    for (let x = 0; x < W; x++) {
      const u = x / W + drift;
      const top = ridgeline(u, R) * H;
      for (let y = Math.max(0, top | 0); y < H; y++) {
        const depth = (y - top) / H;
        // interior texture: slope-ish noise, tightening with depth
        const tex = fbm(x * 0.035 + R.seed * 10, y * 0.035, R.seed, 4);
        let d = R.tone + depth * R.fall + (tex - 0.5) * R.grain;
        // light from upper-left: the lit face of each ridge stays paler
        const lit = fbm(x * 0.012, y * 0.012, R.seed + 5, 3);
        d += (lit - 0.5) * 0.22;
        // painter's order: ranges are drawn far → near, and a near ridge
        // OCCLUDES a far one regardless of which is lighter. Taking a max
        // here would make pale distant ranges punch through dark near ones
        // on an inverted ground.
        dens[y * W + x] = d;
      }
    }
  }
}

/* ---- atmosphere ------------------------------------------------ */
function haze(dens, W, H, t, count, seed) {
  for (let i = 0; i < count; i++) {
    const a = hash(i, 1, seed), b = hash(i, 2, seed), c = hash(i, 3, seed);
    const x = ((a + Math.sin(t * 0.00006 + i) * 0.02) * W) | 0;
    const y = ((0.34 + b * 0.62) * H) | 0;
    if (x < 0 || x >= W || y < 0 || y >= H) continue;
    const rad = 1 + c * 3;
    for (let dy = -rad; dy <= rad; dy++) for (let dx = -rad; dx <= rad; dx++) {
      const px = x + dx, py = y + dy;
      if (px < 0 || px >= W || py < 0 || py >= H) continue;
      const f = 1 - Math.hypot(dx, dy) / (rad + 0.001);
      if (f <= 0) continue;
      const i2 = py * W + px;
      dens[i2] = Math.max(dens[i2], f * 0.30 * (0.4 + c));
    }
  }
}

/* ---- separation ------------------------------------------------
   Quantise a density field to riso levels through the ordered
   dither. `levels` is [threshold, rgb] low → high.              */
function separateLayer(img, dens, W, H, levels, dither = 0.30) {
  const d = img.data;
  for (let y = 0; y < H; y++) for (let x = 0; x < W; x++) {
    const i = y * W + x, o = i * 4;
    const q = dens[i] + (BAYER[(y & 3) * 4 + (x & 3)] - 0.5) * dither;
    let c = null;
    for (let L = levels.length - 1; L >= 0; L--) { if (q > levels[L][0]) { c = levels[L][1]; break; } }
    if (!c) { d[o+3] = 0; continue; }
    d[o] = c[0]; d[o+1] = c[1]; d[o+2] = c[2]; d[o+3] = 255;
  }
}
function separate(img, dens, W, H, levels, ground) {
  const d = img.data;
  for (let y = 0; y < H; y++) {
    for (let x = 0; x < W; x++) {
      const i = y * W + x;
      const q = dens[i] + (BAYER[(y & 3) * 4 + (x & 3)] - 0.5) * 0.30;
      let c = ground;
      for (let L = levels.length - 1; L >= 0; L--) {
        if (q > levels[L][0]) { c = levels[L][1]; break; }
      }
      const o = i * 4;
      d[o] = c[0]; d[o + 1] = c[1]; d[o + 2] = c[2]; d[o + 3] = 255;
    }
  }
}

/* ---- image source ----------------------------------------------
   Drop-in path for Higgsfield plates: sample any loaded image into
   the same density field, then run the identical separation. The
   look is the screen, not the source — so generated, photographic
   and procedural material all land in the same world. (L18)     */
function densityFromImage(dens, W, H, imgCanvasCtx) {
  const src = imgCanvasCtx.getImageData(0, 0, W, H).data;
  for (let i = 0; i < W * H; i++) {
    const o = i * 4;
    dens[i] = (src[o] * 0.2126 + src[o + 1] * 0.7152 + src[o + 2] * 0.0722) / 255;
  }
}

window.APX = { PALETTE, ridged, fbm, ridgeline, relief, haze, separate, separateLayer, densityFromImage };


/* ============================================================
   APEX — page behaviour. One rAF loop drives everything that is
   scroll-linked, so the page has a single motion clock rather
   than five competing ones.
   ============================================================ */
(function () {
  const A = window.APX, P = A.PALETTE;
  const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const LEV_PAPER = [[.30, P.mint], [.56, P.blue], [.82, P.night]];

  /* ---- static relief plates ------------------------------------ */
  const PLATES = {
    fam:  [{seed:41,base:.52,amp:.10,freq:3.8,peakU:.30,peakAmp:.09,tone:.34,fall:.10,grain:.15,drift:0},
           {seed:53,base:.40,amp:.12,freq:5.4,peakU:.78,peakAmp:.07,tone:.62,fall:.12,grain:.18,drift:0}],
    ind:  [{seed:83,base:.50,amp:.10,freq:3.6,peakU:.62,peakAmp:.08,tone:.32,fall:.10,grain:.15,drift:0},
           {seed:97,base:.34,amp:.11,freq:5.6,peakU:.22,peakAmp:.06,tone:.60,fall:.13,grain:.18,drift:0}],
    red:  [{seed:113,base:.48,amp:.09,freq:3.2,peakU:.34,peakAmp:.07,tone:.32,fall:.10,grain:.15,drift:0},
           {seed:127,base:.32,amp:.10,freq:5.2,peakU:.80,peakAmp:.06,tone:.60,fall:.13,grain:.18,drift:0}],
    portrait:[{seed:151,base:.30,amp:.14,freq:2.6,peakU:.48,peakAmp:.16,tone:.44,fall:.16,grain:.22,drift:0},
           {seed:163,base:.12,amp:.10,freq:4.4,peakU:.30,peakAmp:.08,tone:.74,fall:.18,grain:.24,drift:0}],
    pc0:  [{seed:61,base:.44,amp:.11,freq:4.2,peakU:.24,peakAmp:.08,tone:.30,fall:.12,grain:.17,drift:0},
           {seed:71,base:.30,amp:.09,freq:6.0,peakU:.70,peakAmp:.05,tone:.58,fall:.14,grain:.19,drift:0}],
    pc1:  [{seed:74,base:.44,amp:.11,freq:4.2,peakU:.42,peakAmp:.08,tone:.30,fall:.12,grain:.17,drift:0},
           {seed:84,base:.30,amp:.09,freq:6.0,peakU:.58,peakAmp:.05,tone:.58,fall:.14,grain:.19,drift:0}],
    pc2:  [{seed:87,base:.44,amp:.11,freq:4.2,peakU:.60,peakAmp:.08,tone:.30,fall:.12,grain:.17,drift:0},
           {seed:97,base:.30,amp:.09,freq:6.0,peakU:.46,peakAmp:.05,tone:.58,fall:.14,grain:.19,drift:0}],
    pc3:  [{seed:100,base:.44,amp:.11,freq:4.2,peakU:.78,peakAmp:.08,tone:.30,fall:.12,grain:.17,drift:0},
           {seed:110,base:.30,amp:.09,freq:6.0,peakU:.34,peakAmp:.05,tone:.58,fall:.14,grain:.19,drift:0}],
  };
  /* the closing section is on night — aerial perspective inverts:
     distant ridges pale, near ridges near-black silhouettes */
  const BOOK_R = [
    {seed:181,base:.30,amp:.075,freq:3.2,peakU:.72,peakAmp:.07,tone:.54,fall:-.05,grain:.10,drift:0},
    {seed:193,base:.16,amp:.090,freq:4.8,peakU:.66,peakAmp:.10,tone:.26,fall:-.04,grain:.11,drift:0},
    {seed:199,base:.03,amp:.070,freq:6.4,peakU:.60,peakAmp:.05,tone:.09,fall:-.03,grain:.09,drift:0},
  ];
  const LEV_NIGHT = [[.22, P.blue], [.52, P.mint], [.80, P.paper]];

  function paint(cv, ranges, levels, ground, px) {
    const r = cv.getBoundingClientRect();
    if (!r.width || !r.height) return;
    const W = Math.max(1, r.width / (px || 4) | 0), H = Math.max(1, r.height / (px || 4) | 0);
    cv.width = W; cv.height = H;
    const dens = new Float32Array(W * H);
    A.relief(dens, W, H, 0, ranges);
    const ctx = cv.getContext('2d');
    const img = ctx.createImageData(W, H);
    if (ground) A.separate(img, dens, W, H, levels, ground);
    else A.separateLayer(img, dens, W, H, levels);   // transparent ground
    ctx.putImageData(img, 0, 0);
  }
  function dotmark() {
    const cv = document.getElementById('dotmark');
    if (!cv) return;
    const r = cv.getBoundingClientRect();
    if (!r.width || !r.height) return;
    // sample the wordmark at text resolution, print it as an AM halftone:
    // dot SIZE carries the letterform — the footer is literally a print
    const cell = Math.max(7, Math.round(r.width / 110));
    const W = Math.round(r.width), H = Math.round(r.height);
    cv.width = W; cv.height = H;
    const off2 = document.createElement('canvas');
    const gw = Math.ceil(W / cell), gh = Math.ceil(H / cell);
    off2.width = gw; off2.height = gh;
    const octx2 = off2.getContext('2d', { willReadFrequently: true });
    octx2.fillStyle = '#000'; octx2.fillRect(0, 0, gw, gh);
    octx2.fillStyle = '#fff';
    octx2.textBaseline = 'middle'; octx2.textAlign = 'center';
    octx2.font = '700 ' + Math.floor(gh * 1.06) + 'px Arimo, Arial, sans-serif';
    octx2.fillText('Apex', gw / 2, gh * 0.58);
    const d = octx2.getImageData(0, 0, gw, gh).data;
    const ctx2 = cv.getContext('2d');
    ctx2.clearRect(0, 0, W, H);
    ctx2.fillStyle = '#EEEEEE';
    for (let gy = 0; gy < gh; gy++) for (let gx = 0; gx < gw; gx++) {
      const v = d[(gy * gw + gx) * 4] / 255;
      if (v < 0.08) continue;
      const rad = (cell * 0.46) * Math.sqrt(v);
      ctx2.beginPath();
      ctx2.arc(gx * cell + cell / 2, gy * cell + cell / 2, rad, 0, 6.284);
      ctx2.fill();
    }
  }

  function paintAll() {
    dotmark();
    document.querySelectorAll('[data-relief]').forEach(cv => {
      const k = cv.dataset.relief;
      if (k === 'book') paint(cv, BOOK_R, LEV_NIGHT, P.night, 4);
      else if (PLATES[k]) paint(cv, PLATES[k], LEV_PAPER, P.paper, k.startsWith('pc') ? 5 : 4);
    });
  }

  /* ---- hero relief — THREE INDEPENDENT LAYERS -------------------
     Each depth range renders to its own canvas with a transparent
     ground, so the layers can be translated independently on pointer
     move. One combined bitmap could not parallax by depth, and
     recomputing the relief per pointer event is far too expensive
     (~18ms a call). CSS transforms on pre-rendered layers are free. */
  const apexEl = document.getElementById('apexMark');
  /* ONE mountain seen as three depth bands, not three separate ranges that
     happen to overlap. All three summits sit at roughly the same u, so the
     silhouettes stack into a single peak with a face, a shoulder and a
     foreground spur — which is what the sketch asks for. The far band is
     tallest (it is the skyline) and carries the apex marker.
     drift is 0 on the far band: the marker is positioned once at peakU, so
     a drifting summit would slide out from under its own annotation. */
  const HERO_R = [
    {seed:3, base:.30,amp:.050,freq:3.1,peakU:.64,peakAmp:.40,peakW:.18,peakSharp:1.30,peakSkew:1.35,peakCalm:.72,tone:.34,fall:.10,grain:.13,drift:0},
    {seed:11,base:.21,amp:.070,freq:4.4,peakU:.60,peakAmp:.30,peakW:.20,peakSharp:1.45,peakSkew:1.30,peakCalm:.60,tone:.58,fall:.14,grain:.20,drift:.005},
    {seed:23,base:.09,amp:.075,freq:6.2,peakU:.55,peakAmp:.18,peakW:.25,peakSharp:1.60,peakSkew:1.25,peakCalm:.45,tone:.84,fall:.16,grain:.22,drift:.003},
  ];
  const HL = [0,1,2].map(i => document.getElementById('hl' + i));
  /* The summit is defined in u-space, so a portrait viewport stretches the
     same curve into a spire that drives straight through the headline. Scale
     the peak back and settle the mass lower as the canvas narrows, so the
     mountain stays a horizon on a phone instead of a wall. */
  let HERO_EFF = HERO_R;
  function heroRanges(w, h) {
    const ar = w / h;                                   // ~1.2 desktop, ~0.46 phone
    const k = Math.min(1, Math.max(0.40, (ar - 0.30) / 0.90));
    if (k >= 0.999) return HERO_R;
    return HERO_R.map(R => ({ ...R,
      peakAmp: R.peakAmp * k,
      peakW:   R.peakW * (1 + (1 - k) * 0.55),          // broader, less spire-like
      base:    R.base * (0.74 + 0.26 * k) }));
  }
  let hW=0,hH=0,hDens=null,hLayers=null;
  /* energy field for the pixel scatter — shared across layers, decays per
     frame, so the cursor's trail has memory */
  let hE=null, hBox=null, hPrev=null;

  function heroResize() {
    if (!HL[0]) return;
    const r = HL[0].getBoundingClientRect();
    if (!r.width || !r.height) return;
    hW = Math.max(1, r.width/4|0); hH = Math.max(1, r.height/4|0);
    hDens = new Float32Array(hW*hH);
    hE = new Float32Array(hW*hH); hBox = null; hPrev = null;
    hLayers = HL.map(cv => { cv.width=hW; cv.height=hH;
      const src = document.createElement('canvas'); src.width=hW; src.height=hH;
      const sctx = src.getContext('2d', { willReadFrequently: true });
      return { dctx: cv.getContext('2d'), src, sctx, img: sctx.createImageData(hW,hH) }; });
    HERO_EFF = heroRanges(r.width, r.height);
    // the marker belongs on the skyline — that is band 0, the tallest
    const S = HERO_EFF[0];
    apexEl.style.left = (S.peakU * r.width) + 'px';
    apexEl.style.top = (A.ridgeline(S.peakU, S) * r.height) + 'px';
    heroDraw(0);
  }

  function heroDraw(t) {
    if (!hLayers) return;
    for (let i = 0; i < 3; i++) {
      A.relief(hDens, hW, hH, t, [HERO_EFF[i]]);
      if (i === 0) A.haze(hDens, hW, hH, t, 200, 7);   // atmosphere sits furthest back
      A.separateLayer(hLayers[i].img, hDens, hW, hH, LEV_PAPER);
      hLayers[i].sctx.putImageData(hLayers[i].img, 0, 0);
      hLayers[i].dctx.clearRect(0, 0, hW, hH);
      hLayers[i].dctx.drawImage(hLayers[i].src, 0, 0);
    }
  }

  const hero = document.querySelector('.hero');

  /* ---- the sweep: the black section rises through the hero ----------
     Reference behaviour (Shopify Editions): the incoming section does not
     meet the outgoing one on a straight line. It rises behind a wide curved
     crest, and the outgoing image shreds into particles along that crest
     rather than being cut by it.

     Ours is already a dithered print, so the dots ARE the particles — this
     runs in the same canvas pipeline as the relief instead of pulling in
     Three.js. The motion spec's rule applies: reach for a library only when
     the effect provably cannot be done natively, and this one can.

     Scroll-driven with NO added scroll length: progress is just how far the
     hero has left the viewport, so the wave costs the reader nothing. */
  const sweep = document.getElementById('sweep');
  const sCtx = sweep ? sweep.getContext('2d') : null;
  const heroLead = document.querySelector('.hero__lead');
  let sW = 0, sH = 0, sImg = null, sLastP = -1;
  const BAY4 = [0,8,2,10, 12,4,14,6, 3,11,1,9, 15,7,13,5].map(v => v / 16);
  function h2(x, y) { const n = Math.sin(x*127.1 + y*311.7) * 43758.5453; return n - Math.floor(n); }

  function sweepResize() {
    if (!sweep) return;
    const r = sweep.getBoundingClientRect();
    if (!r.width || !r.height) return;
    sW = Math.max(1, r.width/4|0); sH = Math.max(1, r.height/4|0);
    sweep.width = sW; sweep.height = sH;
    sImg = sCtx.createImageData(sW, sH);
    sLastP = -1;
  }

  /* The hero is scrolling up at the same time, so the wave only needs a small
     EXTRA rise to overtake it. Crest position in the viewport works out to
     H*(1 - p - rise); solving for that reaching 0 at p≈0.8 puts the total
     rise around 0.25, not the 1.28 a naive "cover the whole section" reading
     gives — that raced the wave to full black by half a screen. */
  function sweepGeom(p) {
    const e = p*p*(3 - 2*p);
    return { rise:  0.085 + 0.270 * e,     // crest height, fraction of hero
             curve: 0.020 + 0.260 * e,     // how far the shoulders trail it
             band:  0.014 + 0.060 * e };   // depth of the dissolving edge
  }

  function sweepDraw(p) {
    if (!sImg) return;
    const d = sImg.data;
    const G = sweepGeom(p);
    const rise = G.rise, curve = G.curve, band = G.band * sH;
    for (let x = 0; x < sW; x++) {
      const u = x / sW;
      // crest sits between centre and the summit, so the peak goes under last
      const g = Math.exp(-Math.pow((u - 0.58) / 0.50, 2));
      const yTop = sH * (1 - rise) + sH * curve * (1 - g);
      for (let y = 0; y < sH; y++) {
        const o = (y*sW + x) * 4;
        let a = 0;
        if (y >= yTop) a = 255;
        else {
          const dd = (yTop - y) / band;
          if (dd < 1) {                          // ordered dither through the edge
            const t = 1 - dd;
            if (t * 1.18 > BAY4[(y & 3)*4 + (x & 3)] * 0.6 + h2(x*1.7, y*1.7) * 0.5) a = 255;
          } else if (dd < 2.8) {                 // grains thrown ahead of the crest
            if (h2(x*3.1 + 7, y*3.1 + 3) < ((2.8 - dd) / 1.8) * 0.17) a = 255;
          }
        }
        d[o] = 0; d[o+1] = 0; d[o+2] = 0; d[o+3] = a;
      }
    }
    sCtx.putImageData(sImg, 0, 0);
  }

  function sweepTick() {
    if (!hero || !sImg) return;
    const r = hero.getBoundingClientRect();
    if (r.bottom < 0 || r.top > innerHeight) return;
    const p = reduce ? 0 : Math.min(1, Math.max(0, -r.top / r.height));
    if (Math.abs(p - sLastP) > 0.0015) { sweepDraw(p); sLastP = p; }
    /* No opacity ramp on the copy — an earlier version faded it on raw scroll
       progress, which dimmed the headline while the crest was still far below
       it. The headline just scrolls away; at the current tuning the crest
       never actually reaches it (needs rise 0.383, max is 0.355).
       This stays as a guard, not decoration: the margin is thin, and if the
       rise is ever raised the buttons would become invisible tab stops.
       Measured against .hero__lead — .hero__body is a full-height flex child,
       so testing its top could never fire. */
    if (heroLead) {
      const crest = r.top + r.height * (1 - sweepGeom(p).rise);
      const b = heroLead.getBoundingClientRect();
      heroLead.style.visibility = (!reduce && crest <= b.top) ? 'hidden' : '';
    }
  }

  function deposit(cx, cy) {
    const Rd = 13;
    const x0 = Math.max(0, (cx-Rd)|0), y0 = Math.max(0, (cy-Rd)|0);
    const x1 = Math.min(hW-1, (cx+Rd)|0), y1 = Math.min(hH-1, (cy+Rd)|0);
    for (let y=y0; y<=y1; y++) for (let x=x0; x<=x1; x++) {
      const d = Math.hypot(x-cx, y-cy);
      if (d >= Rd) continue;
      const f = 1 - d/Rd, i = y*hW + x;
      if (f > hE[i]) hE[i] = f;
    }
    if (!hBox) hBox = [x0,y0,x1,y1];
    else { hBox[0]=Math.min(hBox[0],x0); hBox[1]=Math.min(hBox[1],y0);
           hBox[2]=Math.max(hBox[2],x1); hBox[3]=Math.max(hBox[3],y1); }
  }
  if (hero && !reduce) {
    hero.addEventListener('pointermove', e => {
      if (!hE || !HL[1]) return;
      const cr = HL[1].getBoundingClientRect();
      const sc = hW / cr.width;
      const cx = (e.clientX - cr.left) * sc, cy = (e.clientY - cr.top) * sc;
      if (hPrev) {   // stamp along the whole path, not just event samples
        const steps = Math.max(1, Math.hypot(cx-hPrev[0], cy-hPrev[1]) / 4 | 0);
        for (let s2=1; s2<=steps; s2++)
          deposit(hPrev[0]+(cx-hPrev[0])*s2/steps, hPrev[1]+(cy-hPrev[1])*s2/steps);
      } else deposit(cx, cy);
      hPrev = [cx, cy];
    }, { passive: true });
    hero.addEventListener('pointerleave', () => { hPrev = null; }, { passive: true });
  }

  /* THE SCATTER — from the Zamp reference: dots the cursor touches are
     kicked off the grid, the trail stays disturbed, then settles back.
     Zamp colours disturbed dots iridescent; the riso equivalent is
     MISREGISTRATION — while energetic, blue and mint slip separations.
     Warping the pre-rendered sources costs ~1ms; recomputing the relief
     is 18ms. Production home for this is a fragment shader. */
  const SCAT = [4, 7, 11];   // kick distance per layer — near reacts most
  function heroScatter(t) {
    if (!hLayers || !hBox) return;
    let alive = false;
    const bx0=hBox[0], by0=hBox[1], bx1=hBox[2], by1=hBox[3];
    for (let y=by0; y<=by1; y++) for (let x=bx0; x<=bx1; x++) {
      const i = y*hW + x;
      if (hE[i] > 0) { hE[i] *= 0.90; if (hE[i] < 0.02) hE[i] = 0; else alive = true; }
    }
    if (!alive) {   // settled — one clean blit, stand down
      for (const L of hLayers) { L.dctx.clearRect(0,0,hW,hH); L.dctx.drawImage(L.src,0,0); }
      hBox = null; return;
    }
    const w = bx1-bx0+1, h2 = by1-by0+1;
    for (let li=0; li<3; li++) {
      const L = hLayers[li];
      L.dctx.clearRect(0,0,hW,hH); L.dctx.drawImage(L.src,0,0);
      const sd = L.sctx.getImageData(bx0,by0,w,h2).data;
      const out = L.dctx.getImageData(bx0,by0,w,h2), od = out.data;
      for (let y=0; y<h2; y++) for (let x=0; x<w; x++) {
        const e = hE[(by0+y)*hW + (bx0+x)];
        if (e <= 0) continue;
        // stable per-grain direction + slow wobble — vibrate, don't smear
        const n = Math.sin((bx0+x)*127.1 + (by0+y)*311.7) * 43758.5453;
        const fr = n - Math.floor(n);
        const ang = fr*6.283 + Math.sin(t*0.004 + fr*6.283)*0.7;
        const s = Math.round(e * SCAT[li]);
        if (!s) continue;
        let sx = x - Math.round(Math.cos(ang)*s), sy = y - Math.round(Math.sin(ang)*s);
        sx = Math.min(w-1, Math.max(0, sx)); sy = Math.min(h2-1, Math.max(0, sy));
        const so = (sy*w+sx)*4, oo = (y*w+x)*4;
        let r2=sd[so], g2=sd[so+1], b2=sd[so+2];
        const a2=sd[so+3];
        if (e > 0.30 && a2) {   // misregistration
          if (b2===255 && r2===1)       { r2=184; g2=236; b2=226; }   // blue → mint
          else if (r2===184 && b2===226){ r2=1;   g2=94;  b2=255; }   // mint → blue
        }
        od[oo]=r2; od[oo+1]=g2; od[oo+2]=b2; od[oo+3]=a2;
      }
      L.dctx.putImageData(out, bx0, by0);
    }
  }
  /* ---- ascent: generated footage through the riso separation -------
     The Higgsfield plume samples into the same night separation as
     everything else (L18: the screen is the look, not the source).
     The reveal is the Spring-reference streak: the frame smears
     radially and resolves sharp over the first beat of the pin —
     executed inside the dither, not as a CSS filter. */
  const asc = document.getElementById('ascent');
  const ascPin = asc?.querySelector('.ascent__pin');
  const climb = document.getElementById('climb');
  const plumeV = document.getElementById('plumeSrc');
  const ascMedia = document.querySelector('.ascent__media');
  const ascWords = [...document.querySelectorAll('#ascentReveal .word')];
  const cCtx = climb ? climb.getContext('2d') : null;
  const clipTune = { dither:0.36, mix:0.50, cutoff:0.07 };
  const vcv = document.createElement('canvas');
  const vctx = vcv.getContext('2d', { willReadFrequently: true });
  const LEV_CLIP = [[.14, P.blue], [.52, P.mint], [.86, P.paper]];
  /* PX 2, not 4 — halves the pixel size, so the rocket reads as a craft
     rather than a blue smear. The source is downsampled to 560px wide, which
     is still ~3x what this canvas needs, so nothing is lost by sampling finer. */
  const A_PX = 1;
  let aW=0, aH=0, aDens=null, aImg=null, aLast=0, aDrawn=false;
  /* The streak reveal used to be keyed to scroll position inside the pin.
     With the pin gone there is no scroll range to key it to, so it runs once
     on time from the moment the section first comes into view. */
  let aEnter = 0;
  const easeS = x => x<0?0:x>1?1:x*x*(3-2*x);

  function ascResize() {
    if (!climb) return;
    const r = climb.getBoundingClientRect();
    if (!r.width || !r.height) return;
    aW = Math.max(1, r.width/A_PX|0); aH = Math.max(1, r.height/A_PX|0);
    climb.width=aW; climb.height=aH; vcv.width=aW; vcv.height=aH;
    aDens = new Float32Array(aW*aH); aImg = cCtx.createImageData(aW,aH);
    aDrawn = false;
    starsResize();
  }

  function ascDraw(prog) {
    if (!cCtx || !aDens || !plumeV || plumeV.readyState < 2) return;
    const vw = plumeV.videoWidth, vh = plumeV.videoHeight;
    if (!vw) return;
    const sc2 = Math.max(aW/vw, aH/vh), dw = vw*sc2, dh = vh*sc2;
    vctx.globalAlpha = 1;
    vctx.drawImage(plumeV, (aW-dw)/2, (aH-dh)/2, dw, dh);
    const streak = 1 - easeS(prog / 0.14);
    if (streak > 0.01) {
      for (let k=1; k<=4; k++) {
        const z = 1 + k*0.13*streak;
        vctx.globalAlpha = 0.28*streak;
        vctx.drawImage(vcv, aW/2-(aW*z)/2, aH/2-(aH*z)/2, aW*z, aH*z);
      }
      vctx.globalAlpha = 1;
    }
    const d = vctx.getImageData(0,0,aW,aH).data, out = aImg.data;
    /* Continuous blue grade, with an adjustable ordered-dither layer over it.
       The tuning panel makes the balance reviewable live rather than baking in
       an arbitrary amount of pixel texture. */
    for (let i=0,p2=0;i<aW*aH;i++,p2+=4) {
      const lum = (d[p2]*0.2126 + d[p2+1]*0.7152 + d[p2+2]*0.0722) / 255;
      if (lum < clipTune.cutoff) { out[p2+3] = 0; continue; }
      const r = Math.min(255, d[p2] * 0.14 + d[p2+1] * 0.05);
      const g = Math.min(255, d[p2+1] * 0.68 + d[p2+2] * 0.10);
      const b = Math.min(255, d[p2+2] * 0.90 + d[p2+1] * 0.24);
      const q = lum + (BAYER[((i / aW | 0) & 3) * 4 + (i & 3)] - 0.5) * clipTune.dither;
      let ink = null;
      for (let L=LEV_CLIP.length-1; L>=0; L--) if (q > LEV_CLIP[L][0]) { ink = LEV_CLIP[L][1]; break; }
      out[p2]   = ink ? r * (1-clipTune.mix) + ink[0] * clipTune.mix : r;
      out[p2+1] = ink ? g * (1-clipTune.mix) + ink[1] * clipTune.mix : g;
      out[p2+2] = ink ? b * (1-clipTune.mix) + ink[2] * clipTune.mix : b;
      out[p2+3] = 255;
    }
    cCtx.putImageData(aImg, 0, 0);
    aDrawn = true;
  }

  /* ---- the starfield ---------------------------------------------
     The shared component for this was a React/motion box-shadow layer; this
     repo is a static Elementor prototype with no React, so the same idea runs
     in the canvas pipeline instead — three parallax depths drifting down so
     the rocket reads as climbing. Positions are hashed, not stored, so there
     is no array to keep in sync on resize. */
  const starsCv = document.getElementById('stars');
  const stCtx = starsCv ? starsCv.getContext('2d') : null;
  /* Counts are a DENSITY, not a fixed number — this canvas is the whole
     section, so it changes size with the viewport and fixed counts would read
     as a crowd on a phone and an empty sky on a wide monitor. */
  const ST_LAYERS = [
    { d: 0.00160, speed: 0.0060, tone: [ 90,  96, 120] },   // far, dim, slow
    { d: 0.00072, speed: 0.0130, tone: [150, 158, 190] },
    { d: 0.00030, speed: 0.0240, tone: [232, 236, 245] },   // near, bright, fast
  ];
  let stW = 0, stH = 0, stImg = null, stN = [0,0,0];

  function starsResize() {
    if (!starsCv) return;
    const r = starsCv.getBoundingClientRect();
    if (!r.width || !r.height) return;
    stW = Math.max(1, r.width/A_PX|0); stH = Math.max(1, r.height/A_PX|0);
    starsCv.width = stW; starsCv.height = stH;
    stImg = stCtx.createImageData(stW, stH);
    const area = stW * stH;
    stN = ST_LAYERS.map(L => Math.min(900, Math.round(area * L.d)));
  }

  function starsDraw(t) {
    if (!stImg) return;
    const d = stImg.data;
    d.fill(0);
    for (let L = 0; L < ST_LAYERS.length; L++) {
      const { speed, tone } = ST_LAYERS[L];
      for (let i = 0, n = stN[L]; i < n; i++) {
        const hx = Math.sin(i*127.1 + L*311.7) * 43758.5453;
        const hy = Math.sin(i*269.5 + L*183.3) * 43758.5453;
        // The rocket travels toward the upper-right, so the field sweeps
        // diagonally down-left past it — a forward-motion cue rather than
        // a simple vertical falling-stars loop.
        let x = (hx - Math.floor(hx)) * stW - t * speed * 0.90;
        x = ((x % stW) + stW) % stW | 0;
        let y = (hy - Math.floor(hy)) * stH + t * speed;
        y = ((y % stH) + stH) % stH | 0;
        if (x < 0 || x >= stW || y < 0 || y >= stH) continue;
        const o = (y*stW + x) * 4;
        d[o] = tone[0]; d[o+1] = tone[1]; d[o+2] = tone[2]; d[o+3] = 255;
        if (L === 2 && x+1 < stW) {          // the near layer gets a little mass
          const o2 = o + 4;
          d[o2] = tone[0]; d[o2+1] = tone[1]; d[o2+2] = tone[2]; d[o2+3] = 255;
        }
      }
    }
    stCtx.putImageData(stImg, 0, 0);
  }

  if (plumeV) {
    plumeV.loop = true;
    plumeV.addEventListener('ended', () => { plumeV.currentTime = 0; plumeV.play().catch(()=>{}); });
    if (reduce) {
      plumeV.addEventListener('loadeddata', () => { try { plumeV.currentTime = 1.2; } catch(e){} }, {once:true});
      plumeV.addEventListener('seeked', () => { ascDraw(1); starsDraw(0); }, {once:true});
    } else if ('IntersectionObserver' in window && asc) {
      new IntersectionObserver(es => es.forEach(ev =>
        ev.isIntersecting ? plumeV.play().catch(()=>{}) : plumeV.pause()
      ), {rootMargin:'160px'}).observe(asc);
    }
    plumeV.load();
  }

  /* ---- redline: one open clause, on a dwell timer ------------------
     The reference advances on a timer. That needs a pause control to
     clear WCAG 2.2.2, so: pause button, pause on hover, pause on
     keyboard focus inside the stack, and no timer at all under
     prefers-reduced-motion — the accordion is then click-only. */
  (function () {
    const stack = document.getElementById('clauses');
    if (!stack) return;
    const rows  = [...stack.querySelectorAll('.clause')];
    const arts  = [...stack.querySelectorAll('.receipt')];
    const bars  = rows.map(r => r.querySelector('.clause__bar i'));
    const play  = document.getElementById('redPlay');
    const DWELL = 6500;

    let at = 0, t0 = 0, held = 0, paused = reduce, hover = false, seen = false, raf = 0;

    function open(n) {
      at = (n + rows.length) % rows.length;
      rows.forEach((row, k) => {
        const on = k === at;
        row.classList.toggle('is-on', on);
        row.querySelector('.clause__hd').setAttribute('aria-expanded', on ? 'true' : 'false');
        if (bars[k]) bars[k].style.transform = 'scaleX(0)';
      });
      arts.forEach((art, k) => art.classList.toggle('is-on', k === at));
      t0 = performance.now();
    }

    function label() {
      if (!play) return;
      play.classList.toggle('is-paused', paused);
      play.querySelector('span').textContent = paused ? 'Play' : 'Pause';
      play.setAttribute('aria-label', (paused ? 'Play' : 'Pause') + ' the terms carousel');
    }

    function tick(now) {
      raf = requestAnimationFrame(tick);
      const running = seen && !paused && !hover;
      if (!running) { t0 = now - held; return; }
      held = now - t0;
      const p = Math.min(1, held / DWELL);
      if (bars[at]) bars[at].style.transform = 'scaleX(' + p.toFixed(4) + ')';
      if (p === 1) { open(at + 1); held = 0; }
    }

    rows.forEach((row, k) => {
      row.querySelector('.clause__hd').addEventListener('click', () => {
        if (k === at && !paused) { paused = true; label(); return; }
        open(k); held = 0;
      });
    });

    stack.addEventListener('pointerenter', () => { hover = true; });
    stack.addEventListener('pointerleave', () => { hover = false; });
    stack.addEventListener('focusin',  () => { hover = true; });
    stack.addEventListener('focusout', () => { hover = false; });

    if (play) play.addEventListener('click', () => { paused = !paused; label(); });

    new IntersectionObserver(e => { seen = e[0].isIntersecting; },
      { threshold: 0.15 }).observe(stack);

    open(0);
    label();
    if (!reduce) raf = requestAnimationFrame(tick);
  })();

  /* ---- industries ------------------------------------------------ */
  const SECTORS = ['Dentistry','Plastic surgery','MedSpa','Dermatology','Chiropractic','Physiotherapy','Veterinary','Optometry','Legal','Accounting','Insurance','Financial advice','Real estate','Mortgage','Home services','HVAC','Roofing','Plumbing','Electrical','Solar','Landscaping','Pest control','Cleaning','Moving','Auto repair','Auto dealers','Fitness','Wellness','Education','Childcare','Restaurants','Hospitality','E-commerce','Trades','Recruitment','Security'];
  let spinTick = () => {};
  (function () {
    const plot = document.getElementById('plot'), list = document.getElementById('list');
    if (!plot) return;
    const tally = document.getElementById('tally');
    if (tally) tally.textContent = SECTORS.length;

    const reading = document.createElement('div');
    reading.className = 'reading';
    reading.setAttribute('aria-hidden','true');   // duplicates the link's own name
    reading.innerHTML = '<div class="reading__n"></div>' +
      '<p class="reading__d">Paid search, social and local — mapped to how this sector actually gets bought.</p>' +
      '<span class="reading__go data data--nano"><span>View sector page</span><span>&rarr;</span></span>';
    const rName = reading.querySelector('.reading__n');

    const N = SECTORS.length, NODES = [];
    /* Fibonacci sphere — the only distribution that spaces N points evenly on a
       sphere without clustering at the poles, which is exactly the collision
       problem the flat grid had. */
    const GOLD = Math.PI * (1 + Math.sqrt(5));
    SECTORS.forEach((name, i) => {
      const phi = Math.acos(1 - 2 * (i + 0.5) / N);
      const th  = GOLD * (i + 0.5);
      const a = document.createElement('a');
      a.className = 'pt'; a.href = '#'; a.setAttribute('role','listitem');
      const inner = document.createElement('span');
      inner.className = 'pt__in'; inner.textContent = name.toLowerCase();
      a.appendChild(inner);

      const on = () => { a.classList.add('is-on'); rName.textContent = name;
        const pr = plot.getBoundingClientRect(), ar = a.getBoundingClientRect();
        let lx = ar.left - pr.left + ar.width * 0.5 + 24;
        if (lx + 268 > pr.width) lx = ar.left - pr.left - 268 - 10;
        let ty = ar.top - pr.top - 12;
        if (ty + 210 > pr.height) ty = pr.height - 210;
        reading.style.left = Math.max(0, lx) + 'px';
        reading.style.top  = Math.max(0, ty) + 'px';
        reading.classList.add('is-on'); };
      const off2 = () => { a.classList.remove('is-on'); reading.classList.remove('is-on'); };
      a.addEventListener('mouseenter', on); a.addEventListener('mouseleave', off2);
      a.addEventListener('focus', on);      a.addEventListener('blur', off2);
      a.addEventListener('click', ev => { if (matchMedia('(hover: none)').matches && !a.classList.contains('is-on')) {
        ev.preventDefault(); plot.querySelectorAll('.pt.is-on').forEach(n => n.classList.remove('is-on')); on(); }});
      plot.appendChild(a);

      NODES.push({ el:a, inner,
        x: Math.sin(phi)*Math.cos(th), y: Math.cos(phi), z: Math.sin(phi)*Math.sin(th) });

      const li = document.createElement('a'); li.href = '#';
      li.innerHTML = '<i></i><span class="data data--nano">' + name + '</span>'; list.appendChild(li);
    });
    plot.appendChild(reading);

    /* rotation: eased toward the cursor, with a slow idle drift so the volume
       is alive before you touch it (L10/L16) */
    /* The reference is a CAMERA SHIFT, not an object spin: labels stay
       suspended and translate at depth-scaled rates as the cursor moves —
       nothing flips sides, nothing flees. So: small rotation (±17°) plus a
       parallax pan where near labels travel ~3x the far ones. The earlier
       ±110° cursor rotation read as labels running away. */
    let nx = 0, ny = 0, tnx = 0, tny = 0, idle = 0;
    plot.addEventListener('pointermove', ev => {
      const r = plot.getBoundingClientRect();
      tnx = ((ev.clientX - r.left) / r.width  - .5) * 2;
      tny = ((ev.clientY - r.top)  / r.height - .5) * 2;
    }, { passive:true });
    plot.addEventListener('pointerleave', () => { tnx = 0; tny = 0; }, { passive:true });

    const PERSP = 2.35;          // camera distance, in sphere radii
    spinTick = function (t) {
      const r = plot.getBoundingClientRect();
      if (!r.width || r.bottom < 0 || r.top > innerHeight) return;
      nx += (tnx - nx) * 0.06; ny += (tny - ny) * 0.06;
      if (!reduce) idle = t * 0.000038;
      const ry = nx * 0.30 + idle, rx = -ny * 0.18;
      const panX = -nx * 30, panY = -ny * 18;
      const cx = r.width/2, cy = r.height/2;
      const R  = Math.min(r.width * 0.40, r.height * 0.60);
      const sy = Math.sin(ry), cyy = Math.cos(ry), sx = Math.sin(rx), cxx = Math.cos(rx);
      for (const n of NODES) {
        // rotate Y then X
        let x =  n.x*cyy + n.z*sy;
        let z = -n.x*sy  + n.z*cyy;
        let y =  n.y*cxx - z*sx;
        z     =  n.y*sx  + z*cxx;
        const sc = PERSP / (PERSP - z);                 // perspective
        const d  = (z + 1) / 2;                          // 0 back … 1 front
        const pf = 0.35 + 0.65*d;   // near labels pan ~3x the far ones
        n.el.style.transform = 'translate3d(' + (cx + x*R*sc + panX*pf).toFixed(1) + 'px,' + (cy + y*R*sc + panY*pf).toFixed(1) + 'px,0) translate(-50%,-50%)';
        n.el.style.fontSize  = (0.62 + d * 1.15).toFixed(3) + 'rem';
        n.el.style.opacity   = (0.14 + d * 0.86).toFixed(3);
        n.el.style.zIndex    = (10 + (d * 400) | 0);
        n.inner.style.setProperty('--up', (1.95 / (0.62 + d * 1.15)).toFixed(2));
      }
    };
  })();

  /* ---- one clock -------------------------------------------------- */
  function resizeAll(){ paintAll(); heroResize(); ascResize(); starsDraw(performance.now()); sweepResize(); sweepTick(); }
  let last = 0;
  function frame(now) {
    // hero relief breathes, throttled — it drifts, it does not animate
    /* Impeccable perf: the hero draws nothing when off-screen — before this
       gate it kept rendering at 9fps for the whole scroll depth of the page */
    const hr = hero ? hero.getBoundingClientRect() : null;
    if (hr && hr.bottom > 0 && hr.top < innerHeight) {
      if (now - last > 110) { heroDraw(now); last = now; }
      heroScatter(now);
      sweepTick();
    }
    spinTick(now);

    if (asc) {
      const r = asc.getBoundingClientRect();
      if (r.top < innerHeight && r.bottom > 0) {
        if (!aEnter) aEnter = now;
        // 900ms reveal, then the plume just plays
        const p = reduce ? 1 : Math.min(1, (now - aEnter) / 900);
        if (now - aLast > 38 || !aDrawn) { ascDraw(p); starsDraw(now); aLast = now; }   // clip is 24fps
        const rv = easeS(p / 0.55);
        if (ascMedia) { ascMedia.style.opacity = (0.15 + 0.85*rv).toFixed(3);
                        ascMedia.style.transform = 'scale(' + (1.18 - 0.18*rv).toFixed(4) + ')'; }
        /* The pin holds the scene while every word resolves. The final word
           lights before the sticky range ends, so the next section never
           begins moving underneath an unfinished statement. */
        const pinHeight = ascPin ? ascPin.getBoundingClientRect().height : innerHeight;
        const holdDistance = Math.max(1, asc.offsetHeight - pinHeight);
        const reveal = reduce ? 1 : Math.max(0, Math.min(1, -r.top / holdDistance));
        const lit = Math.min(ascWords.length, Math.floor((reveal / 0.90) * ascWords.length));
        ascWords.forEach((word, i) => word.classList.toggle('is-lit', i < lit));
      }
    }

    requestAnimationFrame(frame);
  }

  addEventListener('resize', resizeAll);
  addEventListener('load', resizeAll);
  resizeAll();
  if (reduce) {
    ascWords.forEach(word => word.classList.add('is-lit'));
    if (ascMedia) ascMedia.style.opacity = 1;
  } else requestAnimationFrame(frame);
})();


/* Faithful framework-free port of the supplied Admit One Ticket interaction. */
(function () {
  const ticket = document.getElementById('strategyTicket');
  const canvas = document.getElementById('ticketTexture');
  if (!ticket || !canvas) return;
  const ctx = canvas.getContext('2d', { alpha:false });
  if (!ctx) return;
  const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const bayer = [0,8,2,10,12,4,14,6,3,11,1,9,15,7,13,5].map(v=>v/16);
  const colors = [[180,66,160],[207,92,185],[224,134,203],[242,177,228]];

  function draw() {
    /* clientWidth is intentionally used instead of getBoundingClientRect().width.
       The latter changes during 3D tilt and caused the canvas to resize and
       clear repeatedly, which was the black flicker visible in the recording. */
    const w = Math.max(260,Math.round(ticket.clientWidth*.72));
    const h = Math.round(w/(741/425));
    if (canvas.width!==w || canvas.height!==h) { canvas.width=w; canvas.height=h; }
    const image=ctx.createImageData(w,h), d=image.data;
    for (let y=0;y<h;y++) for (let x=0;x<w;x++) {
      const u=x/w, v=y/h;
      const warp=Math.sin(v*13)*.045+Math.sin(v*31)*.016;
      const wave=Math.sin((u+warp)*14+Math.sin(v*7)*2.2)+Math.sin(v*18)*.58;
      const radial=1-Math.min(1,Math.hypot(u-.62,v-.30)/.76);
      const grain=Math.sin((x*12.9898+y*78.233))*43758.5453;
      let value=.46+wave*.14+radial*.34+(grain-Math.floor(grain))*.10;
      value=Math.max(0,Math.min(.999,value+(bayer[(x&3)+((y&3)<<2)]-.5)*.22));
      const c=colors[Math.min(3,Math.floor(value*4))], i=(y*w+x)*4;
      d[i]=c[0]; d[i+1]=c[1]; d[i+2]=c[2]; d[i+3]=255;
    }
    ctx.putImageData(image,0,0);
  }

  let resizeFrame=0;
  new ResizeObserver(()=>{ cancelAnimationFrame(resizeFrame); resizeFrame=requestAnimationFrame(draw); }).observe(ticket);
  if (!reduce) {
    ticket.addEventListener('pointermove', e => {
      const r=ticket.getBoundingClientRect();
      const dx=(e.clientX-r.left)/r.width-.5, dy=(e.clientY-r.top)/r.height-.5;
      ticket.style.transform=`perspective(1200px) rotateX(${-dy*18}deg) rotateY(${dx*18}deg) scale(1.02)`;
      ticket.style.setProperty('--mx',`${(dx+.5)*100}%`);
      ticket.style.setProperty('--my',`${(dy+.5)*100}%`);
    },{passive:true});
    ticket.addEventListener('pointerleave',()=>{
      ticket.style.transform='perspective(1200px) rotateX(0deg) rotateY(0deg) scale(1)';
      ticket.style.setProperty('--mx','50%'); ticket.style.setProperty('--my','50%');
    },{passive:true});
  }
  draw();
})();

/* Cursor-following spotlight treatment ported from the supplied GlowCard. */
(function () {
  const cards=[...document.querySelectorAll('.tier')];
  const buttons=[...document.querySelectorAll('.btn')];
  if (!matchMedia('(hover:hover) and (pointer:fine)').matches) return;
  const bindSpotlight=(element,xProperty,yProperty,activeClass)=>{
    let frame=0, nextX=0, nextY=0;
    const paint=()=>{
      const rect=element.getBoundingClientRect();
      element.style.setProperty(xProperty,`${nextX-rect.left}px`);
      element.style.setProperty(yProperty,`${nextY-rect.top}px`);
      frame=0;
    };
    element.addEventListener('pointerenter',()=>element.classList.add(activeClass),{passive:true});
    element.addEventListener('pointermove',event=>{
      nextX=event.clientX; nextY=event.clientY;
      if (!frame) frame=requestAnimationFrame(paint);
    },{passive:true});
    element.addEventListener('pointerleave',()=>{
      element.classList.remove(activeClass);
      if (frame) { cancelAnimationFrame(frame); frame=0; }
    },{passive:true});
  };
  cards.forEach(card=>bindSpotlight(card,'--glow-x','--glow-y','is-spotlit'));
  buttons.forEach(button=>bindSpotlight(button,'--btn-glow-x','--btn-glow-y','is-btn-spotlit'));
})();

/* Stable dark-blue dither for the featured pricing card. */
(function () {
  const card=document.querySelector('.tier--hot');
  const canvas=document.getElementById('pricingDither');
  if (!card || !canvas) return;
  const ctx=canvas.getContext('2d',{alpha:false});
  if (!ctx) return;
  const bayer=[0,8,2,10,12,4,14,6,3,11,1,9,15,7,13,5].map(v=>v/16);
  const colors=[[3,9,28],[6,22,62],[1,66,180],[1,94,255]];
  function drawPricingDither(time=0){
    const w=Math.max(240,Math.round(card.clientWidth*.7));
    const h=Math.max(320,Math.round(card.clientHeight*.7));
    if(canvas.width!==w||canvas.height!==h){canvas.width=w;canvas.height=h;}
    const image=ctx.createImageData(w,h),d=image.data;
    const drift=time*.00010;
    for(let y=0;y<h;y++)for(let x=0;x<w;x++){
      const u=x/w,v=y/h;
      const warp=Math.sin(v*12+drift)*.055+Math.sin(v*29-drift*.7)*.018;
      const wave=Math.sin((u+warp+drift*.18)*13+Math.sin(v*8-drift*.35)*2.1)+Math.sin(v*17+drift*.5)*.52;
      const radial=1-Math.min(1,Math.hypot(u-(.58+Math.sin(drift*.4)*.06),v-(.28+Math.cos(drift*.3)*.04))/.82);
      const grain=Math.sin((x*12.9898+y*78.233))*43758.5453;
      let value=.24+wave*.12+radial*.28+(grain-Math.floor(grain))*.08;
      value=Math.max(0,Math.min(.999,value+(bayer[(x&3)+((y&3)<<2)]-.5)*.2));
      const c=colors[Math.min(3,Math.floor(value*4))],i=(y*w+x)*4;
      d[i]=c[0];d[i+1]=c[1];d[i+2]=c[2];d[i+3]=255;
    }
    ctx.putImageData(image,0,0);
  }
  let pricingResizeFrame=0;
  new ResizeObserver(()=>{cancelAnimationFrame(pricingResizeFrame);pricingResizeFrame=requestAnimationFrame(drawPricingDither);}).observe(card);
  drawPricingDither();
  if(!matchMedia('(prefers-reduced-motion: reduce)').matches){
    let lastFrame=0;
    const animatePricingDither=(time)=>{
      if(time-lastFrame>120){ drawPricingDither(time); lastFrame=time; }
      requestAnimationFrame(animatePricingDither);
    };
    requestAnimationFrame(animatePricingDither);
  }
})();

/* Footer wordmark: a small, self-contained adaptation of the liquid-text
   morph. It pauses off-screen and honours reduced-motion preferences. */
(function () {
  const root = document.getElementById('footerMorph');
  if (!root) return;
  const [outgoing, incoming] = root.querySelectorAll('span');
  const words = ['ΛPEX', 'MARKETING'];
  const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;

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

/* GSAP reveal system: hierarchy on entry, sequence within each section.
   Existing pinned/interactive motion keeps ownership of its own transforms. */
(function () {
  if (!window.gsap || !window.ScrollTrigger) return;
  gsap.registerPlugin(ScrollTrigger);

  const motion = gsap.matchMedia();
  const cleanupTargets = new Set();

  function revealGroup(selector, options = {}) {
    const elements = gsap.utils.toArray(selector);
    if (!elements.length) return;
    elements.forEach(element => cleanupTargets.add(element));

    const trigger = options.trigger || elements[0];
    const from = {
      autoAlpha: 0,
      x: options.x || 0,
      y: options.y == null ? 34 : options.y,
      scale: options.scale == null ? 1 : options.scale,
      rotate: options.rotate || 0
    };

    gsap.from(elements, {
      ...from,
      duration: options.duration || .9,
      stagger: options.stagger == null ? .1 : options.stagger,
      ease: options.ease || 'power3.out',
      clearProps: 'transform,opacity,visibility',
      scrollTrigger: {
        trigger,
        start: options.start || 'top 84%',
        once: true
      }
    });
  }

  motion.add('(prefers-reduced-motion: no-preference)', () => {
    const hero = document.querySelector('.hero');
    if (hero && scrollY < hero.offsetHeight) {
      const heroTimeline = gsap.timeline({ defaults:{ ease:'power3.out' } });
      heroTimeline
        .from('.hero__h1', { autoAlpha:0, y:48, duration:1.05, clearProps:'transform,opacity,visibility' })
        .from('.hero__lead .lede', { autoAlpha:0, y:24, duration:.75, clearProps:'transform,opacity,visibility' }, '-=.62')
        .from('.hero__lead .btn', { autoAlpha:0, y:18, scale:.98, duration:.65, clearProps:'transform,opacity,visibility' }, '-=.46')
        .from('.hero__rail > *', { autoAlpha:0, y:12, duration:.55, stagger:.08, clearProps:'transform,opacity,visibility' }, '-=.25');
    }

    revealGroup('.svc .sec__head > *', { trigger:'.svc .sec__head', x:-30, y:0, stagger:.14 });
    revealGroup('.svc .pc', { trigger:'.svc .plates', y:52, scale:.985, stagger:.12, duration:1 });

    revealGroup('.ind .sec__head > *', { trigger:'.ind .sec__head', x:30, y:0, stagger:.14 });
    revealGroup('.red__head .h2', { trigger:'.red__head', y:42, duration:1.05 });
    revealGroup('.clause', { trigger:'#clauses', x:-36, y:0, stagger:.09, duration:.82, start:'top 78%' });
    revealGroup('.red__art', { trigger:'#clauses', x:54, y:0, scale:.97, duration:1.05, start:'top 78%' });

    revealGroup('.price .sec__head', { trigger:'.price .sec__head', y:38, duration:1 });
    revealGroup('.price .tier', { trigger:'.tiers', y:58, scale:.975, stagger:.13, duration:1.05, start:'top 82%' });
    revealGroup('.price__foot li', { trigger:'.price__foot', y:22, stagger:.07, duration:.7 });

    revealGroup('.founder__portrait', { trigger:'.founder__cols', x:-52, y:0, scale:.98, duration:1.05 });
    revealGroup('.founder__copy > *', { trigger:'.founder__cols', x:42, y:0, stagger:.09, duration:.88 });

    revealGroup('.admit-ticket', { trigger:'.book__body', y:64, scale:.94, duration:1.15, ease:'back.out(1.25)', start:'top 78%' });
    revealGroup('.ticket-booking-cta', { trigger:'.book__body', y:24, scale:.97, duration:.72, start:'top 72%' });
    revealGroup('.foot__grid > *', { trigger:'.foot', y:30, stagger:.08, duration:.82 });
    revealGroup('.foot__legal > *', { trigger:'.foot__legal', y:12, stagger:.08, duration:.6 });

    const refresh = () => ScrollTrigger.refresh();
    addEventListener('load', refresh, { once:true });
    return () => {
      removeEventListener('load', refresh);
      ScrollTrigger.getAll().forEach(trigger => trigger.kill());
    };
  });

  motion.add('(prefers-reduced-motion: reduce)', () => {
    gsap.set([...cleanupTargets], { clearProps:'transform,opacity,visibility' });
  });
})();

