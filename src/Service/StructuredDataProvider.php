<?php

namespace Wexample\SymfonySeo\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Wexample\SymfonyHelpers\Interface\HeadStructuredDataProviderInterface;
use Wexample\SymfonySeo\DependencyInjection\WexampleSymfonySeoExtension;

/**
 * What the app declares of itself for the engines — the person or the company
 * behind it, the site —, the same on every page: what a knowledge panel and a
 * rich result are drawn from.
 */
class StructuredDataProvider implements HeadStructuredDataProviderInterface
{
    private const string CONTEXT = 'https://schema.org';

    public function __construct(
        #[Autowire(param: WexampleSymfonySeoExtension::PARAMETER_STRUCTURED_DATA)]
        private readonly array $objects,
    ) {
    }

    public function getHeadStructuredData(array $document): array
    {
        return array_map(
            // First when added, as written: the context is read before the rest.
            static fn (array $object): array => isset($object['@context'])
                ? $object
                : ['@context' => self::CONTEXT] + $object,
            array_values(array_filter($this->objects, 'is_array'))
        );
    }
}
