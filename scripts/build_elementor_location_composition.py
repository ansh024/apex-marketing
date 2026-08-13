#!/usr/bin/env python3
"""Generate the Elementor build-composition payload for the Austin, TX location page.

Why this exists (see docs/elementor-authoring.md §5 and the PR discussion): Elementor's
atomic settings/styles use a type-tagged `{"$$type": ..., "value": ...}` wire format that
this session could not fully verify against a live Elementor instance — no site access, no
reachable MCP proxy, no way to test whether a hand-typed nested prop (e.g. a `size` value
living inside a style variant) is wrapped once or twice. Styles fail *soft* on a mismatch
(silently unstyled, not an error), which is exactly the failure mode this whole project is
trying to avoid.

Elementor's own `elementor/build-composition` MCP tool sidesteps that entirely: it accepts
plain XML + plain element_config + plain CSS text, and Elementor's own server-side code does
the $$type-wrapping and CSS-to-atomic-props conversion. That input shape is verified against
the MCP module's own committed guidance
(modules/mcp/static-resources/abilities/build-composition.md in the elementor/elementor repo,
main @ 2026-08-13) rather than reconstructed from reading prop-type source, so it's the one
representation of this page this project can stand behind as correct.

Output: elementor-templates/location-page.json — the payload plus the global-variable and
global-class definitions it depends on, ready to submit once elementor/build-composition is
reachable (self-hosted MCP client, or a future Claude session with site access). See
elementor-templates/README.md for the current (no-MCP-yet) way to use this file by hand.

Run: python3 scripts/build_elementor_location_composition.py
"""
import json
import re
import xml.dom.minidom as minidom
from pathlib import Path

REPO_ROOT = Path(__file__).resolve().parent.parent
OUT_PATH = REPO_ROOT / "elementor-templates" / "location-page.json"

# ---------------------------------------------------------------------------
# Longhand-CSS discipline: the MCP guidance is explicit that shorthand
# properties (padding, margin, border, background, font, gap, place-items,
# flex, inset...) can silently fall back to custom_css, which Pro 3.35+
# strips. Every helper below emits only longhand declarations.
# ---------------------------------------------------------------------------

def box(prop, top=None, right=None, bottom=None, left=None):
    """padding/margin as four explicit longhand declarations."""
    decls = []
    for side, val in (("top", top), ("right", right), ("bottom", bottom), ("left", left)):
        if val is not None:
            decls.append(f"{prop}-{side}: {val};")
    return " ".join(decls)


def border(side, width="1px", color="var(--rule-strong)", style="solid"):
    return f"border-{side}-width: {width}; border-{side}-style: {style}; border-{side}-color: {color};"


def radius(all_corners=None, tl=None, tr=None, br=None, bl=None):
    if all_corners is not None:
        tl = tr = br = bl = all_corners
    decls = []
    for corner, val in (("top-left", tl), ("top-right", tr), ("bottom-right", br), ("bottom-left", bl)):
        if val is not None:
            decls.append(f"border-{corner}-radius: {val};")
    return " ".join(decls)


def gap(row=None, col=None):
    decls = []
    if row is not None:
        decls.append(f"row-gap: {row};")
    if col is not None:
        decls.append(f"column-gap: {col};")
    return " ".join(decls)


# ---------------------------------------------------------------------------
# Tiny element-tree DSL. Each node becomes one XML tag plus entries in the
# element_config / style / classes maps, keyed by configuration-id.
# ---------------------------------------------------------------------------

class Node:
    def __init__(self, tag, cid, config=None, style=None, classes=None, children=None):
        self.tag = tag
        self.cid = cid
        self.config = config or {}
        self.style = style
        self.classes = classes or []
        self.children = children or []


def el(tag, cid, *, tag_attr=None, link=None, style=None, classes=None, children=None, **config):
    cfg = dict(config)
    if tag_attr is not None:
        cfg["tag"] = tag_attr
    if link is not None:
        cfg["link"] = link
    return Node(tag, cid, config=cfg, style=style, classes=classes, children=children)


def div(cid, *, tag_attr="div", link=None, style=None, classes=None, children=None):
    return el("e-div-block", cid, tag_attr=tag_attr, link=link, style=style, classes=classes, children=children)


def flex(cid, *, tag_attr="div", link=None, style=None, classes=None, children=None):
    return el("e-flexbox", cid, tag_attr=tag_attr, link=link, style=style, classes=classes, children=children)


def heading(cid, text, *, tag_attr="h2", link=None, style=None, classes=None):
    return el("e-heading", cid, tag_attr=tag_attr, link=link, style=style, classes=classes,
              title={"content": text, "children": []})


def paragraph(cid, text, *, tag_attr="p", link=None, style=None, classes=None):
    return el("e-paragraph", cid, tag_attr=tag_attr, link=link, style=style, classes=classes,
              paragraph={"content": text, "children": []})


def button(cid, text, href, *, style=None, classes=None, new_tab=False):
    return el("e-button", cid, style=style, classes=classes,
              text={"content": text, "children": []},
              link={"destination": href, "isTargetBlank": new_tab, "tag": "a"})


def image(cid, src, alt, *, style=None, classes=None, link=None):
    return el("e-image", cid, link=link, style=style, classes=classes,
              image={"src": {"url": src}, "size": "full"})


def divider(cid, *, style=None, classes=None):
    return el("e-divider", cid, style=style, classes=classes)


# ---------------------------------------------------------------------------
# Global variables — flat, non-fluid tokens only. Anything defined with
# clamp() (the type scale, --gutter, --section-pad, --grid-cell) cannot be
# represented as a structured Size variable, so it stays literal CSS inside
# each element's `style` string instead — see docs/design.md §10.
# ---------------------------------------------------------------------------
GLOBAL_VARIABLES = [
    {"label": "paper", "type": "color", "value": "#EEEEEE"},
    {"label": "paper-raised", "type": "color", "value": "#F4F4F3"},
    {"label": "paper-sunk", "type": "color", "value": "#E4E4E2"},
    {"label": "ink", "type": "color", "value": "#000000"},
    {"label": "ink-muted", "type": "color", "value": "#55555A"},
    {"label": "ink-faint", "type": "color", "value": "#8A8A8F"},
    {"label": "blue", "type": "color", "value": "#015EFF"},
    {"label": "blue-deep", "type": "color", "value": "#0142B4"},
    {"label": "pink", "type": "color", "value": "#E086CB"},
    {"label": "pink-hot", "type": "color", "value": "#D45CB8"},
    {"label": "mint", "type": "color", "value": "#B8ECE2"},
    {"label": "night", "type": "color", "value": "#151A2C"},
    {"label": "night-raised", "type": "color", "value": "#1E2438"},
    {"label": "rule", "type": "color", "value": "rgba(0,0,0,0.10)"},
    {"label": "rule-strong", "type": "color", "value": "rgba(0,0,0,0.22)"},
    {"label": "s-1", "type": "size", "value": "4px"},
    {"label": "s-2", "type": "size", "value": "8px"},
    {"label": "s-3", "type": "size", "value": "12px"},
    {"label": "s-4", "type": "size", "value": "16px"},
    {"label": "s-5", "type": "size", "value": "24px"},
    {"label": "s-6", "type": "size", "value": "32px"},
    {"label": "s-7", "type": "size", "value": "48px"},
    {"label": "s-8", "type": "size", "value": "64px"},
    {"label": "s-9", "type": "size", "value": "96px"},
    {"label": "hair", "type": "size", "value": "1px"},
    {"label": "r-sm", "type": "size", "value": "2px"},
    {"label": "r-pill", "type": "size", "value": "999px"},
    {"label": "container-max", "type": "size", "value": "1560px"},
    {"label": "font-voice", "type": "font-family", "value": "Arimo, Arial, Helvetica, sans-serif"},
]

# ---------------------------------------------------------------------------
# Global classes — the apex-* component vocabulary from the design system,
# ported 1:1. Defined once, referenced by label from every element that
# needs it. This is what keeps a 20-city rollout to "duplicate the page +
# edit copy" instead of "restyle 20 pages by hand".
# ---------------------------------------------------------------------------
GLOBAL_CLASSES = {
    "container": (
        "width: 100%; margin-left: auto; margin-right: auto; max-width: var(--container-max); "
        + box("padding", left="20px", right="20px")
        + " @media(--tablet) { " + box("padding", left="40px", right="40px") + " } "
        + "@media(--laptop) { " + box("padding", left="64px", right="64px") + " }"
    ),
    "h1": "font-size: 2.9rem; line-height: 0.9; letter-spacing: -0.048em; font-weight: 600; "
          "@media(--tablet) { font-size: 4.2rem; } @media(--laptop) { font-size: 5.95rem; }",
    "h2": "font-size: 2.1rem; line-height: 1.02; letter-spacing: -0.04em; font-weight: 600; "
          "@media(--tablet) { font-size: 3.1rem; } @media(--laptop) { font-size: 4.25rem; }",
    "h3": "font-size: 1.25rem; line-height: 1.15; letter-spacing: -0.03em; font-weight: 600; "
          "@media(--laptop) { font-size: 1.75rem; }",
    "lede": "font-size: 1.0625rem; line-height: 1.5; color: var(--ink-muted); max-width: 48ch; "
            "@media(--laptop) { font-size: 1.4375rem; }",
    "emph": "font-style: italic; font-weight: 400; letter-spacing: 0;",
    "data": "font-size: 0.75rem; letter-spacing: 0.14em; text-transform: uppercase;",
    "data-nano": "font-size: 0.6875rem; letter-spacing: 0.14em; text-transform: uppercase;",
    "eyebrow": "font-size: 0.6875rem; letter-spacing: 0.14em; text-transform: uppercase; color: var(--blue-deep);",
    "btn": (
        "display: inline-flex; align-items: center; "
        + gap(col="8px")
        + " font-size: 0.75rem; letter-spacing: 0.14em; text-transform: uppercase; "
        + box("padding", top="14px", bottom="14px", left="24px", right="24px")
        + radius(all_corners="var(--r-pill)")
        + border("top", color="var(--ink)") + border("right", color="var(--ink)")
        + border("bottom", color="var(--ink)") + border("left", color="var(--ink)")
        + " background-color: var(--ink); color: var(--paper); text-decoration: none; "
        + "&:hover { background-color: var(--blue); border-top-color: var(--blue); border-right-color: var(--blue); "
        + "border-bottom-color: var(--blue); border-left-color: var(--blue); color: #ffffff; }"
    ),
    "btn-ghost": (
        "background-color: transparent; color: var(--ink); "
        + border("top", color="var(--rule-strong)") + border("right", color="var(--rule-strong)")
        + border("bottom", color="var(--rule-strong)") + border("left", color="var(--rule-strong)")
        + " &:hover { background-color: var(--ink); color: var(--paper); border-top-color: var(--ink); "
        + "border-right-color: var(--ink); border-bottom-color: var(--ink); border-left-color: var(--ink); }"
    ),
    "btn-paper": (
        "background-color: var(--paper); color: var(--night); "
        + border("top", color="var(--paper)") + border("right", color="var(--paper)")
        + border("bottom", color="var(--paper)") + border("left", color="var(--paper)")
        + " &:hover { background-color: var(--blue); color: var(--paper); }"
    ),
    "section": (
        "position: relative; background-color: var(--paper); "
        + box("padding", top="96px", bottom="96px")
        + " @media(--laptop) { " + box("padding", top="160px", bottom="160px") + " }"
    ),
    "section-tight": box("padding", top="var(--s-8)", bottom="var(--s-8)"),
    "section-raised": "background-color: var(--paper-raised);",
    "stat-n": "font-size: 1.75rem; font-weight: 600; letter-spacing: -0.04em; line-height: 1; "
              "@media(--laptop) { font-size: 2.5rem; }",
    "stat-k": "color: var(--ink-muted);",
    "pc": (
        "position: relative; display: flex; flex-direction: column; "
        + border("top", color="var(--pc-rule, var(--rule-strong))")
        + border("right", color="var(--pc-rule, var(--rule-strong))")
        + border("bottom", color="var(--pc-rule, var(--rule-strong))")
        + border("left", color="var(--pc-rule, var(--rule-strong))")
        + radius(all_corners="var(--r-sm)")
        + " overflow: hidden; text-decoration: none; color: inherit; background-color: var(--pc-bg, var(--paper-raised));"
    ),
    "pc-band": box("padding", top="24px", right="24px", bottom="16px", left="24px")
        + " background-color: var(--pc-band, var(--paper-raised)); "
        + border("bottom", color="var(--pc-rule, var(--rule-strong))"),
    "pc-title": "font-size: 1.4rem; font-weight: 600; letter-spacing: -0.03em; "
                "@media(--laptop) { font-size: 2rem; }",
    "pc-fig": "position: relative; min-height: 240px; overflow: hidden; background-color: var(--pc-bg, var(--paper-raised));",
    "pc-image": "width: 100%; height: 100%; min-height: 240px; object-fit: cover;",
    "pc-foot": (
        "position: relative; display: flex; align-items: flex-end; justify-content: space-between; "
        + gap(col="16px")
        + box("padding", top="16px", right="24px", bottom="24px", left="24px")
        + " background-color: var(--pc-band, var(--paper-raised)); "
        + border("top", color="var(--pc-rule, var(--rule-strong))")
    ),
    "pc-desc": "font-size: 1rem; line-height: 1.45; color: var(--ink-muted); max-width: 38ch;",
    "feature-tile": (
        "display: flex; align-items: center; "
        + gap(col="0.65rem")
        + " min-height: 3.5rem; "
        + box("padding", top="0.7rem", right="0.8rem", bottom="0.7rem", left="0.8rem")
        + border("top", width="1px", color="rgba(1,94,255,0.24)") + border("right", width="1px", color="rgba(1,94,255,0.24)")
        + border("bottom", width="1px", color="rgba(1,94,255,0.24)") + border("left", width="1px", color="rgba(1,94,255,0.24)")
        + " background-color: var(--paper-raised); font-size: 0.72rem; font-weight: 600; letter-spacing: 0.055em; "
        + "text-transform: uppercase; color: var(--ink);"
    ),
    "pill": (
        "display: inline-flex; align-items: center; min-height: 44px; "
        + box("padding", top="10px", right="18px", bottom="10px", left="18px")
        + radius(all_corners="var(--r-pill)")
        + border("top", color="var(--rule-strong)") + border("right", color="var(--rule-strong)")
        + border("bottom", color="var(--rule-strong)") + border("left", color="var(--rule-strong)")
        + " background-color: var(--paper-raised); color: var(--ink); text-decoration: none; "
        + "&:hover { background-color: var(--ink); color: var(--paper); }"
    ),
    "faq-q": "font-size: 1.0625rem; font-weight: 600; letter-spacing: -0.02em; line-height: 1.3; "
             "@media(--laptop) { font-size: 1.375rem; }",
    "faq-a": "color: var(--ink-muted); font-size: 1rem; line-height: 1.55; max-width: 62ch;",
    "faq-row": border("top", color="var(--rule-strong)") + box("padding", top="24px", bottom="24px"),
}

CLASS_LABELS = sorted(GLOBAL_CLASSES.keys())

# ---------------------------------------------------------------------------
# Content — Austin, TX. Concrete copy, not placeholders; the sibling-city
# pill hrefs and the two Austin-specific FAQ answers are the parts to
# templatize with dynamic tags once the location CPT exists (docs/
# elementor-authoring.md §7) — flagged inline below, not silently implied.
# ---------------------------------------------------------------------------
NEARBY_AREAS = ["Round Rock", "Cedar Park", "Georgetown", "Pflugerville",
                "San Marcos", "Leander", "Kyle", "Buda"]

FAQ = [
    ("Do you work with businesses outside Austin?",
     "Yes — Apex works with businesses across the US. Austin is one of several markets we "
     "actively serve; the process, pricing and guarantee are the same everywhere."),
    ("How fast can an Austin campaign launch?",
     "Most accounts go live within one to two weeks of the strategy call, once tracking and "
     "the landing page are in place. Your free audit gives you a specific timeline before you "
     "commit to anything."),
    ("Is there a contract?",
     "No. Every plan is month-to-month. Leave anytime — no notice period, no termination fee, "
     "no conversation about it."),
    ("What's included in the free audit?",
     "We map your current spend, buying cycle and cost of a bad click, and tell you plainly "
     "what we'd change — before you pay us anything."),
    ("Do you work with competing businesses in the same city?",
     "We don't offer blanket exclusivity by default — most clients choose month-to-month "
     "specifically because nothing is locked up long-term. If category exclusivity in Austin "
     "matters for your business, raise it on the audit call and we'll tell you plainly whether "
     "it's workable for your industry and spend level."),
]

# Real, already-deployed production URLs — these are the exact same four
# illustrations the live homepage uses for its own service cards
# (wordpress/apex-landing-page/templates/template-apex-homepage.php), not
# new assets. No Media Library upload needed; they're already hosted.
ASSET_BASE = "https://apex-marketing.ai/wp-content/plugins/apex-landing-page/assets/images/homepage/opt/"
FOUNDER_PHOTO_URL = "https://apex-marketing.ai/wp-content/plugins/apex-landing-page/assets/images/homepage/nathan.jpg"

SERVICES = [
    ("Google Ads", None, ASSET_BASE + "google-ads-1400.webp",
     "Google Ads campaign and performance dashboard illustration",
     "Local search and map-pack campaigns built around Austin's highest-value neighborhoods.",
     "Google Certified Partner"),
    ("Meta Ads", None, ASSET_BASE + "meta-ads-1400.webp",
     "Social advertising campaign and audience dashboard illustration",
     "Facebook and Instagram campaigns, from new-customer offers to awareness across Central Texas.",
     None),
    ("SEO", None, ASSET_BASE + "seo-1400.webp",
     "SEO rankings and organic performance dashboard illustration",
     "Rankings, organic traffic, and a Google Business Profile that looks credible to Austin searchers.",
     None),
    ("Website", "development", ASSET_BASE + "web-development-1400.webp",
     "Responsive website design and development illustration",
     "Conversion-focused pages with call tracking, so every Austin lead source is accounted for.",
     None),
]


# ---------------------------------------------------------------------------
# Page tree
# ---------------------------------------------------------------------------

def build_tree():
    hero = div(
        "Location Hero", tag_attr="header",
        classes=["section"],
        style="background-color: var(--paper); overflow: hidden; "
              + box("padding", top="120px", bottom="64px"),
        children=[
            flex("Hero Container", tag_attr="div", classes=["container"],
                 style="flex-direction: column;",
                 children=[
                     flex("Hero Breadcrumb", classes=["data-nano"],
                          style="flex-direction: row; align-items: center; " + gap(col="8px")
                                + " color: var(--ink-muted); margin-bottom: var(--s-6);",
                          children=[
                              el("e-button", "Crumb Home", link={"destination": "/", "isTargetBlank": False, "tag": "a"},
                                 style="color: var(--ink-muted); background-color: transparent; padding-top:0; padding-right:0; padding-bottom:0; padding-left:0; border-top-width:0; border-right-width:0; border-bottom-width:0; border-left-width:0; text-transform:none; letter-spacing:0.14em; font-size:0.6875rem;",
                                 text={"content": "Home", "children": []}),
                              paragraph("Crumb Sep 1", "/", style="opacity: 0.5;"),
                              el("e-button", "Crumb Locations", link={"destination": "/locations/", "isTargetBlank": False, "tag": "a"},
                                 style="color: var(--ink-muted); background-color: transparent; padding-top:0; padding-right:0; padding-bottom:0; padding-left:0; border-top-width:0; border-right-width:0; border-bottom-width:0; border-left-width:0; text-transform:none; letter-spacing:0.14em; font-size:0.6875rem;",
                                 text={"content": "Locations", "children": []}),
                              paragraph("Crumb Sep 2", "/", style="opacity: 0.5;"),
                              paragraph("Crumb Current", "Austin, TX", style="color: var(--ink);"),
                          ]),
                     flex("Hero Lead", classes=["h1"],
                          style="flex-direction: column; max-width: 54rem; " + gap(row="var(--s-5)"),
                          children=[
                              paragraph("Hero Eyebrow", "Austin, TX · Paid Acquisition",
                                        classes=["eyebrow"]),
                              heading("Hero H1", "Austin businesses get more customers. Not more spend.",
                                      tag_attr="h1", classes=["h1"]),
                              paragraph("Hero Lede",
                                        "Google Ads, Meta Ads, SEO and web builds for the Austin market. "
                                        "No lock-in. No vague promises.",
                                        classes=["lede"]),
                              flex("Hero CTA Row", style="flex-direction: row; flex-wrap: wrap; align-items: center; "
                                                          + gap(row="var(--s-3)", col="var(--s-3)"),
                                   children=[
                                       button("Hero Book Call", "Book a strategy call", "#book", classes=["btn"]),
                                       button("Hero Call Phone", "Call (855) 740-9608", "tel:+18557409608",
                                              classes=["btn", "btn-ghost"]),
                                   ]),
                          ]),
                 ]),
            flex("Hero Rail", classes=["container", "data-nano"],
                 style="flex-direction: row; justify-content: space-between; flex-wrap: wrap; "
                       + gap(row="var(--s-3)", col="var(--s-3)")
                       + box("padding", top="var(--s-4)", bottom="var(--s-4)")
                       + " margin-top: var(--s-8); color: var(--ink-muted); "
                       + border("top", color="var(--rule-strong)"),
                 children=[
                     paragraph("Rail Channels", "Google Ads · Meta · SEO · Web"),
                     paragraph("Rail Area", "Serving Austin & Central Texas"),
                 ]),
        ],
    )

    proof_stats = flex(
        "Proof Stats Row", style="flex-direction: row; flex-wrap: wrap; " + gap(row="var(--s-7)", col="var(--s-7)"),
        children=[
            flex(f"Stat {label}", style="flex-direction: column; " + gap(row="4px"),
                 children=[
                     paragraph(f"Stat {label} N", value, classes=["stat-n"]),
                     paragraph(f"Stat {label} K", label, classes=["stat-k", "data-nano"]),
                 ])
            for value, label in [("10+", "years in marketing"), ("1000+", "accounts managed"),
                                  ("$10M+", "budget managed"), ("60-day", "money-back guarantee")]
        ],
    )
    proof = flex(
        "Proof Strip", tag_attr="section",
        style="background-color: var(--paper-raised); " + border("top", color="var(--rule-strong)")
              + border("bottom", color="var(--rule-strong)") + box("padding", top="var(--s-7)", bottom="var(--s-7)"),
        children=[
            flex("Proof Container", classes=["container"],
                 style="flex-direction: row; flex-wrap: wrap; justify-content: space-between; align-items: baseline; "
                       + gap(row="var(--s-5)", col="var(--s-7)"),
                 children=[
                     paragraph("Proof Eyebrow", "Why Austin businesses work with Apex",
                               classes=["data-nano"], tag_attr="span",
                               style="color: var(--ink-muted); max-width: 22ch;"),
                     proof_stats,
                 ]),
        ],
    )

    service_cards = [
        div(f"Service {title}", tag_attr="a", link={"destination": "#book", "isTargetBlank": False, "tag": "a"},
            classes=["pc"], style="flex-grow: 1; flex-shrink: 1; flex-basis: 45%; min-width: 320px; position: relative;",
            children=(
                ([paragraph(f"Service {title} Flag", flag, classes=["data-nano"],
                             style="position: absolute; top: 0; right: 0; z-index: 2; background-color: var(--blue); "
                                   "color: var(--paper); " + box("padding", top="6px", right="12px", bottom="6px", left="12px"))]
                 if flag else [])
                + [
                    div(f"Service {title} Band", classes=["pc-band"],
                        children=[heading(f"Service {title} Title",
                                           f"{title} {emph}".strip() if emph else title,
                                           tag_attr="h3", classes=["pc-title"])]),
                    div(f"Service {title} Fig", classes=["pc-fig"],
                        children=[image(f"Service {title} Image", src, alt, classes=["pc-image"])]),
                    flex(f"Service {title} Foot", classes=["pc-foot"],
                         style="flex-direction: row; align-items: flex-end; justify-content: space-between;",
                         children=[
                             paragraph(f"Service {title} Desc", desc, classes=["pc-desc"]),
                             paragraph(f"Service {title} Arrow", "→"),
                         ]),
                ]
            ))
        for title, emph, src, alt, desc, flag in SERVICES
    ]

    services = div(
        "Services Section", tag_attr="section", classes=["section"],
        children=[
            flex("Services Container", classes=["container"], style="flex-direction: column;",
                 children=[
                     flex("Services Head", style="flex-direction: column; " + gap(row="var(--s-6)")
                                                  + " margin-bottom: var(--s-8); max-width: 60rem;",
                          children=[
                              heading("Services H2", "Marketing channels built for the Austin market.",
                                      tag_attr="h2", classes=["h2"]),
                              paragraph("Services Lede",
                                        "Paid acquisition and the infrastructure that makes it convert "
                                        "— tuned to how Austin buyers actually search.", classes=["lede"]),
                              button("Services Audit CTA", "Free audit & consultation", "#book",
                                     classes=["btn", "btn-ghost"]),
                          ]),
                     flex("Services Grid", style="flex-direction: row; flex-wrap: wrap; "
                                                  + gap(row="var(--s-5)", col="var(--s-5)"),
                          children=service_cards),
                     flex("Feature Tile Grid", style="flex-direction: row; flex-wrap: wrap; "
                                                       + gap(row="var(--s-3)", col="var(--s-3)")
                                                       + " margin-top: var(--s-8);",
                          children=[
                              paragraph(f"Feature {i}", t, classes=["feature-tile"])
                              for i, t in enumerate(["Month-to-month terms", "Free audit & consultation",
                                                      "One dedicated contact", "60-day money-back guarantee"], start=1)
                          ]),
                     button("Full Pricing Link", "See full pricing →", "/#pricing",
                            classes=["btn", "btn-ghost"], style="align-self: center; margin-top: var(--s-6);"),
                 ]),
        ],
    )

    area_pills = flex(
        "Area Pills", style="flex-direction: row; flex-wrap: wrap; " + gap(row="var(--s-3)", col="var(--s-3)"),
        children=[
            # NOTE — these link to sibling location pages that don't exist yet
            # (one per nearby city). Swap for a dynamic-tag-driven repeater
            # over the location taxonomy once that CPT exists (elementor-
            # authoring.md §7); for now this is the interlinking PATTERN,
            # not a claim the targets are live.
            button(f"Area {name}", name, f"/locations/{name.lower().replace(' ', '-')}-tx/", classes=["pill"])
            for name in NEARBY_AREAS
        ],
    )
    service_area = div(
        "Service Area Section", tag_attr="section", classes=["section", "section-tight", "section-raised"],
        children=[
            flex("Service Area Container", classes=["container"], style="flex-direction: column;",
                 children=[
                     paragraph("Service Area Eyebrow", "Service area", classes=["data-nano"],
                               style="color: var(--ink-muted);"),
                     heading("Service Area H2", "Also serving the greater Austin area.",
                             tag_attr="h3", classes=["h3"], style="margin-top: var(--s-2); margin-bottom: var(--s-6);"),
                     area_pills,
                 ]),
        ],
    )

    faq_rows = []
    for i, (q, a) in enumerate(FAQ, start=1):
        faq_rows.append(
            flex(f"FAQ {i} Row", classes=["faq-row"], style="flex-direction: column; " + gap(row="var(--s-2)"),
                 children=[
                     heading(f"FAQ {i} Q", q, tag_attr="h3", classes=["faq-q"]),
                     paragraph(f"FAQ {i} A", a, classes=["faq-a"]),
                 ])
        )
    faq = div(
        "FAQ Section", tag_attr="section", classes=["section"],
        children=[
            flex("FAQ Container", classes=["container"], style="flex-direction: column;",
                 children=[
                     paragraph("FAQ Eyebrow", "Austin FAQ", classes=["data-nano"], style="color: var(--ink-muted);"),
                     heading("FAQ H2", "Questions from Austin businesses.", tag_attr="h2", classes=["h2"],
                             style="margin-top: var(--s-2); margin-bottom: var(--s-8); max-width: 16ch;"),
                     flex("FAQ List", style="flex-direction: column; max-width: 860px;", children=faq_rows),
                 ]),
        ],
    )
    # NOTE — always-visible Q&A, not a click-to-expand accordion: `<details>`
    # has no atomic Elementor equivalent in the released widget set. Swap
    # this section's contents into Elementor Pro's native Accordion widget
    # for collapse behaviour (docs/elementor-authoring.md §1a: atomic first,
    # mature V3/Pro widget where V4 has no equivalent) — same copy either way.

    founder = flex(
        "Founder Section", tag_attr="section", classes=["section", "section-raised"],
        style="flex-direction: row; flex-wrap: wrap; align-items: center;",
        children=[
            flex("Founder Container", classes=["container"],
                 style="flex-direction: row; flex-wrap: wrap; align-items: center; " + gap(row="var(--s-7)", col="var(--s-7)"),
                 children=[
                     div("Founder Portrait", style="flex-grow: 0; flex-shrink: 0; flex-basis: 280px; position: relative; "
                                                     "aspect-ratio: 4 / 5; overflow: hidden; " + radius(all_corners="18px")
                                                     + border("top", color="var(--rule-strong)") + border("right", color="var(--rule-strong)")
                                                     + border("bottom", color="var(--rule-strong)") + border("left", color="var(--rule-strong)"),
                         children=[
                             image("Founder Photo", FOUNDER_PHOTO_URL, "Nathan, founder of Apex Marketing",
                                   style="width: 100%; height: 100%; object-fit: cover;"),
                             paragraph("Founder Photo Caption", "Nathan, founder", classes=["data-nano"],
                                       style="position: absolute; left: 0; bottom: 0; background-color: var(--paper); "
                                             + box("padding", top="6px", right="10px", bottom="6px", left="10px")),
                         ]),
                     flex("Founder Copy", style="flex-grow: 1; flex-shrink: 1; flex-basis: 480px; flex-direction: column; "
                                                 + gap(row="var(--s-5)"),
                          children=[
                              heading("Founder H2", "Why I built Apex Marketing.", tag_attr="h2", classes=["h2"]),
                              paragraph("Founder Quote",
                                        "“I’ve managed over 1,000 ad accounts, and the story is almost "
                                        "always the same: a recycled playbook, vanity metrics, and a contract you "
                                        "can’t escape. So I made one rule — we report on the only number "
                                        "that matters. Appointments booked. Not impressions. Not clicks.”",
                                        style="font-size: 1.0625rem; line-height: 1.5; max-width: 52ch;"),
                              flex("Founder Stats", style="flex-direction: row; flex-wrap: wrap; " + gap(row="var(--s-6)", col="var(--s-6)"),
                                   children=[
                                       flex(f"Founder Stat {label}", style="flex-direction: column; " + gap(row="4px"),
                                            children=[
                                                paragraph(f"Founder Stat {label} N", value, classes=["stat-n"]),
                                                paragraph(f"Founder Stat {label} K", label, classes=["stat-k", "data-nano"]),
                                            ])
                                       for value, label in [("10+", "years in marketing"), ("1000+", "accounts managed"),
                                                             ("$10M+", "budget managed")]
                                   ]),
                              paragraph("Founder Signoff",
                                        "Nathan — founder, Apex Marketing. Most clients just call me Nate.",
                                        classes=["data-nano"], style="color: var(--ink-muted);"),
                              button("Founder Book CTA", "Book a call directly with Nathan", "#book", classes=["btn"]),
                          ]),
                 ]),
        ],
    )

    cta = flex(
        "Final CTA Section", tag_attr="section",
        style="flex-direction: row; background-color: var(--night); overflow: hidden; "
              + box("padding", top="var(--s-9)", bottom="var(--s-9)"),
        children=[
            flex("CTA Container", classes=["container"],
                 style="flex-direction: column; align-items: center; text-align: center; " + gap(row="var(--s-5)"),
                 children=[
                     paragraph("CTA Eyebrow", "Austin, TX", classes=["data-nano"], style="color: var(--mint);"),
                     heading("CTA H2", "Get more Austin customers without more spend.", tag_attr="h2",
                             classes=["h2"], style="color: var(--paper); max-width: 20ch;"),
                     paragraph("CTA Lede", "15-minute call · no pressure",
                               classes=["lede"], style="color: rgba(238,238,238,0.72);"),
                     flex("CTA Button Row", style="flex-direction: row; flex-wrap: wrap; justify-content: center; "
                                                   + gap(row="var(--s-3)", col="var(--s-3)"),
                          children=[
                              button("CTA Book Call", "Book a strategy call", "#book", classes=["btn", "btn-paper"]),
                              button("CTA Call Phone", "Call (855) 740-9608", "tel:+18557409608",
                                     classes=["btn", "btn-ghost"],
                                     style="color: var(--paper); border-top-color: rgba(238,238,238,0.3); "
                                           "border-right-color: rgba(238,238,238,0.3); border-bottom-color: rgba(238,238,238,0.3); "
                                           "border-left-color: rgba(238,238,238,0.3);"),
                          ]),
                 ]),
        ],
    )

    return [hero, proof, services, service_area, faq, founder, cta]


# ---------------------------------------------------------------------------
# Tree walk -> xml_structure / element_config / style / classes
# ---------------------------------------------------------------------------

def escape_attr(s):
    """Configuration-ids are derived from copy in a few places (feature-tile
    text, area names) — escape defensively rather than trust every future
    edit to hand-pick an XML-safe id."""
    return (s.replace("&", "&amp;").replace('"', "&quot;")
             .replace("<", "&lt;").replace(">", "&gt;"))


def render_xml(node):
    inner = "".join(render_xml(c) for c in node.children)
    return f'<{node.tag} configuration-id="{escape_attr(node.cid)}">{inner}</{node.tag}>'


def collect(node, config_out, style_out, classes_out):
    if node.config:
        config_out[node.cid] = node.config
    if node.style:
        style_out[node.cid] = node.style.strip()
    if node.classes:
        classes_out[node.cid] = node.classes
    for c in node.children:
        collect(c, config_out, style_out, classes_out)


def main():
    tree = build_tree()
    xml_structure = "".join(render_xml(n) for n in tree)

    # Well-formedness check: wrap in one root and parse.
    minidom.parseString(f"<root>{xml_structure}</root>")

    element_config, style, classes = {}, {}, {}
    for n in tree:
        collect(n, element_config, style, classes)

    # Every class label referenced must be a defined global class.
    used_labels = {label for labels in classes.values() for label in labels}
    unknown = used_labels - set(CLASS_LABELS)
    if unknown:
        raise SystemExit(f"Unknown class labels referenced: {sorted(unknown)}")

    # Every configuration-id must be unique.
    ids = []

    def walk_ids(n):
        ids.append(n.cid)
        for c in n.children:
            walk_ids(c)

    for n in tree:
        walk_ids(n)
    dupes = {i for i in ids if ids.count(i) > 1}
    if dupes:
        raise SystemExit(f"Duplicate configuration-id values: {sorted(dupes)}")

    payload = {
        "_readme": (
            "Payload for Elementor's `elementor/build-composition` MCP tool (not released "
            "yet — see docs/elementor-authoring.md §5). Apply in this order once reachable: "
            "1) elementor/manage-global-variable for each entry in global_variables, "
            "2) elementor/manage-classes for each entry in global_classes, "
            "3) elementor/build-composition with the composition block below, against the "
            "single-location template's content, parent_id: 'document', mode: 'replace_children'. "
            "See elementor-templates/README.md for the manual (no-MCP) path."
        ),
        "global_variables": GLOBAL_VARIABLES,
        "global_classes": [{"label": label, "style": css} for label, css in GLOBAL_CLASSES.items()],
        "composition": {
            "parent_id": "document",
            "mode": "replace_children",
            "xml_structure": xml_structure,
            "element_config": element_config,
            "style": style,
            "classes": classes,
        },
    }

    OUT_PATH.parent.mkdir(parents=True, exist_ok=True)
    OUT_PATH.write_text(json.dumps(payload, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")
    n_elements = len(ids)
    print(f"Wrote {OUT_PATH.relative_to(REPO_ROOT)}: {n_elements} elements, "
          f"{len(GLOBAL_VARIABLES)} global variables, {len(GLOBAL_CLASSES)} global classes.")


if __name__ == "__main__":
    main()
