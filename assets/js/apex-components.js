/* Apex Marketing - shared homepage components.
   Moved out of homepage.js unchanged so every template that uses the
   homepage's terms accordion, pricing tiers, buttons and admit-one ticket
   runs the same behaviour from one copy. No dependencies; each block is a
   no-op when its markup is absent. Enqueued on the homepage and industry
   templates. */

/* ---- redline: one open clause, on a dwell timer ------------------
   The reference advances on a timer. That needs a pause control to
   clear WCAG 2.2.2, so: pause button, pause on hover, pause on
   keyboard focus inside the stack, and no timer at all under
   prefers-reduced-motion — the accordion is then click-only. */
(function () {
  const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
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
  /* Tilt. Hit-testing the tilting element itself made it glitch: the tilt
     swings the card's edge out from under the cursor, which fires
     pointerleave, which resets it, which fires pointerenter again - a
     flicker loop at every edge - and each pointermove restarted the 420ms
     CSS transition, so the card lagged and stuttered behind the cursor.
     Now the section tracks the pointer against the card's UNtransformed box
     (measured from layout, not getBoundingClientRect), updates once per
     frame, follows with a short transition, and eases home on the long one. */
  if (!reduce && matchMedia('(hover:hover) and (pointer:fine)').matches) {
    const zone = ticket.closest('section') || ticket.parentElement;
    const MAX = 12;
    let box = null, raf = 0, px = 0, py = 0, tilted = false;
    const measure = () => {
      let x = 0, y = 0, el = ticket;
      while (el) { x += el.offsetLeft; y += el.offsetTop; el = el.offsetParent; }
      return { left: x - scrollX, top: y - scrollY, width: ticket.offsetWidth, height: ticket.offsetHeight };
    };
    const reset = () => {
      if (!tilted) return;
      tilted = false;
      ticket.style.transition = '';
      ticket.style.transform = 'perspective(1200px) rotateX(0deg) rotateY(0deg) scale(1)';
      ticket.style.setProperty('--mx', '50%'); ticket.style.setProperty('--my', '50%');
    };
    const paint = () => {
      raf = 0;
      if (!box) box = measure();
      const u = (px - box.left) / box.width, v = (py - box.top) / box.height;
      if (u < 0 || u > 1 || v < 0 || v > 1) { reset(); return; }
      const dx = u - .5, dy = v - .5;
      tilted = true;
      ticket.style.transition = 'transform 140ms linear, filter 420ms cubic-bezier(.22,1,.36,1)';
      ticket.style.transform = `perspective(1200px) rotateX(${(-dy * MAX).toFixed(2)}deg) rotateY(${(dx * MAX).toFixed(2)}deg) scale(1.02)`;
      ticket.style.setProperty('--mx', `${(u * 100).toFixed(1)}%`);
      ticket.style.setProperty('--my', `${(v * 100).toFixed(1)}%`);
    };
    zone.addEventListener('pointermove', e => {
      px = e.clientX; py = e.clientY;
      if (!raf) raf = requestAnimationFrame(paint);
    }, { passive: true });
    zone.addEventListener('pointerleave', () => { if (raf) { cancelAnimationFrame(raf); raf = 0; } reset(); }, { passive: true });
    // layout can move under a still pointer; re-measure on the next move
    addEventListener('scroll', () => { box = null; }, { passive: true });
    addEventListener('resize', () => { box = null; }, { passive: true });
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
