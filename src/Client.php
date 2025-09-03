<?php
declare(strict_types=1);

namespace Wheakerd\SseClient;

use Psr\Http\Message\UriInterface;
use function array_merge;
use function ctype_xdigit;
use function hexdec;
use function json_validate;
use function ltrim;
use function preg_split;
use function sprintf;
use function str_starts_with;
use function strlen;
use function substr;
use function trim;

final class Client
{
	private \Swoole\Coroutine\Client $client;

	private UriInterface $uri;

	private bool $connected;

	private array $headers = [
		'Accept'        => 'text/event-stream',
		'Cache-Control' => 'no-cache',
		'Connection'    => 'keep-alive',
		'Content-Type'  => 'application/json',
	];

	/**
	 * Clients accept an array of constructor parameters.
	 *
	 * Here's an example of creating a client using a base_uri and an array of
	 * default request options to apply to each request:
	 *
	 *     $client = new Client('https://www.example.com', 0.5);
	 *
	 * Client configuration settings include the following options:
	 *
	 * - uri: (string) request uri.
	 * - timeout: Connection timeout.
	 *
	 * @param string $uri
	 * @param float  $timeout
	 */
	public function __construct(string $uri, float $timeout = 0.5)
	{
		// Convert the base_uri to a UriInterface
		$this->uri = new Uri($uri);

		$this->client = new \Swoole\Coroutine\Client(
			type: $this->uri->getScheme() === 'https' ? SWOOLE_SOCK_TCP | SWOOLE_SSL : SWOOLE_SOCK_TCP);

		$this->connected = $this->client->connect($this->uri->getHost(), $this->uri->getPort(), $timeout);
	}

	public function setHeader(string $name, int|string $value): void
	{
		$this->headers[$name] = $value;
	}

	public function setHeaders(array $headers): void
	{
		$this->headers = array_merge($headers, $this->headers);
	}

	public function isConnected(): bool
	{
		return $this->connected;
	}

	public function close(): void
	{
		$this->client->close();
	}

	public function send(string $data = ''): int|bool
	{
		$headerContent = '';
		foreach ($this->headers as $name => $value) {
			$headerContent .= sprintf("%s: %s\r\n", $name, $value);
		}

		$dataLength = strlen($data);

		$request = "POST " . $this->uri->getPath() . " HTTP/1.1\r\n" .
		           "Host: " . $this->uri->getHost() . "\r\n" .
		           $headerContent .
		           "Content-Length: " . $dataLength . "\r\n\r\n" .
		           $data;

		return $this->client->send($request);
	}

	public function sta()
	{
		return $this->client->errCode;
	}

	public function recv(): string|bool
	{
		return $this->client->recv();
	}

	public function decode(string $data): string|false|null
	{
		$lines = preg_split("/\r\n|\n|\r/", $data);

		$chunkLength = 0;

		foreach ($lines as $line) {
			$trimmedLine = ltrim(trim($line), "\r\n");

			if (ctype_xdigit($trimmedLine)) {
				$chunkLength = hexdec($trimmedLine);
				continue;
			}
			if (!str_starts_with($trimmedLine, 'data:')) {
				continue;
			}
			if ($chunkLength === 0 || $chunkLength != strlen($line) + 2) {
				continue;
			}

			$jsonString = substr($line, 5);

			if (!json_validate($jsonString)) {
				continue;
			}

			[
				'code' => $code,
				'data' => $data,
			] = json_decode($jsonString, true);
			if (3 === $code) {
				return $data;
			}
			if (0 === $code) {
				return false;
			}
		}

		return null;
	}
}