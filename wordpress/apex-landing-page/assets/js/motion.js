/* ============================================================
   APEX - motion.js
   Set piece 1: The Calendar Fills (hero)
   Set piece 2: The Signed Guarantee (certificate)
   Plus: strike-cycle H1, pinned pains rail, journey line,
   count-ups, reveals. Degrades fully without JS / reduced motion.
   ============================================================ */

(function () {
  "use strict";

  window.__motionErrors = [];
  window.addEventListener("error", function (e) {
    window.__motionErrors.push((e.message || "?") + " @ " + (e.filename || "") + ":" + (e.lineno || 0));
  });

  // Keep the services overview directly beneath the hero.
  var heroSection = document.getElementById("hero");
  var servicesSection = document.getElementById("services");
  if (heroSection && servicesSection) heroSection.insertAdjacentElement("afterend", servicesSection);
  var painsSection = document.getElementById("pains");
  var guaranteeSection = document.getElementById("guarantee");
  if (painsSection && guaranteeSection) guaranteeSection.insertAdjacentElement("beforebegin", painsSection);
  var proofSection = document.getElementById("proof");
  if (painsSection && proofSection) painsSection.insertAdjacentElement("afterend", proofSection);
  var pricingSection = document.getElementById("pricing");
  var founderSection = document.getElementById("founder");
  if (pricingSection && founderSection) pricingSection.insertAdjacentElement("afterend", founderSection);
  if (pricingSection && guaranteeSection) pricingSection.insertAdjacentElement("afterend", guaranteeSection);

  var reduced = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  var hasGsap = typeof gsap !== "undefined";
  if (reduced || !hasGsap) document.documentElement.classList.add("reduced");

  /* ---------- Calendar DOM (built regardless of motion) ---------- */
  var WEEKDAYS = ["MON", "TUE", "WED", "THU", "FRI", "SAT", "SUN"];
  var START_COUNT = 2;
  var TARGET_COUNT = 30;
  var calendarHead = document.getElementById("calendarHead");
  var body = document.getElementById("calendarBody");
  var monthEl = document.getElementById("calendarMonth");
  var countEl = document.getElementById("consultCount");
  var bookedChecks = [];

  function getNextMonthDate(fromDate) {
    return new Date(fromDate.getFullYear(), fromDate.getMonth() + 1, 1);
  }

  function formatHeroMonth(date) {
    return date.toLocaleString("en-US", { month: "long", year: "numeric" }).toUpperCase();
  }

  function cubicBezier(x1, y1, x2, y2) {
    function sampleCurveX(t) {
      return ((1 - 3 * x2 + 3 * x1) * t + (3 * x2 - 6 * x1)) * t * t + (3 * x1) * t;
    }
    function sampleCurveY(t) {
      return ((1 - 3 * y2 + 3 * y1) * t + (3 * y2 - 6 * y1)) * t * t + (3 * y1) * t;
    }
    function sampleCurveDerivativeX(t) {
      return (3 * (1 - 3 * x2 + 3 * x1) * t + 2 * (3 * x2 - 6 * x1)) * t + (3 * x1);
    }
    function solveCurveX(x) {
      var t = x;
      for (var i = 0; i < 8; i++) {
        var x2Sample = sampleCurveX(t) - x;
        if (Math.abs(x2Sample) < 0.000001) return t;
        var d2 = sampleCurveDerivativeX(t);
        if (Math.abs(d2) < 0.000001) break;
        t = t - x2Sample / d2;
      }
      var t0 = 0;
      var t1 = 1;
      t = x;
      while (t0 < t1) {
        var x2Binary = sampleCurveX(t);
        if (Math.abs(x2Binary - x) < 0.000001) return t;
        if (x > x2Binary) t0 = t;
        else t1 = t;
        t = (t1 - t0) * 0.5 + t0;
      }
      return t;
    }
    return function (progress) {
      return sampleCurveY(solveCurveX(progress));
    };
  }

  function renderCalendar(fromDate) {
    if (!body || !calendarHead) return;

    var nextMonth = getNextMonthDate(fromDate || new Date());
    var year = nextMonth.getFullYear();
    var month = nextMonth.getMonth();

    if (monthEl) monthEl.textContent = formatHeroMonth(nextMonth);
    if (countEl) countEl.textContent = START_COUNT + " booked";

    calendarHead.innerHTML = "";
    body.innerHTML = "";
    bookedChecks = [];

    WEEKDAYS.forEach(function (day) {
      var label = document.createElement("span");
      label.textContent = day;
      calendarHead.appendChild(label);
    });

    for (var i = 0; i < 28; i++) {
      var cell = document.createElement("div");
      cell.className = "cal-cell";
      cell.dataset.slot = String(i + 1);

      var check = document.createElement("span");
      check.className = "cal-check";
      check.innerHTML = '<svg viewBox="0 0 32 26" aria-hidden="true"><path d="M2 14c3 2.2 8 7 10 9.5C14 20 23 6.5 30.5 2"/></svg>';
      cell.appendChild(check);
      body.appendChild(cell);

      if (bookedChecks.length < TARGET_COUNT) bookedChecks.push(check);
    }
  }

  renderCalendar(new Date());

  /* ---------- No-motion fallback ---------- */
  if (reduced || !hasGsap) {
    bookedChecks.forEach(function (check) {
      check.style.opacity = 1;
      check.style.transform = "none";
      if (check.parentNode) check.parentNode.classList.add("is-booked");
    });
    if (countEl) countEl.textContent = TARGET_COUNT + " booked";
    var t = document.getElementById("certType");
    if (t) t.textContent = "You get every dollar back.";
    wireUi();
    return;
  }

  gsap.registerPlugin(ScrollTrigger);

  // Pinned sections + browser scroll restoration race on refresh and can
  // leave once-only triggers unfired (invisible sections). Take scroll
  // restoration over manually - GSAP's documented fix.
  ScrollTrigger.clearScrollMemory("manual");

  // UI bindings first - must never depend on animation code succeeding
  wireUi();

  /* ---------- Lenis smooth scroll ---------- */
  var lenis = null;
  if (typeof Lenis !== "undefined" && window.innerWidth > 760) {
    lenis = new Lenis({ duration: 1.1, smoothWheel: true });
    lenis.on("scroll", ScrollTrigger.update);
    gsap.ticker.add(function (time) { lenis.raf(time * 1000); });
    gsap.ticker.lagSmoothing(0);
  }

  /* ============================================================
     HERO ENTRANCE + SET PIECE 1: THE CALENDAR FILLS
     ============================================================ */
  var heroTl = gsap.timeline({ defaults: { ease: "power2.out" } });

  heroTl
    .to(".hero__eyebrow", { opacity: 1, y: 0, duration: 0.6 }, 0.1)
    .from(".hero__line", { y: 28, opacity: 0, stagger: 0.1, duration: 0.75, ease: "power3.out" }, 0.15)
    .to(".hero__sub", { opacity: 1, y: 0, duration: 0.6 }, 0.7)
    .to(".hero__trust", { opacity: 1, y: 0, duration: 0.6 }, 0.85)
    .to(".hero__primary", { opacity: 1, y: 0, duration: 0.6 }, 1.0)
    .from(".hero__portrait", { y: 20, opacity: 0, duration: 0.8, ease: "power3.out" }, 0.3)
    .from(".calendar", { y: 28, opacity: 0, duration: 0.8, ease: "power3.out" }, 0.45)
    .from(".hero__stat", { y: 16, opacity: 0, duration: 0.6, ease: "power3.out" }, 0.75);

  /* calendar fills - loops: empty -> fill -> hold -> snap back -> repeat */
  if (bookedChecks.length) {
    var bookedEase = cubicBezier(0.15, 0.85, 0.3, 1);
    var bookedStagger = 0.095479;
    var bookedDuration = ((bookedChecks.length - 1) * bookedStagger) + 0.3;
    var holdTime = 1.3;

    var calTl = gsap.timeline({ delay: 1.05, repeat: -1, repeatDelay: holdTime });
    var count = { v: START_COUNT };

    // reset to empty state - runs at t=0 on first play AND at the top of every repeat
    calTl.call(function () {
      bookedChecks.forEach(function (check) {
        if (check.parentNode) check.parentNode.classList.remove("is-booked");
      });
      if (countEl) countEl.textContent = START_COUNT + " booked";
    }, null, 0);

    calTl.to(count, {
      v: TARGET_COUNT,
      duration: bookedDuration,
      ease: "power2.out",
      onUpdate: function () {
        if (countEl) countEl.textContent = Math.round(count.v) + " booked";
      },
      onComplete: function () {
        if (countEl) countEl.textContent = TARGET_COUNT + " booked";
      }
    }, 0);

    bookedChecks.forEach(function (check, i) {
      var at = i * bookedStagger;
      calTl.call(function () {
        if (check.parentNode) check.parentNode.classList.add("is-booked");
      }, null, at);
      calTl.fromTo(
        check,
        { opacity: 0 },
        { opacity: 1, duration: 0.08, ease: "power1.inOut" },
        at
      );
      calTl.fromTo(
        check,
        { y: 20, rotate: 60, scale: 0 },
        { y: 0, rotate: 0, scale: 1, duration: 0.3, ease: bookedEase },
        at
      );
    });
  }

  /* ticker loop */
  var track = document.getElementById("tickerTrack");
  if (track) {
    track.innerHTML += track.innerHTML + track.innerHTML;
    gsap.to(track, { xPercent: -33.333, duration: 22, repeat: -1, ease: "none" });
  }

  /* ============================================================
     SET PIECE 2: THE SIGNED GUARANTEE
     ============================================================ */
  var certBorder = document.getElementById("certBorder");
  var sigPath = document.getElementById("sigPath");
  var typeTarget = document.getElementById("certType");
  var caret = document.getElementById("certCaret");
  var TYPE_TEXT = "You get every dollar back.";

  var certSeal = document.getElementById("certSeal");

  if (certBorder && sigPath && typeTarget) {
    var borderLen = certBorder.getTotalLength();
    var sigLen = sigPath.getTotalLength();
    gsap.set(certBorder, { strokeDasharray: borderLen, strokeDashoffset: borderLen });
    gsap.set(sigPath, { strokeDasharray: sigLen, strokeDashoffset: sigLen });
    if (certSeal) gsap.set(certSeal, { opacity: 0, scale: 0.4, rotate: -28, transformOrigin: "50% 50%" });

    var certTl = gsap.timeline({
      scrollTrigger: { trigger: "#cert", start: "top 72%", once: true }
    });

    certTl
      .to(certBorder, { strokeDashoffset: 0, duration: 1.6, ease: "power2.inOut" })
      .from(".cert__h2", { opacity: 0, y: 18, duration: 0.6 }, 0.6);

    // seal "stamp" - slams in with an elastic overshoot, then a tiny recoil
    if (certSeal) {
      certTl.to(certSeal, {
        opacity: 1, scale: 1, rotate: -6, duration: 0.7, ease: "back.out(2.2)"
      }, 0.05)
      .to(certSeal, { rotate: 0, duration: 0.4, ease: "power2.out" }, 0.75);
    }

    // typewriter
    certTl.call(function () {
      var i = 0;
      var iv = setInterval(function () {
        typeTarget.textContent = TYPE_TEXT.slice(0, ++i);
        if (i >= TYPE_TEXT.length) {
          clearInterval(iv);
          // sign it
          gsap.to(sigPath, { strokeDashoffset: 0, duration: 1.7, ease: "power1.inOut", delay: 0.35 });
          gsap.from(".cert__sigline, .cert__signame", { opacity: 0, duration: 0.6, delay: 1.6 });
          if (caret) gsap.to(caret, { opacity: 0, duration: 0.3, delay: 0.8 });
        }
      }, 34);
    }, null, 1.0);

    certTl.from(".cert__body, .cert__signrow .btn", {
      opacity: 0, y: 16, stagger: 0.15, duration: 0.6
    }, 1.4);
  }

  /* ============================================================
     PAINS - pinned horizontal rail (desktop only)
     ============================================================ */
  var painsTrack = document.getElementById("painsTrack");
  var painsPin = document.getElementById("painsPin");
  if (painsTrack && painsPin && window.innerWidth > 1020) {
    var getScroll = function () { return painsTrack.scrollWidth - window.innerWidth + 48; };
    gsap.to(painsTrack, {
      x: function () { return -getScroll(); },
      ease: "none",
      scrollTrigger: {
        trigger: "#pains",
        start: "top top",
        end: function () { return "+=" + getScroll(); },
        pin: true,
        anticipatePin: 1,
        scrub: true,
        invalidateOnRefresh: true
      }
    });
  } else if (painsPin) {
    painsPin.style.overflowX = "auto";
    painsPin.style.webkitOverflowScrolling = "touch";
  }

  /* ============================================================
     GENERIC: reveals + count-ups
     ============================================================ */
  document.querySelectorAll(".reveal").forEach(function (el) {
    if (el.closest(".hero")) return; // hero handled by entrance timeline
    gsap.to(el, {
      opacity: 1, y: 0, duration: 0.75, ease: "power2.out",
      scrollTrigger: { trigger: el, start: "top 85%", once: true }
    });
  });

  document.querySelectorAll(".stat[data-count]").forEach(function (el) {
    var target = parseInt(el.dataset.count, 10) || 0;
    var prefix = el.dataset.prefix || "";
    var suffix = el.dataset.suffix || "";
    var obj = { v: 0 };
    ScrollTrigger.create({
      trigger: el,
      start: "top 85%",
      once: true,
      onEnter: function () {
        gsap.to(obj, {
          v: target, duration: 1.4, ease: "power2.out",
          onUpdate: function () { el.textContent = prefix + Math.round(obj.v) + suffix; },
          onComplete: function () { el.textContent = prefix + target + suffix; }
        });
      }
    });
  });

  /* ============================================================
     RELIABILITY: recalc trigger positions once assets settle, and
     force-reveal anything ScrollTrigger missed (refresh mid-page,
     layout shifts from images/fonts/pin, CDN hiccups).
     ============================================================ */
  window.addEventListener("load", function () { ScrollTrigger.refresh(); });
  if (document.fonts && document.fonts.ready) {
    document.fonts.ready.then(function () { ScrollTrigger.refresh(); });
  }

  if ("IntersectionObserver" in window) {
    var failsafe = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        var el = entry.target;
        failsafe.unobserve(el);
        // Give the normal ScrollTrigger animation a beat to run first;
        // only force-reveal if the element is still hidden after it.
        setTimeout(function () {
          if (parseFloat(getComputedStyle(el).opacity) < 0.9) {
            gsap.to(el, { opacity: 1, y: 0, duration: 0.6, ease: "power2.out" });
          }
        }, 800);
      });
    }, { threshold: 0.1 });
    document.querySelectorAll(".reveal").forEach(function (el) { failsafe.observe(el); });

    // Certificate heading is typed in by its own once-only timeline - kick
    // it if the section is on screen but the timeline never started.
    var certEl = document.getElementById("cert");
    if (certEl && typeof certTl !== "undefined") {
      var certFailsafe = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting) return;
          certFailsafe.unobserve(entry.target);
          setTimeout(function () {
            if (certTl.progress() === 0 && !certTl.isActive()) certTl.play(0);
          }, 1000);
        });
      }, { threshold: 0.25 });
      certFailsafe.observe(certEl);
    }
  }

  /* ============================================================
     UI wiring (nav, menu, mobile cta, form)
     ============================================================ */
  function wireUi() {
    var nav = document.getElementById("nav");
    var mobileCta = document.getElementById("mobileCta");
    var hero = document.getElementById("hero");
    var bookSection = document.getElementById("book");

    window.addEventListener("scroll", onScroll, { passive: true });
    onScroll();

    function onScroll() {
      var y = window.scrollY || document.documentElement.scrollTop;
      if (nav) nav.classList.toggle("is-scrolled", y > 30);
      if (mobileCta && hero && bookSection && window.innerWidth <= 760) {
        var pastHero = y > hero.offsetHeight * 0.6;
        var atForm = y + window.innerHeight > bookSection.offsetTop + 120;
        mobileCta.classList.toggle("show", pastHero && !atForm);
      }
    }

    var burger = document.getElementById("burger");
    var menu = document.getElementById("mobileMenu");
    if (burger && menu) {
      burger.addEventListener("click", function () {
        burger.classList.toggle("open");
        menu.classList.toggle("open");
      });
      menu.querySelectorAll("a").forEach(function (a) {
        a.addEventListener("click", function () {
          burger.classList.remove("open");
          menu.classList.remove("open");
        });
      });
    }

    /* ---- Book modal: open/close GHL form ---- */
    var overlay = document.getElementById("bookModalOverlay");
    var closeBtn = document.getElementById("bookModalClose");
    var lastFocused = null;

    function openModal() {
      if (!overlay) return;
      lastFocused = document.activeElement;
      overlay.classList.add("is-open");
      overlay.setAttribute("aria-hidden", "false");
      document.body.classList.add("modal-open");
      if (closeBtn) setTimeout(function () { closeBtn.focus(); }, 300);
    }

    function closeModal() {
      if (!overlay) return;
      overlay.classList.remove("is-open");
      overlay.setAttribute("aria-hidden", "true");
      document.body.classList.remove("modal-open");
      if (lastFocused && lastFocused.focus) lastFocused.focus();
    }

    document.querySelectorAll(".cta-book").forEach(function (cta) {
      cta.addEventListener("click", function (e) {
        e.preventDefault();
        openModal();
      });
    });

    if (closeBtn) closeBtn.addEventListener("click", closeModal);
    if (overlay) {
      overlay.addEventListener("click", function (e) {
        if (e.target === overlay) closeModal();
      });
    }
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && overlay && overlay.classList.contains("is-open")) closeModal();
    });

  }
})();
