## What it does

Search engines read what a page is in schema.org's vocabulary, as JSON-LD blocks in its head: who the site is about, what the page shows. symfony-seo prints two kinds of them:

- what the site declares of itself, the same on every page — the person or the company behind it, the site — taken from the configuration;
- what the page is about, when its template names an entity: described from the entity itself, in the visitor's language.

They come through `HeadStructuredDataProviderInterface` (symfony-helpers), which the page's base template of symfony-loader prints.

## The site

```yaml
wexample_symfony_seo:
  structured_data:
    - '@type': Person
      '@id': '/#person'
      name: 'Jane Doe'
      url: 'https://example.com'
    - '@type': WebSite
      name: 'Jane Doe'
      url: 'https://example.com'
```

An `@id` opening on a slash is completed with the address the page is served from — `https://example.com/#person` — so that what refers to it, on any host, finds the same node.

## The page's subject

An entity says what it is with `#[SchemaOrg]`, and which of its fields are which property with `#[SchemaOrgProperty]`:

```php
#[SchemaOrg('CreativeWork', extra: ['author' => ['@id' => '/#person']])]
class Project extends AbstractEntity
{
    #[Translatable]
    #[SchemaOrgProperty('name')]
    protected string $title;

    #[SchemaOrgProperty('image')]
    protected ?Media $cover = null;
}
```

Its page names it, once:

```twig
{%- set document_subject = project -%}
```

The entity is then described by `SchemaOrgNormalizer`, in the serializer's `schema_org` format, and completed with what the page knows of it: its `@id` (the canonical address and `#subject`), its `url`, its `inLanguage`.

- A field is read through the `EntityFieldReaderInterface` services: a `#[Translatable]` one in the visitor's language when symfony-translations is installed, as stored otherwise.
- What a field holds is described in turn: another `#[SchemaOrg]` entity by this same normalizer, a media by symfony-media's — an `ImageObject` at the address it is served from, a restricted one left out. An object nothing describes is left out rather than dumped.
- An empty field is left out.
- An object met again in the same description is referred to by its `@id`, or left out without one: a cycle stops there.
- `extra` adds what no field holds, its relative `@id` completed as the configuration's are: an author kept in configuration, until it is an entity of its own.

## Describing an object from another bundle

A normalizer supporting the `schema_org` format (`HeadStructuredDataProviderInterface::FORMAT_SCHEMA_ORG`) for its class describes it wherever an entity holds one. The api's entity normalizers leave that format alone.
