## What it does

A link to the app shared in a chat, a mail or a social network is shown as a card: a title, a line of description, a picture. The services drawing it read the page's head and run none of its scripts. symfony-seo adds the Open Graph tags they read to every page:

- `og:title`, `og:description`, `og:url`: what the page already says of itself — its `<title>`, its meta description, its canonical address;
- `og:image`: the app's picture, as a whole address;
- `og:site_name`, `og:type`, and `twitter:card` — the large card when there is a picture.

Nothing is to be called from a template. The tags come through `HeadMetaProviderInterface` (symfony-helpers), which the page's base template of symfony-loader prints: installing the bundle is enough.

## Configuring it

```yaml
wexample_symfony_seo:
  open_graph:
    image: '/images/og-image.png'   # under public/, or an address; null: no picture
    site_name: 'Sapiens'            # null: none
    enabled: true                   # false: no tag at all
```

The picture is 1200 × 630 pixels: the size every service crops to, the large card's.

## Adding tags from another bundle

Any service implementing `HeadMetaProviderInterface` is picked up, its tag following the interface: it receives what the page says of itself (`title`, `description`, `url`) and returns the attributes of each `<meta>` to add. A verification of ownership, a hint to a crawler, are added the same way, without the layouts knowing.
