## What it does

Browsers ask for `/favicon.ico` on every site, whether the page declares an icon or not. Without a file, each visit logs a 404. The route `seo_favicon` answers `/favicon.ico` with a neutral icon shipped in the bundle (`src/Resources/favicon/favicon.ico`, a dark disc at 16, 32 and 48 px), served as `image/x-icon` and cached publicly for a day.

It comes with the same routes import as `/robots.txt`:

```yaml
wexample_symfony_seo:
  resource: '@WexampleSymfonySeoBundle/Resources/config/routes.yaml'
```

## Replacing it

The route is a fallback. A `public/favicon.ico` in the app is served by the web server before the request reaches PHP, so an app replaces the default icon just by adding the file. The same precedence that makes a static `robots.txt` a trap works in your favour here.

To serve an icon kept elsewhere, or to turn the route off:

```yaml
wexample_symfony_seo:
  favicon:
    enabled: true                                        # false: the route answers 404
    path: '%kernel.project_dir%/assets/brand/favicon.ico' # null: the bundled icon
```

## When the route is never reached

Some web server configurations answer static extensions themselves and never fall back to PHP, for example an nginx `location ~* \.(ico|css|js)$ { ... }` block with no `try_files ... /index.php`. The 404 then comes from the server, not from Symfony. Check on the deployed site:

```bash
curl -I https://example.com/favicon.ico
```
