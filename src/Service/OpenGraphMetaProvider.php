<?php

namespace Wexample\SymfonySeo\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Wexample\SymfonyHelpers\Interface\HeadMetaProviderInterface;
use Wexample\SymfonySeo\DependencyInjection\WexampleSymfonySeoExtension;

/**
 * The card a link to the app shows once shared: what the page already says of
 * itself — its title, its description, its address —, and the app's picture.
 * Read by those who never run the page's scripts, so it is all in the head.
 */
class OpenGraphMetaProvider implements HeadMetaProviderInterface
{
    public function __construct(
        private readonly RequestStack $requestStack,
        #[Autowire(param: WexampleSymfonySeoExtension::PARAMETER_OPEN_GRAPH_ENABLED)]
        private readonly bool $enabled,
        #[Autowire(param: WexampleSymfonySeoExtension::PARAMETER_OPEN_GRAPH_IMAGE)]
        private readonly ?string $image,
        #[Autowire(param: WexampleSymfonySeoExtension::PARAMETER_OPEN_GRAPH_SITE_NAME)]
        private readonly ?string $siteName,
    ) {
    }

    public function getHeadMeta(array $document): array
    {
        if (! $this->enabled) {
            return [];
        }

        $image = $this->absolute($this->image);
        $meta = [
            ['property' => 'og:type', 'content' => 'website'],
            ['property' => 'og:title', 'content' => $document['title']],
            ['property' => 'og:description', 'content' => (string) $document['description']],
            ['property' => 'og:url', 'content' => (string) $document['url']],
            ['property' => 'og:site_name', 'content' => (string) $this->siteName],
            ['property' => 'og:image', 'content' => (string) $image],
            // The large card where there is a picture to show in it.
            ['name' => 'twitter:card', 'content' => $image ? 'summary_large_image' : 'summary'],
        ];

        // What is not known is left out rather than said empty.
        return array_values(array_filter($meta, static fn (array $tag): bool => '' !== $tag['content']));
    }

    /**
     * A crawler fetches the picture on its own: its address must be whole.
     */
    private function absolute(?string $path): ?string
    {
        if (! $path || preg_match('#^https?://#', $path)) {
            return $path ?: null;
        }

        $request = $this->requestStack->getCurrentRequest();

        return $request ? $request->getSchemeAndHttpHost() . $request->getBasePath() . '/' . ltrim($path, '/') : $path;
    }
}
