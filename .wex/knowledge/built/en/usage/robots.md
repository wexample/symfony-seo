## Delete `public/robots.txt` first

A static `public/robots.txt` wins over the route: the web server serves the file before the request ever reaches PHP, and the rules declared by the bundles are silently never sent. Installing this bundle means deleting that file. No application test catches it — only a request on the deployed site shows which one answers:

```bash
curl -i https://example.com/robots.txt
```

## What it does

`/robots.txt` is served by Symfony, route `seo_robots`, as `text/plain; charset=UTF-8`. Its body is the sum of what the installed bundles declare, plus what the app adds in configuration. A bundle that exposes paths crawlers should not follow declares them once, and every app installing it serves them.

The path breaks the rule that a path read by a program opens on an underscore: the standard sets it, as it does for `/api`.

## Installing

```yaml
# config/routes/wexample_symfony_seo.yaml
wexample_symfony_seo:
  resource: '@WexampleSymfonySeoBundle/Resources/config/routes.yaml'
```

## Declaring rules from a bundle or an app

Implement `RobotsProviderInterface` on any autoconfigured service — nothing else to register:

```php
use Wexample\SymfonySeo\Class\RobotsRule;
use Wexample\SymfonySeo\Interface\RobotsProviderInterface;

class TunnelRobotsProvider implements RobotsProviderInterface
{
    public function getRobotsRules(): array
    {
        return [
            new RobotsRule(disallow: ['/tunnel/']),
            new RobotsRule(disallow: ['/drafts/'], userAgent: 'Googlebot'),
        ];
    }
}
```

`RobotsRule` takes `disallow`, `allow` and `userAgent` (default `*`). Rules on the same user-agent, from any number of providers, are merged into one block, in the order the user-agents were first declared, matched case-insensitively. Inside a block `Allow` lines come before `Disallow` lines, which is what Google and Bing read on overlapping paths. A rule with neither list opens no block.

## Configuration

```yaml
wexample_symfony_seo:
  robots:
    enabled: true          # false: the route answers 404
    disallow_all: false    # true: the whole body becomes `User-agent: *` / `Disallow: /`
    sitemaps:
      - 'https://example.com/sitemap.xml'
    extra: |
      Crawl-delay: 10
```

- `disallow_all` is meant for staging environments, which get indexed because nobody thought of them. It overrides providers, `extra` and `sitemaps` alike; set it in `config/packages/<env>/` rather than deploying a different file per environment.
- `extra` is written as is after the user-agent blocks.
- `sitemaps` are global lines, tied to no user-agent, and close the file.

## What it does not do

`robots.txt` only says "do not crawl", never "do not index", and only polite crawlers read it. A URL disallowed here may still show up in results if it is linked from elsewhere. Keeping a page out of the index takes an `X-Robots-Tag: noindex` header on its response.
