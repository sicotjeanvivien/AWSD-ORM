<?php

declare(strict_types=1);

namespace AWSD\ORM\Core\Metadata;

final class PrimaryKeyMetadata
{
  public function __construct(
    private readonly string $key
  ) {}

  public function key(): string
  {
    return $this->key;
  }
}
