# CMB2 (vendored)

This directory is a **vendored, unmodified copy of [CMB2](https://cmb2.io)**,
the admin metabox / options-page framework THP plugins build their settings
screens on. The version currently bundled is **v2.13.2**.

It is committed here on purpose so there is a single shared copy to upgrade and
security-patch, rather than one per consuming plugin. It is loaded through CMB2's
own official entry point (`init.php`), required once from `thp-core.php`; see the
comment block there for the loading rationale.

**Never hand-edit any file inside this directory.** Every file here is upstream
CMB2 verbatim. Local edits would be silently destroyed by the next upgrade (which
replaces the whole directory) and would break the version-coexistence mechanism
described below. If you think CMB2 itself needs a change, take it upstream.

## How to upgrade

1. Download the new official release — from
   <https://wordpress.org/plugins/cmb2/> or
   <https://github.com/CMB2/CMB2/releases>. Use a tagged release, not a `main`
   snapshot.
2. **Delete this entire `CMB2/` directory** and replace it with the new
   release's contents *wholesale*. Do not merge file-by-file — a clean swap is
   what keeps this copy provably identical to upstream. This file
   (`VENDORED.md`) is the one thing here that is **not** upstream, so save it
   first and restore it into the fresh directory afterwards.
3. Keep the version note at the top of this restored file (`v2.13.2` above) in
   sync with what you just dropped in.
4. Commit it as a **single, dedicated commit**, e.g.
   `chore: bump bundled CMB2 to vX.Y.Z`. **Never mix a version bump with
   unrelated changes** — it makes the bump impossible to audit against upstream
   and impossible to revert cleanly.

## After upgrading: re-verify the deploy layout

A new release can add files that did not exist in the version this was last
verified against (e.g. new field types shipping their own JS/CSS assets). Two
things must still hold after a bump:

1. **The `init.php` path still resolves.** Deployment rsyncs the repo root into
   `wp-content/mu-plugins/`, so this file lands at
   `mu-plugins/thp-core/lib/CMB2/init.php` and `thp-core.php` requires it from
   `THP_CORE_DIR . '/lib/CMB2/init.php'`. Re-run the deploy-layout check (rsync
   dry-run with the `DEPLOY_EXCLUDE` list from `.deployrc` replayed) and confirm
   that path is present on the deployed side.
2. **No new file is caught by an exclude pattern.** Walk the new release against
   `DEPLOY_EXCLUDE` (and the matching `deploy_exclude` list in the CI workflows)
   and confirm nothing CMB2 needs at runtime — a newly added asset, language
   file, or include — matches an exclude glob (e.g. `*.log`) and gets dropped
   from the deploy. If it does, the field type or feature that depends on it will
   silently break on the server only.

## Why multiple CMB2 versions can safely coexist (do not "deduplicate" this)

Other plugins (now or later) may bundle their own copy of CMB2. That is fine and
by design — **do not try to remove or centralize the other copies to save
space.** CMB2 is explicitly built to be bundled by several plugins/themes at once
without a redeclare fatal:

- `init.php` wraps everything in
  `if ( ! class_exists( 'CMB2_Bootstrap_2132', false ) )`. **The bootstrap class
  name embeds the version** (`CMB2_Bootstrap_2132` for 2.13.2), so two different
  versions declare two differently-named classes and never collide.
- Each copy hooks its loader on the `init` action at a `PRIORITY` constant that
  **decrements with every release** — so the newest bundled version runs first.
- That loader (`include_cmb()`) bails immediately on
  `class_exists( 'CMB2', false )`. Once the newest-loaded copy has defined the
  real `CMB2` class, **every older copy stands down.**

Net effect: **newest-loaded-first wins; older bailouts via
`class_exists( 'CMB2', false )`.** Because the version is carried in the
bootstrap class name and the winner is chosen at runtime by priority, having more
than one copy across plugins is safe. Deleting a "duplicate" copy elsewhere would
only remove that plugin's fallback for when this one is deactivated — it fixes
nothing.
