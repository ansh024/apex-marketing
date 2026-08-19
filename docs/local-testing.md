# Testing this repo locally

Two harnesses, in increasing order of fidelity. Both run without Docker, without a database
server, and without reaching wordpress.org, because the sandboxes this repo gets worked on in
usually block all three.

| | Needs | Covers |
|---|---|---|
| `scripts/test-location-cpt.php` | PHP only | Helper logic, WordPress stubbed |
| `scripts/setup-wp-sandbox.sh` + `scripts/test-location-integration.php` | PHP, git, network to GitHub | Real WordPress: registration, rewrites, REST, meta box, save handler |

## 1. Logic tests, no WordPress

```bash
php scripts/test-location-cpt.php
```

Stubs the dozen WordPress functions the helpers touch. Fast, and enough to catch the grammar and
ordering bugs that county names cause.

## 2. Integration tests, real WordPress

```bash
./scripts/setup-wp-sandbox.sh
php /tmp/apex-wp-sandbox/wp-cli.phar --allow-root \
    --path=/tmp/apex-wp-sandbox/wp \
    eval-file scripts/test-location-integration.php
```

The setup script builds a throwaway install at `/tmp/apex-wp-sandbox`:

- **WordPress core from the GitHub mirror** (`github.com/WordPress/WordPress`), because
  `wordpress.org` and `downloads.wordpress.org` are usually blocked, which also rules out
  `wp core download`.
- **SQLite instead of MySQL**, via WordPress's own `sqlite-database-integration`, so no database
  server is needed. The repo ships its driver as a symlink, so the script resolves it into a real
  copy the way the release build does.
- **The plugin symlinked from the repo**, so edits are picked up without reinstalling.
- Pretty permalinks, and the Austin location seeded from `scripts/seed-location-austin.php`.

Then serve it:

```bash
php -S 127.0.0.1:8080 -t /tmp/apex-wp-sandbox/wp /tmp/apex-wp-sandbox/wp/index.php
```

`http://localhost:8080/wp-admin` (admin / admin), and the location at
`http://localhost:8080/locations/austin-tx/`.

### Why `wp-env` is not the answer here

`.wp-env.json` is still in the repo and works on a normal machine. It needs to pull container
images, and this environment's egress policy returns 403 on Docker registry blobs, so it cannot
start. The sandbox script exists to not depend on that.

### One trap when writing more integration tests

wp-cli runs `eval-file` **inside a function**, so top-level variables in the test file are not
globals. A `global $x` inside a helper will not see them, and the failure is silent: the test
prints failures and still exits 0. `test-location-integration.php` shares state through
`$GLOBALS` explicitly for that reason.

## 3. Elementor

Elementor is not part of either harness, but it can be built in the same sandbox and this is worth
knowing because it is how §11 of `docs/elementor-authoring.md` was verified:

```bash
git clone --depth 1 --branch v4.0.8 https://github.com/elementor/elementor.git
cd elementor && npm ci && npm run build:packages && npx grunt build
# then copy ./build into wp-content/plugins/elementor
```

`npm ci` runs `composer install` as a post-step and **will fail** on `ext-bcmath` and on
`composer.elementor.com` being blocked. Neither is fatal: Elementor loads `vendor/autoload.php`
only `if ( file_exists(...) )`, so running the two build commands directly works.

What that gets you, and what it does not:

- **Works**: the experiments registry, the full atomic element inventory, `Props_Parser` and
  `Style_Parser`. That is enough to validate a template's element types and its settings/styles
  encoding before it ever touches the live site.
- **Does not work**: rendered markup. Atomic elements render through Twig, shipped prefixed as
  `ElementorDeps\Twig` by php-scoper from the blocked composer package, so `vendor_prefixed/` is
  empty and rendering returns nothing. Aliasing plain Twig into the prefixed namespace gets the
  fatal error to go away but still renders empty.

**Elementor Pro cannot be obtained this way at all** — it is licensed and not on GitHub. Anything
Pro-specific (the Theme Builder parts, dynamic tag binding, the Accordion fallback) has to be
verified on the real site or a staging copy of it.
