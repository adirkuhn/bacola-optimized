# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Bacola is a WordPress WooCommerce theme (v1.6.2) for grocery/organic food stores. It requires PHP 7.2+, WordPress 5.0+, and the following plugins:

**Required:** WooCommerce, Elementor, Bacola Core (proprietary), Envato Market  
**Optional:** Kirki (Customizer), Meta Box (custom fields), Contact Form 7, MailChimp for WP

The theme runs on a live WordPress installation — there is no standalone build system. Changes take effect when the theme files are served by WordPress.

## Architecture

### Entry Points

- `functions.php` — bootstraps everything: enqueues assets, registers menus/widgets, hooks into WordPress/WooCommerce, and `require_once`s all `/includes/` modules at the bottom
- `header.php` / `footer.php` — delegate to `includes/header/main_header.php` and `includes/footer/main_footer.php` via action hooks
- `index.php`, `single.php`, `archive.php`, `page.php`, `search.php` — standard WordPress template hierarchy

### Hook Architecture

Theme output is driven by WordPress action hooks, not direct function calls:

```
bacola_main_header → bacola_canvas_menu (priority 10)
                   → bacola_top_notification (priority 20)
                   → bacola_main_header_function (priority 30)
                        → header-type1.php OR header-type2.php (based on Customizer)

bacola_before_main_shop / bacola_after_main_shop
bacola_before_main_footer / bacola_after_main_footer
bacola_before_main_header / bacola_after_main_header
  → all fire Elementor template injection via bacola_get_elementor_template()
```

### `/includes/` Modules

| File | Purpose |
|------|---------|
| `woocommerce.php` | Product display helpers (image, badge, sale %, second image) |
| `woocommerce-filter.php` | Shop view (grid/list), body class, PJAX detection |
| `pjax/filter-functions.php` | AJAX shop filtering — enqueues pjax.js + AjaxFilter.js when `bacola_ajax_on_shop` Customizer option is enabled |
| `metaboxes.php` | Custom product/page meta fields via Meta Box plugin (`klb_` prefix) |
| `sanitize.php` | `bacola_sanitize_data()` — wraps `wp_kses()` with an extended allowed-tag list; use this for all HTML output |
| `header/main_header.php` | Wires canvas menu, top notification, and header type selection |
| `merlin/` | One-time setup wizard (demo import). Do not modify. |

### WooCommerce Templates

Overrides live in `/woocommerce/` (standard WooCommerce override convention). The theme overrides: `archive-product.php`, `content-product.php`, `content-single-product.php`, cart, checkout, myaccount, loop partials, and global wrappers.

### MultiVendorX

`/MultiVendorX/mvx-archive-page-vendor.php` — single vendor store page template override.

### Assets

- **CSS source:** `assets/scss/base.scss` imports all partials (`_header`, `_woocommerce`, `_modules`, etc.). Compiled output is `assets/css/base.css`.
- **JS:** `assets/js/bundle.js` contains the main BACOLA_APP object (jQuery). `assets/js/init.js` handles admin post-format meta box visibility. PJAX filtering JS lives in `includes/pjax/js/`.
- No `package.json` or build tool config exists — SCSS must be compiled manually or via editor plugin.

### Customizer (Theme Options)

All user-configurable options use `get_theme_mod('bacola_*')`. Key options:

| Option | Effect |
|--------|--------|
| `bacola_header_type` | `type1` or `type2` header layout |
| `bacola_ajax_on_shop` | Enable PJAX-based shop filtering |
| `bacola_product_badge_tab` | Use custom badge instead of sale percentage |
| `bacola_mapapi` | Google Maps API key |
| `bacola_top_header` / `bacola_header_sidebar` | Show/hide top bar and sidebar menu |

## Coding Rules

### Think Before Coding

- State assumptions explicitly before implementing. If multiple interpretations exist, present them — don't pick silently.
- If something is unclear, stop and ask.
- If a simpler approach exists, say so.

### Simplicity First

- Minimum code that solves the problem. Nothing speculative.
- No abstractions for single-use code.
- No flexibility or configurability that wasn't requested.
- No error handling for impossible scenarios.

### Surgical Changes

- Touch only what the request requires.
- Don't improve adjacent code, comments, or formatting.
- Match existing style even if you'd do it differently.
- Mention unrelated dead code — don't delete it.
- Remove imports/variables made unused by YOUR changes only.

### Goal-Driven Execution

Transform tasks into verifiable goals:
- "Fix bug" → write test that reproduces it, then make it pass
- "Add validation" → write tests for invalid inputs first, then implement

For multi-step tasks, state a brief plan with verify steps before starting.

### Clean Architecture & TDD

- Separate concerns: domain logic vs. WordPress/WooCommerce integration vs. presentation
- Write failing test first, then implementation
- One assert per test; tests must be fast, independent, and repeatable

### General Rules

- Files must not exceed 400 lines — refactor if exceeded
- Keep configurable data at high levels (Customizer options, not hardcoded values)
- Prefer polymorphism over if/else chains
- Use dependency injection
- Follow Law of Demeter — a class should know only its direct dependencies
- Replace magic numbers with named constants
- Prefer dedicated value objects over primitives
- Avoid negative conditionals
- No flag arguments — split into separate methods

### Sanitization

Always pass HTML output through `bacola_sanitize_data()` (`includes/sanitize.php`). Never use raw `echo` with user-controlled or DB-sourced HTML. Always use `esc_url()`, `esc_attr()`, `esc_html()` for attributes and plain text.

### WordPress Conventions

- All functions prefixed `bacola_` to avoid conflicts
- Guard new functions with `if ( ! function_exists( 'bacola_*' ) )`
- Use `get_template_directory_uri()` for asset URLs, `get_template_directory()` for file paths
- Hook into WordPress actions/filters rather than calling functions directly where possible

### Code Smells to Avoid

- **Rigidity** — small change causes cascade of changes
- **Fragility** — breaks in many places from one change
- **Needless Complexity / Repetition**
- **Opacity** — code hard to understand without comments

## Investigation-First Workflow

For every bug report or feature request:
1. Diagnose root cause
2. Present: what the bug is, why it happens, proposed fix, how the fix resolves it
3. Wait for approval before implementing
