#!/usr/bin/env python3
"""Scope a stylesheet's selectors under a body class.

Elementor's kit ships rules like `.elementor-kit-12 p` (specificity 0,1,1),
which outrank the bare-element and single-class rules these standalone
templates rely on. Prefixing every selector with the template's own body class
puts them back in front without touching a single declaration.

Leaves alone: :root, html, @keyframes bodies, and anything already scoped.
Verifies that the set of declaration blocks is byte-identical afterwards, so a
run can only ever change selectors.

  usage: scope-css.py <file.css> <.scope-class> [--check]
"""
import re, sys, io

def strip_lead(prelude):
    """Split a prelude into (leading comments/whitespace, the rest).

    An at-rule preceded by a comment does not start with '@', so classifying on
    the raw prelude silently demotes `/* … */ @media {...}` to a plain rule and
    leaves its whole body unscoped.
    """
    m = re.match(r'\s*(?:/\*.*?\*/\s*)*', prelude, re.S)
    lead = m.group(0) if m else ''
    return lead, prelude[len(lead):]


def split_top_level(css):
    """Yield (kind, prelude, body) for each top-level construct."""
    out, i, n = [], 0, len(css)
    while i < n:
        brace = css.find('{', i)
        if brace == -1:
            out.append(('raw', css[i:], None)); break
        prelude = css[i:brace]
        # find matching close brace
        depth, j = 0, brace
        while j < n:
            if css[j] == '{': depth += 1
            elif css[j] == '}':
                depth -= 1
                if depth == 0: break
            j += 1
        body = css[brace+1:j]
        out.append(('at' if strip_lead(prelude)[1].startswith('@') else 'rule', prelude, body))
        i = j + 1
    return out

def scope_selector_list(sellist, scope):
    scopes = [s for s in scope.split(',') if s]
    parts = []
    for sel in sellist.split(','):
        raw = sel.strip()
        if not raw:
            continue
        if raw.startswith((':root', 'html', '@')) or raw.startswith(tuple(scopes)):
            parts.append(raw)
        elif raw.startswith('body'):
            parts.extend('body' + sc + raw[4:] for sc in scopes)
        elif raw.startswith('*'):
            # `*` matches <html> and the scope element itself, neither of which
            # is a descendant of `.scope`. Dropping them silently returns <html>
            # to content-box and shifts the whole layout, so emit all three
            # forms. These stylesheets only load on their own templates, so the
            # bare `html` selector cannot leak anywhere else.
            rest = raw[1:]
            parts.append('html' + rest)
            for sc in scopes:
                parts.append(sc + rest)
                parts.append('%s *%s' % (sc, rest))
        else:
            parts.extend('%s %s' % (sc, raw) for sc in scopes)
    return ','.join(parts)

def transform(css, scope):
    out = []
    for kind, prelude, body in split_top_level(css):
        if body is None:
            out.append(prelude); continue
        lead, head = strip_lead(prelude)
        if kind == 'at':
            # keyframe steps ("from"/"to"/"50%") are not selectors - never scope them
            # keyframe steps ("from"/"to"/"50%") are not selectors - never scope them
            if head.startswith(('@keyframes', '@font-face')):
                out.append(prelude + '{' + body + '}'); continue
            if head.startswith(('@media', '@supports')):
                out.append(prelude + '{' + transform(body, scope) + '}'); continue
            out.append(prelude + '{' + body + '}'); continue
        out.append(lead + scope_selector_list(head, scope) + '{' + body + '}')
    return ''.join(out)

def decls(css):
    return sorted(re.findall(r'\{([^{}]*)\}', css))

def main():
    path, scope = sys.argv[1], sys.argv[2]
    src = io.open(path, encoding='utf-8').read()
    res = transform(src, scope)
    if decls(src) != decls(res):
        sys.exit('ABORT: declaration blocks changed - refusing to write %s' % path)
    if src.count('{') != res.count('{') or src.count('}') != res.count('}'):
        sys.exit('ABORT: brace count changed - refusing to write %s' % path)
    if '--check' in sys.argv:
        print('ok (dry run) %s: %d -> %d bytes' % (path, len(src), len(res))); return
    io.open(path, 'w', encoding='utf-8').write(res)
    print('scoped %s under %s: %d -> %d bytes' % (path, scope, len(src), len(res)))

main()
