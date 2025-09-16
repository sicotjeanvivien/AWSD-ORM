<?php

declare(strict_types=1);

namespace AWSD\ORM\Core\Metadata;

use AWSD\ORM\Dialect\SqlType;
use AWSD\ORM\Utils\Normalize;

final class ColumnMetadata
{
  private readonly string $propertyName;
  private readonly string $columnName;
  private readonly SqlType $sqlType;
  private readonly bool $nullable;
  private readonly string $defaultValue;
  private readonly string $phpType;

  public function __construct(string $propertyName, bool $nullable)
  {
    $this->propertyName = Normalize::string($propertyName);
    $this->nullable = $nullable;
    $this->initialize();
  }

  /**
   * Get the value of propertyName
   */
  public function propertyName(): string
  {
    return $this->propertyName;
  }

  /**
   * Get the value of columnName
   */
  public function columnName(): string
  {
    return $this->columnName;
  }

  /**
   * Get the value of sqlType
   */
  public function sqlType(): SqlType
  {
    return $this->sqlType;
  }

  /**
   * Get the value of nullable
   */
  public function nullable(): bool
  {
    return $this->nullable;
  }

  /**
   * Get the value of phpType
   */
  public function phpType(): string
  {
    return $this->phpType;
  }

  private function initialize(): void {
    
  }
}
