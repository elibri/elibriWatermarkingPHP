<?php

declare(strict_types=1);

namespace Elibri\Watermarking\Tests\Fake;

use Elibri\Watermarking\Exception\ApiConnectionException;
use Elibri\Watermarking\Http\HttpClient;
use Elibri\Watermarking\Http\Response;

/**
 * Records the requests made against it and replays canned answers.
 */
final class FakeHttpClient implements HttpClient
{
    /** @var array<int,array{method:string,url:string,params:array<string,string>}> */
    public $requests = [];

    /** @var Response[] */
    private $responses;

    /** @var \Throwable|null */
    private $failure;

    public function __construct(Response ...$responses)
    {
        $this->responses = $responses;
    }

    public static function failing(?\Throwable $failure = null): self
    {
        $client = new self();
        $client->failure = $failure ?? new ApiConnectionException('Could not resolve host', 6);

        return $client;
    }

    public function request(string $method, string $url, array $params): Response
    {
        $this->requests[] = ['method' => $method, 'url' => $url, 'params' => $params];

        if ($this->failure !== null) {
            throw $this->failure;
        }

        $response = array_shift($this->responses);
        if ($response === null) {
            throw new \LogicException('FakeHttpClient ran out of canned responses');
        }

        return $response;
    }

    /**
     * @return array{method:string,url:string,params:array<string,string>}
     */
    public function lastRequest(): array
    {
        if ($this->requests === []) {
            throw new \LogicException('No request was made');
        }

        return $this->requests[\count($this->requests) - 1];
    }
}
