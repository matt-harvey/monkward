<?php

declare(strict_types=1);

namespace Monkward\Http;

final readonly class MonkwardRoute
{
    public const KIND_LISTING = 'listing';
    public const KIND_DOC = 'doc';
    public const KIND_ASSET = 'asset';

    public function __construct(
        public string $kind,
        public string $template,
        public ?string $relativePath = null,
        public ?string $assetName = null,
    ) {
    }

    public static function listing(string $relativeDir): self
    {
        return new self(self::KIND_LISTING, 'listing', relativePath: $relativeDir);
    }

    public static function doc(string $relativePath): self
    {
        return new self(self::KIND_DOC, 'doc', relativePath: $relativePath);
    }

    public static function asset(string $name): self
    {
        return new self(self::KIND_ASSET, 'asset', assetName: $name);
    }
}
