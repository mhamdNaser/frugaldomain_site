# Selling Shopify themes and apps

## On the server (once)

```bash
php artisan migrate --force      # market_items, market_purchases
```

The hourly `market:sync-releases` runs from the same cron line as the rest
(`php artisan schedule:run`). Payment methods are the ones under
Admin → Shopify → Payment Methods; every active method is offered.

## Add an item

Admin → Shopify → **Marketplace**:

1. Type (theme / app), slug, name, price (0 = free), status.
2. Summary, features (one per line), SEO title and description, in English and Arabic.
3. README: paste it or pick the `README.md` file (it fills the text box).
4. Preview link and its password (both are shown on the item page, with a copy button).
5. Logo (PNG, JPG, WebP or SVG, up to 2 MB).
6. The file, one of:
   - **Upload**: the zip (up to 200 MB).
   - **GitHub**: the private repo (`owner/name`) and a read-only token, then
     **Check for updates**. The server also checks every hour.

Buyers always download the newest version. They never see the repo or the token.

## Linking a private GitHub repo

### 1. A read-only token

GitHub → Settings → Developer settings → Fine-grained tokens → Generate:

- Repository access: **Only select repositories** → the theme's repo.
- Permissions → Repository → **Contents: Read-only** (nothing else).
- Expiration: up to a year; put a reminder to replace it.

Paste it in the item's "GitHub token" field. Leaving the field empty later keeps the saved token.

### 2. A release with a ready zip

The site downloads the **first `.zip` asset of the latest GitHub Release**. Shopify wants
the theme folders at the root of the zip, so let a GitHub Action build it on every tag.
Add `.github/workflows/release.yml` to the theme repo:

```yaml
name: Release theme zip
on:
  push:
    tags: ['v*']
permissions:
  contents: write
jobs:
  release:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Build the theme zip (folders at the root, as Shopify expects)
        run: |
          dirs=$(ls -d assets blocks config layout locales sections snippets templates 2>/dev/null)
          zip -r theme.zip $dirs -x '*.DS_Store'
      - uses: softprops/action-gh-release@v2
        with:
          files: theme.zip
          generate_release_notes: true
```

For a **Shopify app** repo, replace the zip step with
`git archive --format=zip -o app.zip HEAD` and `files: app.zip`.

### 3. Publish a version

```bash
git tag v1.0.0
git push origin v1.0.0
```

Within the hour (or at once with **Check for updates**) the item shows version `v1.0.0`,
its release notes, and buyers download the new zip.

### Prompt you can give an AI assistant in the theme repo

> Add a GitHub Actions workflow at `.github/workflows/release.yml` that runs when a tag
> matching `v*` is pushed. It must zip only the Shopify theme folders that exist
> (assets, blocks, config, layout, locales, sections, snippets, templates) with those
> folders at the root of `theme.zip`, then create a GitHub Release for the tag with
> `theme.zip` attached and auto-generated release notes (softprops/action-gh-release@v2,
> `permissions: contents: write`). Do not commit any secret.
