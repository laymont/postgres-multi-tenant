<?php

declare(strict_types=1);

namespace Laymont\PostgresMultiTenant\Exceptions;

use InvalidArgumentException;

final class InvalidTenantSchemaException extends InvalidArgumentException {}
