<?php

namespace Wexample\SymfonySeo\Helper;

final class SchemaOrgHelper
{
    /**
     * Every `@id` opening on a slash made absolute on the site's address: the
     * person of the configuration, `/#person`, and the author referring to it
     * are the same node on every host the site is served from.
     */
    public static function resolveIds(mixed $value, ?string $baseUrl): mixed
    {
        if (! is_array($value) || ! $baseUrl) {
            return $value;
        }

        foreach ($value as $name => $item) {
            $value[$name] = '@id' === $name && is_string($item) && str_starts_with($item, '/')
                ? rtrim($baseUrl, '/').$item
                : self::resolveIds($item, $baseUrl);
        }

        return $value;
    }
}
