#!/usr/bin/env python3
"""Generate the Elementor build-composition payload for the Austin, TX location page.

Mirrors design/location-pages/austin-tx.html section for section. Regenerate this
rather than hand-editing the JSON: the script validates configuration-id uniqueness,
XML well-formedness, and that every referenced global class is defined before writing.

WHY THIS FORMAT (not a raw $$type document JSON)
------------------------------------------------
Elementor's atomic settings/styles use a type-tagged {"$$type": ..., "value": ...} wire
format. Reading the PropType source (prop-types/base/object-prop-type.php) leaves a real
ambiguity about whether fields inside an object-shaped prop are each individually wrapped:
the base class reads as if they are, but the one concrete example in source
(Size_Prop_Type::generate in atomic-heading.php) passes them raw. Nothing available settles
it for a *style variant* specifically, there is no live Elementor here to test against, and
styles fail SOFT on a mismatch (silently unstyled, not an error).

elementor/build-composition sidesteps that entirely: plain XML + plain element_config +
plain CSS text, with Elementor's own server doing the type-wrapping and CSS conversion.
Input shape verified against modules/mcp/static-resources/abilities/build-composition.md
in elementor/elementor @ main, 2026-08-13.

Run: python3 scripts/build_elementor_location_composition.py
"""
import json
import xml.dom.minidom as minidom
from pathlib import Path

REPO_ROOT = Path(__file__).resolve().parent.parent
OUT_PATH = REPO_ROOT / "elementor-templates" / "location-page.json"

# --------------------------------------------------------------------------
# Longhand-only CSS helpers. The MCP guidance is explicit that shorthands can
# fall back to custom_css, which Pro 3.35+ strips.
# --------------------------------------------------------------------------

def box(prop, top=None, right=None, bottom=None, left=None):
    out = []
    for side, val in (("top", top), ("right", right), ("bottom", bottom), ("left", left)):
        if val is not None:
            out.append(f"{prop}-{side}: {val};")
    return " ".join(out)


def border(side, width="1px", color="var(--rule-strong)", style="solid"):
    return f"border-{side}-width: {width}; border-{side}-style: {style}; border-{side}-color: {color};"


def borders(width="1px", color="var(--rule-strong)"):
    return "".join(border(s, width, color) for s in ("top", "right", "bottom", "left"))


def radius(all_corners=None):
    if all_corners is None:
        return ""
    return "".join(f"border-{c}-radius: {all_corners};"
                   for c in ("top-left", "top-right", "bottom-right", "bottom-left"))


def gap(row=None, col=None):
    out = []
    if row is not None:
        out.append(f"row-gap: {row};")
    if col is not None:
        out.append(f"column-gap: {col};")
    return " ".join(out)


# --------------------------------------------------------------------------
# Element tree DSL
# --------------------------------------------------------------------------

class Node:
    def __init__(self, tag, cid, config=None, style=None, classes=None, children=None):
        self.tag, self.cid = tag, cid
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
    return Node(tag, cid, cfg, style, classes, children)


def div(cid, *, tag_attr="div", link=None, style=None, classes=None, children=None):
    return el("e-div-block", cid, tag_attr=tag_attr, link=link, style=style, classes=classes, children=children)


def flex(cid, *, tag_attr="div", link=None, style=None, classes=None, children=None):
    return el("e-flexbox", cid, tag_attr=tag_attr, link=link, style=style, classes=classes, children=children)


def heading(cid, text, *, tag_attr="h2", style=None, classes=None):
    return el("e-heading", cid, tag_attr=tag_attr, style=style, classes=classes,
              title={"content": text, "children": []})


def para(cid, text, *, tag_attr="p", style=None, classes=None):
    return el("e-paragraph", cid, tag_attr=tag_attr, style=style, classes=classes,
              paragraph={"content": text, "children": []})


def button(cid, text, href, *, style=None, classes=None, new_tab=False):
    return el("e-button", cid, style=style, classes=classes,
              text={"content": text, "children": []},
              link={"destination": href, "isTargetBlank": new_tab, "tag": "a"})


def image(cid, src, alt, *, style=None, classes=None):
    return el("e-image", cid, style=style, classes=classes,
              image={"src": {"url": src}, "size": "full"})


def divider(cid, *, style=None, classes=None):
    return el("e-divider", cid, style=style, classes=classes)


# --------------------------------------------------------------------------
# Global variables. Flat tokens only: a V4 Size variable is a structured
# {size, unit} pair with no slot for clamp(), so the fluid type scale, gutter,
# section-pad and grid-cell stay as literal CSS with @media steps instead.
# --------------------------------------------------------------------------
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
    {"label": "rule", "type": "color", "value": "rgba(0,0,0,0.10)"},
    {"label": "rule-strong", "type": "color", "value": "rgba(0,0,0,0.22)"},
    {"label": "tone-mint", "type": "color", "value": "#198F7B"},
    {"label": "tone-lavender", "type": "color", "value": "#6354C7"},
    {"label": "pc-a-bg", "type": "color", "value": "#F6F8DC"},
    {"label": "pc-a-band", "type": "color", "value": "#EAF1A8"},
    {"label": "pc-a-rule", "type": "color", "value": "#A7B833"},
    {"label": "pc-b-bg", "type": "color", "value": "#FBEDF8"},
    {"label": "pc-b-band", "type": "color", "value": "#F5D6EE"},
    {"label": "pc-b-rule", "type": "color", "value": "#D86ABF"},
    {"label": "pc-c-bg", "type": "color", "value": "#EDF9EE"},
    {"label": "pc-c-band", "type": "color", "value": "#D8F1D9"},
    {"label": "pc-c-rule", "type": "color", "value": "#63AD6A"},
    {"label": "pc-d-bg", "type": "color", "value": "#EEF0FF"},
    {"label": "pc-d-band", "type": "color", "value": "#DDE1FF"},
    {"label": "pc-d-rule", "type": "color", "value": "#7587E8"},
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

# --------------------------------------------------------------------------
# Global classes. Defined once, referenced by label. This is what keeps a
# 20-city rollout to "duplicate and edit copy" rather than "restyle 20 pages".
# Accessibility values here are the post-review ones (white on blue at 5.19:1,
# 12px nano floor on mobile) rather than the design system's originals.
# --------------------------------------------------------------------------
GLOBAL_CLASSES = {
    "container": (
        "width: 100%; margin-left: auto; margin-right: auto; max-width: var(--container-max); "
        + box("padding", left="20px", right="20px")
        + " @media(--tablet) { " + box("padding", left="40px", right="40px") + " }"
        + " @media(--laptop) { " + box("padding", left="64px", right="64px") + " }"
    ),
    "hero-h1": (
        "font-size: 2.9rem; line-height: 0.9; letter-spacing: -0.048em; font-weight: 600; "
        "@media(--tablet) { font-size: 4.2rem; } @media(--laptop) { font-size: 5.95rem; }"
    ),
    "h2": (
        "font-size: 2.1rem; line-height: 1.02; letter-spacing: -0.04em; font-weight: 600; "
        "@media(--tablet) { font-size: 3.1rem; } @media(--laptop) { font-size: 4.25rem; }"
    ),
    "h3": "font-size: 1.25rem; line-height: 1.15; letter-spacing: -0.03em; font-weight: 600; "
          "@media(--laptop) { font-size: 1.75rem; }",
    "lede": "font-size: 1.0625rem; line-height: 1.5; color: var(--ink-muted); max-width: 48ch; "
            "@media(--laptop) { font-size: 1.4375rem; }",
    "emph": "font-style: italic; font-weight: 400; letter-spacing: 0;",
    "data": "font-size: 0.75rem; letter-spacing: 0.14em; text-transform: uppercase;",
    # 12px floor on mobile: design.md §8, and the packaged system dropped it.
    "data-nano": "font-size: 0.75rem; letter-spacing: 0.14em; text-transform: uppercase; "
                 "@media(--laptop) { font-size: 0.6875rem; }",
    "btn": (
        "display: inline-flex; align-items: center; justify-content: center; min-height: 44px; "
        + gap(col="8px")
        + " font-size: 0.75rem; letter-spacing: 0.14em; text-transform: uppercase; "
        + box("padding", top="14px", bottom="14px", left="24px", right="24px")
        + radius("var(--r-pill)") + borders(color="var(--ink)")
        + " background-color: var(--ink); color: var(--paper); text-decoration: none; "
        + "&:hover { background-color: var(--blue); border-top-color: var(--blue); "
          "border-right-color: var(--blue); border-bottom-color: var(--blue); "
          "border-left-color: var(--blue); color: #ffffff; }"
    ),
    "btn-ghost": (
        "background-color: transparent; color: var(--ink); " + borders(color="var(--rule-strong)")
        + " &:hover { background-color: var(--ink); color: var(--paper); border-top-color: var(--ink); "
          "border-right-color: var(--ink); border-bottom-color: var(--ink); border-left-color: var(--ink); }"
    ),
    "section": (
        "position: relative; background-color: var(--paper); "
        + box("padding", top="96px", bottom="96px")
        + " @media(--laptop) { " + box("padding", top="160px", bottom="160px") + " }"
    ),
    "section-raised": "background-color: var(--paper-raised);",
    "section-tight": box("padding", top="64px", bottom="64px"),
    # The construction grid: a repeating linear-gradient background, which IS
    # expressible in the atomic style schema (design.md §9 item 5).
    "grid-field": (
        "position: absolute; top: 0; right: 0; bottom: 0; left: 0; pointer-events: none; opacity: 0.05; "
        "background-image: linear-gradient(to right, var(--ink) 1px, transparent 1px), "
        "linear-gradient(to bottom, var(--ink) 1px, transparent 1px); "
        "background-size: 220px 220px;"
    ),
    "plate": (
        "position: relative; background-color: var(--paper-raised); " + borders()
        + radius("var(--r-sm)") + " overflow: hidden;"
    ),
    "plate-row": (
        "display: flex; justify-content: space-between; align-items: baseline; "
        + gap(col="16px") + box("padding", top="12px", bottom="12px")
        + border("bottom", color="var(--rule)")
    ),
    "stat-n": "font-size: 1.75rem; font-weight: 600; letter-spacing: -0.04em; line-height: 1; "
              "font-variant-numeric: tabular-nums; @media(--laptop) { font-size: 2.5rem; }",
    "stat-k": "color: var(--ink-muted);",
    "pc": (
        "position: relative; display: flex; flex-direction: column; flex-grow: 1; flex-shrink: 1; "
        "flex-basis: 45%; min-width: 320px; " + borders(color="var(--pc-rule)")
        + radius("var(--r-sm)") + " overflow: hidden; text-decoration: none; color: inherit; "
        "background-color: var(--pc-bg);"
    ),
    "pc-band": box("padding", top="24px", right="24px", bottom="16px", left="24px")
               + " background-color: var(--pc-band); " + border("bottom", color="var(--pc-rule)"),
    "pc-title": "font-size: 1.4rem; font-weight: 600; letter-spacing: -0.03em; "
                "@media(--laptop) { font-size: 2rem; }",
    "pc-fig": "position: relative; min-height: 240px; overflow: hidden; background-color: var(--pc-bg);",
    "pc-image": "width: 100%; height: 100%; min-height: 240px; object-fit: cover;",
    "pc-foot": (
        "position: relative; display: flex; align-items: flex-end; justify-content: space-between; "
        + gap(col="16px") + box("padding", top="16px", right="24px", bottom="24px", left="24px")
        + " background-color: var(--pc-band); " + border("top", color="var(--pc-rule)")
    ),
    "pc-desc": "font-size: 1rem; line-height: 1.45; color: var(--ink-muted); max-width: 38ch;",
    # White, not paper: paper on blue is 4.47:1 and fails AA at this size.
    "pc-flag": (
        "position: absolute; top: 0; right: 0; z-index: 2; background-color: var(--blue); "
        "color: #FFFFFF; " + box("padding", top="6px", right="12px", bottom="6px", left="12px")
    ),
    "clause-term": "font-size: 1.2rem; font-weight: 600; letter-spacing: -0.032em; line-height: 1.14; "
                   "color: var(--ink); @media(--laptop) { font-size: 1.9rem; }",
    "clause-note": "font-size: 1rem; line-height: 1.55; color: var(--ink-muted); max-width: 46ch;",
    "feature-tile": (
        "display: flex; align-items: center; " + gap(col="0.65rem") + " min-height: 3.5rem; "
        + box("padding", top="0.7rem", right="0.8rem", bottom="0.7rem", left="0.8rem")
        + borders(color="rgba(1,94,255,0.24)")
        + " background-color: var(--paper-raised); font-size: 0.75rem; font-weight: 600; "
          "letter-spacing: 0.055em; text-transform: uppercase; color: var(--ink);"
    ),
    "pill": (
        "display: inline-flex; align-items: center; min-height: 44px; "
        + box("padding", top="10px", right="18px", bottom="10px", left="18px")
        + radius("var(--r-pill)") + borders(color="var(--rule-strong)")
        + " background-color: var(--paper-raised); color: var(--ink);"
    ),
    "faq-row": border("top", color="var(--rule-strong)") + box("padding", top="24px", bottom="24px"),
    "faq-q": "font-size: 1.0625rem; font-weight: 600; letter-spacing: -0.02em; line-height: 1.3; "
             "@media(--laptop) { font-size: 1.375rem; }",
    "faq-a": "color: var(--ink-muted); font-size: 1rem; line-height: 1.55; max-width: 62ch;",
}

CLASS_LABELS = set(GLOBAL_CLASSES)

# --------------------------------------------------------------------------
# Content. Austin, TX. Service descriptions are the homepage's, unmodified:
# the service is identical in every market, so the copy is too. Local
# specificity lives only where it is genuinely checkable.
# --------------------------------------------------------------------------
NEARBY = ["Round Rock", "Cedar Park", "Georgetown", "Pflugerville",
          "San Marcos", "Leander", "Kyle", "Buda"]

PLATE = [("Market", "Austin, TX"), ("Counties", "Travis, Williamson, Hays"),
         ("Areas served", "8"), ("Time zone", "Central"), ("Plans from", "$2,500 / mo")]

STATS = [("10+", "years in marketing"), ("1000+", "accounts managed"), ("$10M+", "budget managed")]

SERVICES = [
    ("Google Ads", None, "a", "google-ads-1400.webp",
     "Google Ads campaign and performance dashboard illustration",
     "Local search and map-pack campaigns built around your highest-value services.",
     "Google Certified Partner"),
    ("Meta Ads", None, "b", "meta-ads-1400.webp",
     "Social advertising campaign and audience dashboard illustration",
     "Facebook and Instagram campaigns, from new-customer offers to awareness.", None),
    ("SEO", None, "c", "seo-1400.webp",
     "SEO rankings and organic performance dashboard illustration",
     "Rankings, organic traffic, and a Google Business Profile that looks credible.", None),
    ("Website", "development", "d", "web-development-1400.webp",
     "Responsive website design and development illustration",
     "Conversion-focused pages with call tracking, so every source is accounted for.", None),
]

CLAUSES = [
    ("blue", "var(--blue)", "Month to month. Leave anytime.",
     "No notice period. No termination fee. No conversation about it. You stay because the work is worth staying for."),
    ("mint", "var(--tone-mint)", "Every engagement starts with a free audit.",
     "We map your offer, buying cycle and current spend before you commit to anything. No cost, no obligation."),
    ("lavender", "var(--tone-lavender)", "Reporting in booked appointments.",
     "One number, walked through by a real person every month. Impressions and reach are not reported, because they are not the point."),
    ("rose", "var(--pink-hot)", "60 days, or every dollar back.",
     "In writing. No fine print, no qualifying conditions, no minimum spend threshold."),
]

TILES = ["Month-to-month terms", "Free audit and consultation",
         "One dedicated contact", "60-day money-back guarantee"]

FAQ = [
    ("Which areas around Austin do you cover?",
     "The Austin metro across Travis, Williamson and Hays counties, including Round Rock, "
     "Cedar Park, Georgetown, Pflugerville, San Marcos, Leander, Kyle and Buda. If you are "
     "just outside that list, ask on the audit call."),
    ("How fast can a campaign launch?",
     "Most accounts go live within one to two weeks of the strategy call, once tracking and "
     "the landing page are in place. Your free audit gives you a specific timeline before you "
     "commit to anything."),
    ("Is there a contract?",
     "No. Every plan is month-to-month. Leave anytime. No notice period, no termination fee, "
     "no conversation about it."),
    ("What does the free audit include?",
     "We map your current spend, buying cycle and cost of a bad click, then tell you plainly "
     "what we would change. It happens before you pay us anything."),
    ("Do you work with competing businesses in the same city?",
     "We do not offer blanket exclusivity by default. Most clients choose month-to-month "
     "specifically because nothing is locked up long term. If category exclusivity in Austin "
     "matters to you, raise it on the audit call and we will tell you plainly whether it is "
     "workable for your industry and spend level."),
    ("What does it cost?",
     "Plans start at $2,500 a month and every price is published, including what each channel "
     "costs on its own. See the full pricing table on the homepage."),
]

ASSETS = "https://apex-marketing.ai/wp-content/plugins/apex-landing-page/assets/images/homepage/opt/"
NATHAN = "https://apex-marketing.ai/wp-content/plugins/apex-landing-page/assets/images/homepage/nathan.jpg"
GHL_FORM = "https://api.leadconnectorhq.com/widget/form/PV33s1v3pTF8y2bzSIIs"

SECTION_PAD = box("padding", top="96px", bottom="96px") + " @media(--laptop) { " + box("padding", top="160px", bottom="160px") + " }"


def grid_field(cid):
    return div(cid, classes=["grid-field"])


def section_head(prefix, title, lede=None, cta=None):
    kids = [flex(f"{prefix} Head Title", style="flex-direction: column;",
                 children=[heading(f"{prefix} H2", title, classes=["h2"], style="max-width: 18ch;")])]
    intro = []
    if lede:
        intro.append(para(f"{prefix} Lede", lede, classes=["lede"], style="max-width: 34ch;"))
    if cta:
        intro.append(button(f"{prefix} CTA", cta, "#book", classes=["btn", "btn-ghost"]))
    if intro:
        kids.append(flex(f"{prefix} Head Intro",
                         style="flex-direction: column; align-items: flex-start; " + gap(row="16px"),
                         children=intro))
    return flex(f"{prefix} Head",
                style="flex-direction: column; " + gap(row="32px") + " margin-bottom: 64px; max-width: 62rem;",
                children=kids)


def build_tree():
    # ---- I. HERO ---------------------------------------------------------
    plate_rows = [
        flex(f"Plate Row {k}", classes=["plate-row"], style="flex-direction: row;",
             children=[para(f"Plate Key {k}", k, classes=["data-nano"], style="color: var(--ink-muted);"),
                       para(f"Plate Value {k}", v, classes=["data-nano"],
                            style="color: var(--ink); text-align: right; font-variant-numeric: tabular-nums;")])
        for k, v in PLATE
    ]
    hero = div("Hero", tag_attr="header",
               style="position: relative; background-color: var(--paper); overflow: hidden; "
                     + box("padding", top="112px", bottom="64px")
                     + " @media(--laptop) { " + box("padding", top="168px", bottom="64px") + " }",
               children=[
                   grid_field("Hero Grid"),
                   flex("Hero Container", classes=["container"],
                        style="position: relative; z-index: 2; flex-direction: column;",
                        children=[
                            flex("Hero Breadcrumb", classes=["data-nano"],
                                 style="flex-direction: row; align-items: center; flex-wrap: wrap; "
                                       + gap(col="8px") + " color: var(--ink-muted); margin-bottom: 32px;",
                                 children=[
                                     button("Crumb Home", "Home", "/", classes=["data-nano"],
                                            style="background-color: transparent; color: var(--ink-muted); "
                                                  "border-top-width: 0; border-right-width: 0; border-bottom-width: 0; "
                                                  "border-left-width: 0; min-height: 44px; "
                                                  + box("padding", top="0", right="4px", bottom="0", left="4px")),
                                     para("Crumb Sep", "/", style="opacity: 0.45;"),
                                     para("Crumb Current", "Austin, TX", style="color: var(--ink);"),
                                 ]),
                            flex("Hero Cols",
                                 style="flex-direction: column; " + gap(row="48px", col="48px")
                                       + " @media(--laptop) { flex-direction: row; align-items: flex-start; }",
                                 children=[
                                     flex("Hero Lead",
                                          style="flex-direction: column; align-items: flex-start; flex-grow: 1; "
                                                "flex-shrink: 1; flex-basis: 58%; " + gap(row="24px"),
                                          children=[
                                              heading("Hero H1", "More customers in Austin. Not more spend.",
                                                      tag_attr="h1", classes=["hero-h1"]),
                                              para("Hero Lede",
                                                   "Paid ads for businesses across the Austin metro. No lock-in. No vague promises.",
                                                   classes=["lede"]),
                                              flex("Hero CTA Row",
                                                   style="flex-direction: row; flex-wrap: wrap; align-items: center; "
                                                         + gap(row="12px", col="12px"),
                                                   children=[
                                                       button("Hero Book", "Book a strategy call", "#book", classes=["btn"]),
                                                       button("Hero Call", "Call (855) 740-9608", "tel:+18557409608",
                                                              classes=["btn", "btn-ghost"]),
                                                   ]),
                                          ]),
                                     # The survey plate: the annotation drum doing its
                                     # actual job. Every value is checkable, none is a claim.
                                     div("Hero Plate", classes=["plate"],
                                         style="flex-grow: 0; flex-shrink: 1; flex-basis: 34%; "
                                               "align-self: flex-start; width: 100%; max-width: 420px;",
                                         children=[
                                             div("Hero Plate Grid", classes=["grid-field"], style="opacity: 0.085;"),
                                             flex("Hero Plate Body",
                                                  style="position: relative; z-index: 1; flex-direction: column; "
                                                        + box("padding", top="24px", right="24px", bottom="16px", left="24px"),
                                                  children=plate_rows),
                                         ]),
                                 ]),
                            # The rail carries the plate index, like the homepage's
                            # hero rail. It is the document's contents, not decoration.
                            flex("Hero Rail", classes=["data-nano"],
                                 style="flex-direction: column; align-items: flex-start; margin-top: 64px; "
                                       + box("padding", top="16px", bottom="16px")
                                       + border("top", color="var(--rule-strong)")
                                       + " color: var(--ink-muted); " + gap(row="8px")
                                       + " @media(--laptop) { flex-direction: row; justify-content: space-between; align-items: center; }",
                                 children=[
                                     para("Rail Channels", "Google Ads, Meta, SEO and web"),
                                     para("Rail Area", "Serving Austin and Central Texas"),
                                 ]),
                        ]),
               ])

    # ---- II. TRACK RECORD ------------------------------------------------
    proof = div("Proof", tag_attr="section",
                style="position: relative; background-color: var(--paper-raised); "
                      + border("top", color="var(--rule-strong)") + border("bottom", color="var(--rule-strong)")
                      + box("padding", top="48px", bottom="48px"),
                children=[
                    flex("Proof Container", classes=["container"],
                         style="flex-direction: column; " + gap(row="24px", col="48px")
                               + " @media(--laptop) { flex-direction: row; justify-content: space-between; align-items: baseline; }",
                         children=[
                             para("Proof Label", "Track record, across every market we work in",
                                  classes=["data-nano"], style="color: var(--ink-muted); max-width: 30ch;"),
                             flex("Proof Stats",
                                  style="flex-direction: row; flex-wrap: wrap; " + gap(row="24px", col="48px"),
                                  children=[
                                      flex(f"Stat {label}", style="flex-direction: column; " + gap(row="4px"),
                                           children=[para(f"Stat {label} N", value, classes=["stat-n"]),
                                                     para(f"Stat {label} K", label, classes=["stat-k", "data-nano"])])
                                      for value, label in STATS
                                  ]),
                         ]),
                ])

    # ---- III. SERVICES ---------------------------------------------------
    cards = []
    for title, emph, tone, img, alt, desc, flag in SERVICES:
        kids = []
        if flag:
            kids.append(para(f"Service {title} Flag", flag, classes=["pc-flag", "data-nano"]))
        kids += [
            div(f"Service {title} Band", classes=["pc-band"],
                children=[heading(f"Service {title} Title", f"{title} {emph}".strip() if emph else title,
                                  tag_attr="h3", classes=["pc-title"])]),
            div(f"Service {title} Fig", classes=["pc-fig"],
                children=[image(f"Service {title} Image", ASSETS + img, alt, classes=["pc-image"])]),
            flex(f"Service {title} Foot", classes=["pc-foot"], style="flex-direction: row;",
                 children=[para(f"Service {title} Desc", desc, classes=["pc-desc"]),
                           para(f"Service {title} Arrow", "→", style="color: var(--pc-rule); font-size: 1.25rem;")]),
        ]
        cards.append(div(f"Service {title}", tag_attr="a",
                         link={"destination": "#book", "isTargetBlank": False, "tag": "a"},
                         classes=["pc"], style=f"--pc-bg: var(--pc-{tone}-bg); --pc-band: var(--pc-{tone}-band); "
                                               f"--pc-rule: var(--pc-{tone}-rule);",
                         children=kids))

    services = div("Services", tag_attr="section", classes=["section"], children=[
        grid_field("Services Grid"),
        flex("Services Container", classes=["container"],
             style="position: relative; z-index: 2; flex-direction: column;",
             children=[
                 section_head("Services", "Marketing channels that bring in leads.",
                              "Paid acquisition and the infrastructure that makes it convert. "
                              "Not a full-service shop doing eleven things adequately.",
                              "Free audit and consultation"),
                 flex("Services Cards", style="flex-direction: row; flex-wrap: wrap; " + gap(row="24px", col="24px"),
                      children=cards),
             ]),
    ])

    # ---- IV. TERMS -------------------------------------------------------
    clause_rows = []
    for tone, accent, term, note in CLAUSES:
        clause_rows.append(
            flex(f"Clause {tone}",
                 style="flex-direction: column; " + gap(row="12px")
                       + box("padding", top="24px", bottom="24px")
                       + border("bottom", color="var(--rule-strong)")
                       + " @media(--laptop) { flex-direction: row; align-items: flex-start; column-gap: 32px; }",
                 children=[
                     heading(f"Clause {tone} Term", term, tag_attr="h3", classes=["clause-term"],
                             style="flex-grow: 0; flex-shrink: 0; flex-basis: 40%;"),
                     divider(f"Clause {tone} Bar",
                             style=f"flex-grow: 0; flex-shrink: 0; flex-basis: 56px; height: 3px; "
                                   f"background-color: {accent}; border-top-width: 0; margin-top: 8px;"),
                     para(f"Clause {tone} Note", note, classes=["clause-note"],
                          style="flex-grow: 1; flex-shrink: 1; flex-basis: 40%;"),
                 ]))

    terms = div("Terms", tag_attr="section", classes=["section", "section-raised"], children=[
        grid_field("Terms Grid"),
        flex("Terms Container", classes=["container"],
             style="position: relative; z-index: 2; flex-direction: column;",
             children=[
                 section_head("Terms", "Terms built around your business, not an agency contract."),
                 flex("Terms Stack", style="flex-direction: column; " + border("top", color="var(--rule-strong)"),
                      children=clause_rows),
                 flex("Terms Tiles",
                      style="flex-direction: row; flex-wrap: wrap; " + gap(row="12px", col="12px")
                            + " margin-top: 64px; max-width: 1100px;",
                      children=[para(f"Tile {i}", t, classes=["feature-tile"],
                                     style="flex-grow: 1; flex-shrink: 1; flex-basis: 220px;")
                                for i, t in enumerate(TILES, 1)]),
             ]),
    ])

    # ---- V. SERVICE AREA -------------------------------------------------
    # Plain text, not links: the sibling location pages do not exist yet.
    # They become buttons with dynamic permalinks once the location CPT ships.
    area = div("Area", tag_attr="section", classes=["section", "section-tight"], children=[
        grid_field("Area Grid"),
        flex("Area Container", classes=["container"],
             style="position: relative; z-index: 2; flex-direction: column;",
             children=[
                 heading("Area H2", "Also serving the greater Austin area.", classes=["h3"]),
                 para("Area Counties", "Travis, Williamson and Hays counties", classes=["data-nano"],
                      style="color: var(--ink-muted); margin-top: 8px; margin-bottom: 32px;"),
                 flex("Area Pills", style="flex-direction: row; flex-wrap: wrap; " + gap(row="12px", col="12px"),
                      children=[para(f"Area {n}", n, classes=["pill"]) for n in NEARBY]),
             ]),
    ])

    # ---- VI. FAQ ---------------------------------------------------------
    # Always-visible Q&A. <details> has no atomic equivalent; swap this
    # section's contents into Elementor Pro's Accordion for collapse behaviour.
    faq_rows = [
        flex(f"FAQ {i}", classes=["faq-row"], style="flex-direction: column; " + gap(row="8px"),
             children=[heading(f"FAQ {i} Q", q, tag_attr="h3", classes=["faq-q"]),
                       para(f"FAQ {i} A", a, classes=["faq-a"])])
        for i, (q, a) in enumerate(FAQ, 1)
    ]
    faq = div("FAQ", tag_attr="section", classes=["section", "section-raised"], children=[
        grid_field("FAQ Grid"),
        flex("FAQ Container", classes=["container"],
             style="position: relative; z-index: 2; flex-direction: column;",
             children=[
                 section_head("FAQ", "Questions from Austin businesses."),
                 flex("FAQ List", style="flex-direction: column; max-width: 860px;", children=faq_rows),
             ]),
    ])

    # ---- VII. FOUNDER ----------------------------------------------------
    founder = div("Founder", tag_attr="section", classes=["section"], children=[
        grid_field("Founder Grid"),
        flex("Founder Container", classes=["container"],
             style="position: relative; z-index: 2; flex-direction: column; " + gap(row="48px", col="48px")
                   + " @media(--laptop) { flex-direction: row; align-items: center; }",
             children=[
                 div("Founder Portrait",
                     style="flex-grow: 0; flex-shrink: 0; flex-basis: 300px; max-width: 320px; position: relative; "
                           "aspect-ratio: 4 / 5; overflow: hidden; " + radius("18px") + borders(),
                     children=[image("Founder Photo", NATHAN, "Nathan, founder of Apex Marketing",
                                     style="width: 100%; height: 100%; object-fit: cover;")]),
                 flex("Founder Copy",
                      style="flex-grow: 1; flex-shrink: 1; flex-basis: 55%; flex-direction: column; "
                            "align-items: flex-start; " + gap(row="24px"),
                      children=[
                          heading("Founder H2", "Why I built Apex Marketing.", classes=["h2"]),
                          para("Founder Quote",
                               "“I’ve managed over 1,000 ad accounts, and the story is almost always "
                               "the same: a recycled playbook, vanity metrics, and a contract you can’t "
                               "escape. So I made one rule. We report on the only number that matters. "
                               "Appointments booked. Not impressions. Not clicks.”",
                               style="font-size: 1.0625rem; line-height: 1.5; max-width: 52ch; "
                                     "@media(--laptop) { font-size: 1.3125rem; }"),
                          para("Founder Sig", "Nathan, founder of Apex Marketing. Most clients just call me Nate.",
                               style="color: var(--ink-muted); font-size: 0.875rem;"),
                          button("Founder CTA", "Book a call directly with Nathan", "#book", classes=["btn"]),
                      ]),
             ]),
    ])

    # ---- VIII. BOOK ------------------------------------------------------
    # The form itself is an Elementor HTML widget (see README): there is no
    # atomic iframe element, and this is the sanctioned V3 fallback rather
    # than faking an embed out of atomic parts.
    book = div("Book", tag_attr="section",
               style="position: relative; background-color: #000000; color: var(--paper); overflow: hidden; "
                     + box("padding", top="96px", bottom="96px"),
               children=[
                   flex("Book Container", classes=["container"],
                        style="position: relative; z-index: 2; flex-direction: column; " + gap(row="48px", col="48px")
                              + " @media(--laptop) { flex-direction: row; align-items: flex-start; }",
                        children=[
                            flex("Book Copy",
                                 style="flex-grow: 1; flex-shrink: 1; flex-basis: 42%; flex-direction: column; "
                                       "align-items: flex-start; " + gap(row="24px"),
                                 children=[
                                     heading("Book H2", "Book your free Austin audit.", classes=["h2"],
                                             style="color: var(--paper); max-width: 14ch;"),
                                     flex("Book List", style="flex-direction: column; " + gap(row="12px"),
                                          children=[
                                              para("Book Point 1", "15 minutes, no pressure, no pitch deck.",
                                                   style="color: rgba(238,238,238,0.82);"),
                                              para("Book Point 2", "We map your current spend before you commit to anything.",
                                                   style="color: rgba(238,238,238,0.82);"),
                                              para("Book Point 3", "You leave with a specific timeline and a number, whether or not you hire us.",
                                                   style="color: rgba(238,238,238,0.82);"),
                                          ]),
                                     button("Book Call", "Call (855) 740-9608", "tel:+18557409608",
                                            classes=["btn", "btn-ghost"],
                                            style="color: var(--paper); border-top-color: rgba(238,238,238,0.3); "
                                                  "border-right-color: rgba(238,238,238,0.3); "
                                                  "border-bottom-color: rgba(238,238,238,0.3); "
                                                  "border-left-color: rgba(238,238,238,0.3);"),
                                 ]),
                            div("Book Form Slot",
                                style="flex-grow: 1; flex-shrink: 1; flex-basis: 52%; width: 100%; "
                                      "min-height: 640px; background-color: var(--paper); " + radius("var(--r-sm)")),
                        ]),
               ])

    return [hero, proof, services, terms, area, faq, founder, book]


# --------------------------------------------------------------------------

def escape_attr(s):
    return (s.replace("&", "&amp;").replace('"', "&quot;")
             .replace("<", "&lt;").replace(">", "&gt;"))


def render_xml(node):
    inner = "".join(render_xml(c) for c in node.children)
    return f'<{node.tag} configuration-id="{escape_attr(node.cid)}">{inner}</{node.tag}>'


def collect(node, cfg, sty, cls):
    if node.config:
        cfg[node.cid] = node.config
    if node.style:
        sty[node.cid] = " ".join(node.style.split())
    if node.classes:
        cls[node.cid] = node.classes
    for c in node.children:
        collect(c, cfg, sty, cls)


def main():
    tree = build_tree()
    xml_structure = "".join(render_xml(n) for n in tree)
    minidom.parseString(f"<root>{xml_structure}</root>")  # well-formedness gate

    cfg, sty, cls = {}, {}, {}
    for n in tree:
        collect(n, cfg, sty, cls)

    unknown = {l for ls in cls.values() for l in ls} - CLASS_LABELS
    if unknown:
        raise SystemExit(f"Unknown class labels referenced: {sorted(unknown)}")

    ids = []

    def walk(n):
        ids.append(n.cid)
        for c in n.children:
            walk(c)

    for n in tree:
        walk(n)
    dupes = sorted({i for i in ids if ids.count(i) > 1})
    if dupes:
        raise SystemExit(f"Duplicate configuration-id values: {dupes}")

    # No em-dash may reach the page: taste-skill 9.G, zero tolerance.
    blob = json.dumps([cfg, sty], ensure_ascii=False)
    for ch, name in (("—", "em-dash"), ("–", "en-dash")):
        if ch in blob:
            raise SystemExit(f"{name} found in generated copy; rewrite the sentence.")

    payload = {
        "_readme": (
            "Payload for Elementor's `elementor/build-composition` MCP tool. Apply in order: "
            "1) elementor/manage-global-variable per entry in global_variables, "
            "2) elementor/manage-classes per entry in global_classes, "
            "3) elementor/build-composition with the composition block, against the "
            "single-location template, parent_id 'document', mode 'replace_children'. "
            "Then add the booking form as an HTML widget inside 'Book Form Slot' "
            "(see elementor-templates/README.md). Header, footer and nav are Theme Builder "
            "parts and are deliberately not in this composition."
        ),
        "manual_steps": [
            {
                "target": "Book Form Slot",
                "why": "No atomic iframe element exists; this is the sanctioned V3 fallback.",
                "action": "Drop an Elementor HTML widget inside this div and paste the iframe.",
                "html": ('<iframe src="' + GHL_FORM + '" title="Book a free strategy call with Apex Marketing" '
                         'style="width:100%;min-height:640px;border:0;display:block" loading="lazy"></iframe>'),
            },
            {
                "target": "FAQ section",
                "why": "<details> has no atomic equivalent in the released widget set.",
                "action": "Optional: replace the FAQ rows with Elementor Pro's Accordion widget "
                          "for collapse behaviour. Same six questions and answers either way.",
            },
        ],
        "global_variables": GLOBAL_VARIABLES,
        "global_classes": [{"label": k, "style": " ".join(v.split())} for k, v in GLOBAL_CLASSES.items()],
        "composition": {
            "parent_id": "document",
            "mode": "replace_children",
            "xml_structure": xml_structure,
            "element_config": cfg,
            "style": sty,
            "classes": cls,
        },
    }

    OUT_PATH.parent.mkdir(parents=True, exist_ok=True)
    OUT_PATH.write_text(json.dumps(payload, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")
    print(f"Wrote {OUT_PATH.relative_to(REPO_ROOT)}: {len(ids)} elements, "
          f"{len(GLOBAL_VARIABLES)} variables, {len(GLOBAL_CLASSES)} classes. "
          f"No em-dashes. All class refs resolved.")


if __name__ == "__main__":
    main()
