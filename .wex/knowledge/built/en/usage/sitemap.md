## What it does

`/sitemap.xml` lists the pages a visitor can open, read off the routes rather than written by hand: those answering GET with nothing in their path but the language, in every language the path offers, each one pointing at the others through `hreflang` alternates and an `x-default`.

Left out: paths read by programs (`/_…`, `/api`), files (`/robots.txt`), pages behind a sign-in — `#[IsGranted]` on the controller or a role in the app's `access_control` —, routes carrying the option `sitemap: false`, what robots.txt keeps crawlers from, and the patterns the app excludes:

```yaml
wexample_symfony_seo:
  sitemap:
    exclude:
      - '^/[^/]+/admin/'
```

## Pages whose path takes a parameter

A route like `articles/{slug}` is not listed by itself: the routes say a slug is needed, not which ones exist. The app that knows them names them by implementing `SitemapProviderInterface` on any autoconfigured service — nothing else to register:

```php
use Wexample\SymfonySeo\Class\SitemapRoute;
use Wexample\SymfonySeo\Interface\SitemapProviderInterface;

class ArticleSitemapProvider implements SitemapProviderInterface
{
    public function __construct(private readonly ArticleRepository $articles)
    {
    }

    public function getSitemapRoutes(): iterable
    {
        foreach ($this->articles->findPublished() as $article) {
            yield new SitemapRoute('articles_show', ['slug' => $article->getSlug()]);
        }
    }
}
```

`SitemapRoute` takes the route name and its parameters, the language left out: the sitemap fills in each one the route offers, adds the alternates, and applies the same exclusions as for the other pages. Choosing which records have a page — published ones, not drafts — is the provider's call: the sitemap cannot tell a draft from a page that answers.

## More pages than one file holds

A sitemap file lists 50,000 urls at most. Past that, `/sitemap.xml` becomes a `<sitemapindex>` of `/sitemap-1.xml`, `/sitemap-2.xml`…, each holding the next 50,000, in the order the pages were listed. A file past the last, or any of them while the sitemap fits in one, is a 404. A lower limit — to keep each file light — is set by the app:

```yaml
wexample_symfony_seo:
  sitemap:
    max_urls: 10000
```

Under `symfony-translations`' locale routing, these files keep their url like `/sitemap.xml`: no language in front.
