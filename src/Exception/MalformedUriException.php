<?php
declare(strict_types=1);

namespace Wheakerd\SseClient\Exception;

use InvalidArgumentException;

/**
 * Exception thrown if a URI cannot be parsed because it's malformed.
 */
final class MalformedUriException extends InvalidArgumentException
{
}
