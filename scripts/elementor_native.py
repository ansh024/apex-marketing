"""
Compile the location-page tree into a native Elementor template.

Why this exists: `location-page.json` is a `build-composition` payload, and that
API ships only in Elementor's unreleased `modules/mcp`. Nothing on a real site
can consume it. This emits the document format Elementor actually imports.

The awkward part is styles. The atomic style schema is **shorthand-keyed and
logical**: there is no `padding-top`, no `row-gap`, no `flex-grow`, no
`border-top-width`, no `background-color`. There is `padding` taking
`{block-start, inline-end, block-end, inline-start}`, `gap` taking
`{row, column}`, `flex` taking `{flexGrow, flexShrink, flexBasis}`, and so on.
That is the exact inverse of the longhand-only rule that applies to the
composition CSS path, so this module's main job is aggregating longhands back
into structured shorthands.

Nothing here is trusted on inference. `scripts/validate-elementor-template.php`
runs the output through Elementor's own `Props_Parser` and `Style_Parser`; a
template that has not passed that is not built.
"""

import hashlib
import re

# Breakpoints Elementor knows. The composition CSS uses @media(--name) blocks
# with these names; each becomes its own style variant.
KNOWN_BREAKPOINTS = {"mobile", "tablet", "laptop", "widescreen", "desktop"}

# Longhand -> (shorthand key, field in the shorthand object).
# Field names are logical, matching the schema dumped from a live Elementor.
DIMENSION_FIELDS = {
    "top": "block-start",
    "right": "inline-end",
    "bottom": "block-end",
    "left": "inline-start",
}

# The schema has no top/right/bottom/left; insets are logical.
INSET_FIELDS = {
    "top": "inset-block-start",
    "bottom": "inset-block-end",
    "left": "inset-inline-start",
    "right": "inset-inline-end",
}

# text-align in the schema is logical. left/right are rejected outright.
TEXT_ALIGN = {"left": "start", "right": "end"}

# The schema's align-items enum has no `baseline`. Used on the stat rows and
# clause rows, where a large number sits beside a small label. `flex-end` puts
# their bottoms on one line, which is what baseline was buying at these sizes.
ALIGN_ITEMS = {"baseline": "flex-end"}

CORNER_FIELDS = {
    "top-left": "start-start",
    "top-right": "start-end",
    "bottom-right": "end-end",
    "bottom-left": "end-start",
}

# Properties the schema has no home for, and what we do about them.
# Dropping is only acceptable where it is a deliberate, recorded decision.
DROPPED = {
    # Five uses, all tabular-nums on the hero survey plate. Four of the five
    # values are not numeric. See docs/converter-plan.md.
    "font-variant-numeric",

    # The three below are all one class: `grid-field`, the 5%-opacity
    # construction-grid texture behind each section.
    #
    # It is a repeating two-axis linear-gradient. The schema's gradient overlay
    # has type/angle/stops but no background-size, and without that a gradient
    # fills its box once and draws a single line rather than a grid. So the
    # pattern genuinely cannot be expressed as a gradient.
    #
    # It CAN be restored as a background-image overlay: that shape does support
    # `repeat: repeat` and a scaled size, so one 220x220 tile reproduces it
    # exactly. That needs an uploaded attachment, so it is a manual step in the
    # template rather than something this generator can emit. Recorded in
    # docs/converter-plan.md and in the template's manual_steps.
    "background-image",
    "background-size",
    # Belongs to the same overlay. It is decorative and sits below content
    # (content carries position:relative + z-index:2), so it never covers an
    # interactive element and dropping it changes no behaviour.
    "pointer-events",
}

# Schema keys that take a plain keyword string.
STRING_PROPS = {
    "align-content", "align-items", "align-self", "appearance", "aspect-ratio",
    "border-style", "clip-path", "content", "cursor", "direction", "display",
    "flex-direction", "flex-wrap", "font-style", "font-weight", "justify-content",
    "justify-items", "mix-blend-mode", "object-fit", "outline-style", "overflow",
    "position", "text-align", "text-decoration", "text-transform",
}

# Schema keys that take a bare number.
NUMBER_PROPS = {"column-count", "order", "z-index"}

# Schema keys that take a size (or a global size variable).
SIZE_PROPS = {
    "column-gap", "font-size", "height", "letter-spacing", "line-height",
    "max-height", "max-width", "min-height", "min-width", "opacity",
    "outline-offset", "outline-width", "scroll-margin-top", "width",
    "word-spacing", "inset-block-start", "inset-block-end",
    "inset-inline-start", "inset-inline-end",
}

# object-position is its own shape: a union of a keyword string and an {x, y}
# position. Two-value forms like `center 18%` only fit the latter.
POSITION_PROPS = {"object-position"}

# Schema keys that take a colour (or a global colour variable).
COLOR_PROPS = {"color", "border-color", "outline-color", "stroke"}


# Elementor renamed two prop-type keys between 4.0 and 4.2, and the parser
# rejects the old names outright. These target 4.2+, which is what the Apex
# staging site runs; on 4.0.x they are 'border-width' and 'border-radius' and
# the sides of border-width are all required. Validate against the version you
# are actually importing into, because this is invisible until you do: a
# rejected style renders unstyled rather than raising anything.
BORDER_WIDTH_TYPE = "border-width-v2"
BORDER_RADIUS_TYPE = "border-radius-v2"


class UnmappableCSS(Exception):
    """A declaration the style schema has no home for. Always fatal."""


# ---------------------------------------------------------------------------
# Stable ids
# ---------------------------------------------------------------------------

def stable_id(*parts):
    """
    Deterministic 8-hex id from the human-readable configuration-id.

    Regenerating must produce identical ids, otherwise every rebuild churns the
    whole document and re-importing creates a copy instead of updating.
    """
    seed = "|".join(str(p) for p in parts)
    return hashlib.sha1(seed.encode("utf-8")).hexdigest()[:8]


def local_style_id(element_id, cid):
    return "e-{}-{}".format(element_id, hashlib.sha1(cid.encode("utf-8")).hexdigest()[:7])


def variable_id(label):
    return "e-gv-{}".format(hashlib.sha1(("var:" + label).encode("utf-8")).hexdigest()[:7])


# ---------------------------------------------------------------------------
# Value parsing
# ---------------------------------------------------------------------------

LENGTH_RE = re.compile(r"^(-?\d*\.?\d+)(px|rem|em|%|vh|vw|vmin|vmax|ch|fr|s|ms|deg)$")
NUMBER_RE = re.compile(r"^-?\d*\.?\d+$")
VAR_RE = re.compile(r"^var\(\s*--([A-Za-z0-9_-]+)\s*\)$")
HEX_RE = re.compile(r"^#([0-9A-Fa-f]{3,8})$")


def size_value(raw, variables):
    """A `size` prop, or a global size variable reference."""
    raw = raw.strip()

    m = VAR_RE.match(raw)
    if m:
        label = m.group(1)
        var = variables.get(label)
        if var is None:
            raise UnmappableCSS("var(--{}) is not a declared global variable".format(label))
        if var["type"] != "size":
            raise UnmappableCSS("var(--{}) is a {} variable used where a size is required"
                                .format(label, var["type"]))
        return {"$$type": "global-size-variable", "value": variable_id(label)}

    m = LENGTH_RE.match(raw)
    if m:
        return {"$$type": "size", "value": {"size": num(m.group(1)), "unit": m.group(2)}}

    if NUMBER_RE.match(raw):
        # An empty unit is rejected by the schema, so a bare number only works
        # for zero, where the unit is irrelevant. Everything else that is legal
        # unitless in CSS (line-height, opacity) is given a real unit by the
        # caller before it gets here.
        if float(raw) == 0:
            return {"$$type": "size", "value": {"size": 0, "unit": "px"}}
        raise UnmappableCSS(
            "unitless size {!r} has no valid unit; give it one explicitly".format(raw))

    if raw in ("auto", "none"):
        return {"$$type": "string", "value": raw}

    raise UnmappableCSS("cannot express {!r} as a size".format(raw))


def num(raw):
    """
    Integral values must serialise as ints, not floats.

    Elementor's Size prop rejects a float where an int is expected: emitting
    1.0 instead of 1 makes `border-width` invalid, and an invalid style renders
    unstyled without complaining. Verified against Style_Parser.
    """
    value = float(raw)
    return int(value) if value.is_integer() else value


# The object form of object-position takes a size on each axis; keywords are
# rejected there, and the string form only accepts the nine "<v> <h>" pairs.
# So a mixed value like `center 18%` has to resolve its keyword to a percentage.
POSITION_KEYWORDS = {"left": 0, "top": 0, "center": 50, "right": 100, "bottom": 100}


def position_component(raw, variables):
    """One axis of an object-position, always as a size."""
    if raw in POSITION_KEYWORDS:
        return {"$$type": "size", "value": {"size": POSITION_KEYWORDS[raw], "unit": "%"}}
    return size_value(raw, variables)


def color_value(raw, variables):
    """A `color` prop, or a global colour variable reference."""
    raw = raw.strip()

    m = VAR_RE.match(raw)
    if m:
        label = m.group(1)
        var = variables.get(label)
        if var is None:
            raise UnmappableCSS("var(--{}) is not a declared global variable".format(label))
        if var["type"] != "color":
            raise UnmappableCSS("var(--{}) is a {} variable used where a colour is required"
                                .format(label, var["type"]))
        return {"$$type": "global-color-variable", "value": variable_id(label)}

    if HEX_RE.match(raw) or raw.startswith(("rgb(", "rgba(", "hsl(", "hsla(")):
        return {"$$type": "color", "value": raw}

    if raw in ("transparent", "currentColor", "inherit"):
        return {"$$type": "color", "value": raw}

    raise UnmappableCSS("cannot express {!r} as a colour".format(raw))


# ---------------------------------------------------------------------------
# CSS text -> declarations, split by breakpoint
# ---------------------------------------------------------------------------

MEDIA_RE = re.compile(r"@media\(--([a-z]+)\)\s*\{([^{}]*)\}")
STATE_RE = re.compile(r"&:([a-z-]+)\s*\{([^{}]*)\}")

# States the style schema's variant meta accepts.
KNOWN_STATES = {"hover", "focus", "active", "visited"}


def split_variants(css):
    """
    -> {(breakpoint, state): [(prop, value), ...]}

    A style string can carry `@media(--laptop) { … }` blocks and `&:hover { … }`
    blocks. Each becomes its own variant: Elementor keys variants by
    `meta: {breakpoint, state}`, with desktop and state=None as the base.
    """
    buckets = {}
    base = css

    for m in STATE_RE.finditer(css):
        state = m.group(1)
        if state not in KNOWN_STATES:
            raise UnmappableCSS("&:{} is not a state the schema accepts".format(state))
        buckets.setdefault(("desktop", state), []).extend(parse_declarations(m.group(2)))
        base = base.replace(m.group(0), "")

    for m in MEDIA_RE.finditer(base):
        name = m.group(1)
        if name not in KNOWN_BREAKPOINTS:
            raise UnmappableCSS(
                "@media(--{}) is not an Elementor breakpoint name".format(name))
        buckets.setdefault((name, None), []).extend(parse_declarations(m.group(2)))
        base = base.replace(m.group(0), "")

    decls = parse_declarations(base)
    if decls:
        buckets.setdefault(("desktop", None), []).extend(decls)

    return buckets


def split_tone_dependent(css):
    """
    Split a style string into (tone-independent, tone-dependent) halves,
    preserving `@media(--x) { … }` blocks in both.

    The service cards theme themselves through --pc-* custom properties read by
    shared global classes. The style schema has no custom properties and a
    shared class cannot carry a per-card colour, so the tone-dependent
    declarations have to be lifted out and re-emitted per element.
    """
    indep_blocks, dep_blocks = [], []
    base = css

    # Nested blocks are split in place so a &:hover or @media block that mixes
    # toned and untoned declarations lands correctly on both sides.
    for regex, wrapper in (
        (STATE_RE, lambda name, body: "&:{} {{ {} }}".format(name, body)),
        (MEDIA_RE, lambda name, body: "@media(--{}) {{ {} }}".format(name, body)),
    ):
        for m in regex.finditer(base):
            i, d = _split_decls_by_tone(m.group(2))
            if i:
                indep_blocks.append(wrapper(m.group(1), i))
            if d:
                dep_blocks.append(wrapper(m.group(1), d))
        base = regex.sub("", base)

    indep, dep = _split_decls_by_tone(base)

    return (
        " ".join(([indep] if indep else []) + indep_blocks),
        " ".join(([dep] if dep else []) + dep_blocks),
    )


def _split_decls_by_tone(text):
    indep, dep = [], []
    for chunk in text.split(";"):
        chunk = chunk.strip()
        if not chunk:
            continue
        (dep if "var(--pc-" in chunk or chunk.startswith("--pc-") else indep).append(chunk + ";")
    return " ".join(indep), " ".join(dep)


def parse_declarations(text):
    out = []
    for chunk in text.split(";"):
        chunk = chunk.strip()
        if not chunk:
            continue
        if ":" not in chunk:
            raise UnmappableCSS("cannot parse declaration {!r}".format(chunk))
        prop, value = chunk.split(":", 1)
        out.append((prop.strip(), value.strip()))
    return out


# ---------------------------------------------------------------------------
# Declarations -> typed props
# ---------------------------------------------------------------------------

def compile_declarations(decls, variables, where):
    """
    Aggregate longhands into the schema's shorthand objects and type every value.

    Raises UnmappableCSS, with `where` for context, on anything the schema
    cannot hold. Silence is never an option: a dropped declaration renders as a
    subtly wrong page that nobody traces back to here.
    """
    props = {}
    # Accumulators for shorthands built from several longhands.
    dimensions = {"padding": {}, "margin": {}}
    border_width = {}
    border_radius = {}
    flex = {}
    gap = {}
    errors = []

    for prop, raw in decls:
        try:
            if prop in DROPPED:
                continue

            # Custom properties have no schema equivalent. The generator resolves
            # the --pc-* indirection before it reaches here, so any survivor is a bug.
            if prop.startswith("--"):
                raise UnmappableCSS(
                    "custom property {} has no equivalent in the style schema; "
                    "resolve it to a concrete value in the generator".format(prop))

            # Physical insets have logical schema keys.
            if prop in INSET_FIELDS:
                props[INSET_FIELDS[prop]] = size_value(raw, variables)
                continue

            # padding-* / margin-*
            m = re.match(r"^(padding|margin)-(top|right|bottom|left)$", prop)
            if m:
                dimensions[m.group(1)][DIMENSION_FIELDS[m.group(2)]] = size_value(raw, variables)
                continue
            if prop in ("padding", "margin"):
                props[prop] = size_value(raw, variables)
                continue

            # border-<side>-width / -color / -style
            m = re.match(r"^border-(top|right|bottom|left)-(width|color|style)$", prop)
            if m:
                side, kind = m.group(1), m.group(2)
                if kind == "width":
                    border_width[DIMENSION_FIELDS[side]] = size_value(raw, variables)
                elif kind == "color":
                    # The schema has a single border-color. Verified against the
                    # payload: no element uses conflicting colours across sides.
                    new = color_value(raw, variables)
                    if "border-color" in props and props["border-color"] != new:
                        raise UnmappableCSS(
                            "different border colours per side; the schema has one border-color")
                    props["border-color"] = new
                else:
                    if "border-style" in props and props["border-style"]["value"] != raw:
                        raise UnmappableCSS(
                            "different border styles per side; the schema has one border-style")
                    props["border-style"] = {"$$type": "string", "value": raw}
                continue

            # border-<corner>-radius
            m = re.match(r"^border-(top-left|top-right|bottom-right|bottom-left)-radius$", prop)
            if m:
                border_radius[CORNER_FIELDS[m.group(1)]] = size_value(raw, variables)
                continue
            if prop == "border-radius":
                props["border-radius"] = size_value(raw, variables)
                continue

            # flex-grow / flex-shrink / flex-basis
            if prop == "flex-grow":
                flex["flexGrow"] = {"$$type": "number", "value": num(raw)}
                continue
            if prop == "flex-shrink":
                flex["flexShrink"] = {"$$type": "number", "value": num(raw)}
                continue
            if prop == "flex-basis":
                flex["flexBasis"] = size_value(raw, variables)
                continue

            # row-gap / column-gap. There is no row-gap key; both fold into gap.
            if prop == "row-gap":
                gap["row"] = size_value(raw, variables)
                continue
            if prop == "column-gap":
                gap["column"] = size_value(raw, variables)
                continue
            if prop == "gap":
                gap["row"] = size_value(raw, variables)
                gap["column"] = size_value(raw, variables)
                continue

            # background-color -> background
            if prop == "background-color":
                props["background"] = {
                    "$$type": "background",
                    "value": {"color": color_value(raw, variables)},
                }
                continue

            if prop in POSITION_PROPS:
                parts = raw.split()
                if len(parts) == 1:
                    # A lone keyword is not in the enum; express it on both axes.
                    parts = [parts[0], "center"] if parts[0] in ("left", "right") \
                        else ["center", parts[0]]
                if len(parts) == 2:
                    props[prop] = {
                        "$$type": "object-position",
                        "value": {
                            "x": position_component(parts[0], variables),
                            "y": position_component(parts[1], variables),
                        },
                    }
                else:
                    raise UnmappableCSS("object-position takes one or two values")
                continue

            # The schema rejects a size with an empty unit, so bare ratios need
            # a real one. `em` is exact for line-height; opacity becomes a
            # percentage. Both verified against Style_Parser.
            if prop == "line-height" and NUMBER_RE.match(raw):
                props[prop] = {"$$type": "size", "value": {"size": num(raw), "unit": "em"}}
                continue
            if prop == "opacity" and NUMBER_RE.match(raw):
                props[prop] = {"$$type": "size", "value": {"size": num(float(raw) * 100), "unit": "%"}}
                continue

            # text-align is logical: left/right are rejected, start/end are not.
            if prop == "text-align":
                props[prop] = {"$$type": "string",
                               "value": TEXT_ALIGN.get(raw, raw)}
                continue
            if prop == "align-items":
                props[prop] = {"$$type": "string",
                               "value": ALIGN_ITEMS.get(raw, raw)}
                continue

            if prop in COLOR_PROPS:
                props[prop] = color_value(raw, variables)
                continue
            if prop in SIZE_PROPS:
                props[prop] = size_value(raw, variables)
                continue
            if prop in NUMBER_PROPS:
                if not NUMBER_RE.match(raw):
                    raise UnmappableCSS("{} needs a bare number, got {!r}".format(prop, raw))
                props[prop] = {"$$type": "number", "value": num(raw)}
                continue
            if prop in STRING_PROPS:
                props[prop] = {"$$type": "string", "value": raw}
                continue

            raise UnmappableCSS("{} is not in the style schema".format(prop))

        except UnmappableCSS as exc:
            # Collect rather than raise: one bad declaration must not hide the
            # rest of the block, or fixing them turns into a one-at-a-time crawl.
            errors.append("{}: {} (in `{}: {}`)".format(where, exc, prop, raw))

    if errors:
        raise UnmappableCSS("\n  ".join(errors))

    for shorthand, fields in dimensions.items():
        if fields:
            props[shorthand] = {"$$type": "dimensions", "value": fields}
    if border_width:
        # Unlike padding, border-width rejects a partial object: all four sides
        # must be present. Sides the CSS did not set are explicitly zero.
        zero = {"$$type": "size", "value": {"size": 0, "unit": "px"}}
        for field in DIMENSION_FIELDS.values():
            border_width.setdefault(field, zero)
        props["border-width"] = {"$$type": BORDER_WIDTH_TYPE, "value": border_width}
    if border_radius:
        props["border-radius"] = {"$$type": BORDER_RADIUS_TYPE, "value": border_radius}
    if flex:
        props["flex"] = {"$$type": "flex", "value": flex}
    if gap:
        if set(gap) == {"row", "column"} and gap["row"] == gap["column"]:
            props["gap"] = gap["row"]
        else:
            props["gap"] = {"$$type": "layout-direction", "value": gap}

    return props


def compile_style(css, style_id, label, variables, where):
    """A style string -> one style block with a variant per breakpoint."""
    variants = []
    buckets = split_variants(css)

    # Base first so it reads as the default; the rest in a stable order.
    def rank(key):
        bp, state = key
        return (bp != "desktop", state is not None, bp, state or "")

    for key in sorted(buckets, key=rank):
        bp, state = key
        label_bits = "@{}{}".format(bp, ":" + state if state else "")
        props = compile_declarations(buckets[key], variables, "{} {}".format(where, label_bits))
        if not props:
            continue
        variants.append({
            "meta": {"breakpoint": bp, "state": state},
            "props": props,
        })

    if not variants:
        return None

    return {"id": style_id, "label": label, "type": "class", "variants": variants}
