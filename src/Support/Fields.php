<?php

declare(strict_types=1);

namespace TiMacDonald\JsonApi\Support;

use Illuminate\Http\Request;

use WeakMap;

use function array_key_exists;
use function explode;
use function is_string;

/**
 * @internal
 */
final class Fields
{
    private static self|null $instance;

    /**
     * @var WeakMap<Request, array<string, array<string>|null>>
     */
    private WeakMap $cache;

    private function __construct()
    {
        $this->cache = new WeakMap();
    }

    /**
     * @return self
     */
    public static function getInstance()
    {
        return self::$instance ??= new static();
    }

    /**
     * @return array<string>|null
     */
    public function parse(Request $request, string $resourceType, bool $minimalAttributes)
    {
        return $this->rememberResourceType($request, "type:{$resourceType};minimal:{$minimalAttributes};", static function () use ($request, $resourceType, $minimalAttributes): ?array {
            $typeFields = $request->query('fields') ?? [];

            abort_if(is_string($typeFields), 400, 'The fields parameter must be an array of resource types.');

            if (! array_key_exists($resourceType, $typeFields)) {
                return $minimalAttributes
                    ? []
                    : null;
            }

            $fields = $typeFields[$resourceType] ?? '';

            abort_if(! is_string($fields), 400, 'The fields parameter value must be a comma seperated list of attributes.');

            return array_filter(explode(',', $fields), static fn (string $value): bool => $value !== '');
        });
    }

    /**
     * @infection-ignore-all
     *
     * @param (callable(): array<int, string>|null) $callback
     * @return array<int, string>|null
     */
    private function rememberResourceType(Request $request, string $resourceType, callable $callback)
    {
        $this->cache[$request] ??= [];

        return $this->cache[$request][$resourceType] ??= $callback();
    }

    /**
     * @return void
     */
    public function flush()
    {
        $this->cache = new WeakMap();
    }
}
