<?php

declare(strict_types=1);

namespace Elibri\Watermarking\Tests;

use Elibri\Watermarking\Exception\ForbiddenException;
use Elibri\Watermarking\Exception\InvalidAuthException;
use Elibri\Watermarking\Exception\InvalidResponseException;
use Elibri\Watermarking\Exception\NoServerRespondingException;
use Elibri\Watermarking\Exception\NotFoundException;
use Elibri\Watermarking\Exception\ParametersException;
use Elibri\Watermarking\Exception\RequestExpiredException;
use Elibri\Watermarking\Exception\WrongFormatsException;
use Elibri\Watermarking\Format;
use Elibri\Watermarking\Http\Response;
use Elibri\Watermarking\RequestSigner;
use Elibri\Watermarking\Tests\Fake\FakeHttpClient;
use Elibri\Watermarking\WatermarkingClient;
use PHPUnit\Framework\TestCase;

final class WatermarkingClientTest extends TestCase
{
    private const TOKEN = '061df0c87cd0472a5e4e5708fd788246';
    private const SECRET = 'fbef4d3bbb30cbf502bbdb0f8ede3fc7';

    private function client(FakeHttpClient $http): WatermarkingClient
    {
        return new WatermarkingClient(self::TOKEN, self::SECRET, 'https://example.test/', $http);
    }

    public function testWatermarkPostsToTheWatermarkEndpointAndReturnsTheTransactionId(): void
    {
        $http = new FakeHttpClient(new Response(200, 'abc123'));

        $transId = $this->client($http)->watermark(
            '2a0ec1239e285ec46b11',
            'mobi,epub',
            'Ksiazka dla Wojtka Kowalskiego'
        );

        self::assertSame('abc123', $transId);

        $request = $http->lastRequest();
        self::assertSame('POST', $request['method']);
        self::assertSame('https://example.test/watermarking/watermark', $request['url']);
        self::assertSame('2a0ec1239e285ec46b11', $request['params']['record_reference']);
        self::assertSame('mobi,epub', $request['params']['formats']);
        self::assertSame('Ksiazka dla Wojtka Kowalskiego', $request['params']['visible_watermark']);
        self::assertSame(self::TOKEN, $request['params']['token']);
        self::assertArrayNotHasKey('client_symbol', $request['params']);
    }

    public function testThirteenDigitIdentifierIsSentAsIsbn(): void
    {
        $http = new FakeHttpClient(new Response(200, 'abc123'));

        $this->client($http)->watermark('9788324631766', Format::EPUB, 'footer');

        $params = $http->lastRequest()['params'];
        self::assertSame('9788324631766', $params['isbn']);
        self::assertArrayNotHasKey('record_reference', $params);
    }

    public function testClientSymbolIsSentWhenGiven(): void
    {
        $http = new FakeHttpClient(new Response(200, 'abc123'));

        $this->client($http)->watermark('9788324631766', Format::EPUB, 'footer', 'promo-7');

        self::assertSame('promo-7', $http->lastRequest()['params']['client_symbol']);
    }

    public function testEveryRequestIsSignedWithTheSecretAndItsTimestamp(): void
    {
        $http = new FakeHttpClient(new Response(200, 'abc123'));

        $this->client($http)->watermark('9788324631766', Format::EPUB, 'footer');

        $params = $http->lastRequest()['params'];
        $expected = (new RequestSigner(self::SECRET))->sign((int) $params['stamp']);
        self::assertSame($expected, $params['sig']);
    }

    /**
     * The elibri server expects the percent-encoded signature, which the form
     * encoder then encodes a second time. Pin that down so nobody "fixes" it.
     */
    public function testSignatureIsPercentEncodedBeforeItIsSent(): void
    {
        self::assertSame(
            'zGM1V%2FDfJCXwVPmOT7JQ8zcDbFU%3D',
            (new RequestSigner(self::SECRET))->sign(1700000000)
        );
    }

    /**
     * @dataProvider formatProvider
     *
     * @param string|string[] $formats
     */
    public function testFormatsAreNormalized($formats, string $expected): void
    {
        $http = new FakeHttpClient(new Response(200, 'abc123'));

        $this->client($http)->watermark('9788324631766', $formats, 'footer');

        self::assertSame($expected, $http->lastRequest()['params']['formats']);
    }

    /**
     * @return array<string,array{0:string|string[],1:string}>
     */
    public function formatProvider(): array
    {
        return [
            'single string' => ['epub', 'epub'],
            'comma separated' => ['epub,mobi', 'epub,mobi'],
            'array' => [['epub', 'mobi'], 'epub,mobi'],
            'surrounding whitespace' => [' epub , mobi ', 'epub,mobi'],
            'mixed case' => ['EPUB,Mobi', 'epub,mobi'],
            'duplicates collapsed' => ['epub,epub', 'epub'],
            'empty entries dropped' => ['epub,,mobi', 'epub,mobi'],
            'audio' => ['mp3_in_zip', 'mp3_in_zip'],
        ];
    }

    /**
     * @dataProvider badFormatProvider
     *
     * @param string|string[] $formats
     */
    public function testUnsupportedFormatsAreRejectedBeforeAnyRequest($formats): void
    {
        $http = new FakeHttpClient();

        $this->expectException(WrongFormatsException::class);

        try {
            $this->client($http)->watermark('9788324631766', $formats, 'footer');
        } finally {
            self::assertSame([], $http->requests);
        }
    }

    /**
     * @return array<string,array{0:string|string[]}>
     */
    public function badFormatProvider(): array
    {
        return [
            'unknown format' => ['azw3'],
            'concatenated formats' => ['epubmobi'],
            'only separators' => [','],
            'empty string' => [''],
            'empty array' => [[]],
            'one bad among good' => [['epub', 'doc']],
        ];
    }

    public function testEmptyIdentifierIsRejected(): void
    {
        $this->expectException(ParametersException::class);

        $this->client(new FakeHttpClient())->watermark('  ', Format::EPUB, 'footer');
    }

    public function testDeliverPostsTheTransactionId(): void
    {
        $http = new FakeHttpClient(new Response(200, 'ok'));

        self::assertSame('ok', $this->client($http)->deliver('abc123'));

        $request = $http->lastRequest();
        self::assertSame('POST', $request['method']);
        self::assertSame('https://example.test/watermarking/deliver', $request['url']);
        self::assertSame('abc123', $request['params']['trans_id']);
    }

    public function testEmptyTransactionIdIsRejected(): void
    {
        $this->expectException(ParametersException::class);

        $this->client(new FakeHttpClient())->deliver('');
    }

    /**
     * @dataProvider listingProvider
     */
    public function testListingsAreFetchedWithGetAndDecoded(string $method, string $endpoint): void
    {
        $http = new FakeHttpClient(new Response(200, '[{"record_reference":"abc","title":"Book"}]'));

        $files = $this->client($http)->{$method}();

        self::assertSame([['record_reference' => 'abc', 'title' => 'Book']], $files);

        $request = $http->lastRequest();
        self::assertSame('GET', $request['method']);
        self::assertSame('https://example.test/watermarking/' . $endpoint, $request['url']);
    }

    /**
     * @return array<int,array{0:string,1:string}>
     */
    public function listingProvider(): array
    {
        return [
            ['availableFiles', 'available_files.json'],
            ['soonAvailableFiles', 'soon_available_files.json'],
            ['soonUnavailableFiles', 'soon_unavailable_files.json'],
        ];
    }

    public function testUndecodableListingRaisesInvalidResponse(): void
    {
        $http = new FakeHttpClient(new Response(200, '<html>oops</html>'));

        $this->expectException(InvalidResponseException::class);

        $this->client($http)->availableFiles();
    }

    public function testAcceptedAndPreconditionFailedBodiesArePassedThrough(): void
    {
        foreach ([202, 412] as $code) {
            $http = new FakeHttpClient(new Response($code, 'body-' . $code));
            self::assertSame('body-' . $code, $this->client($http)->deliver('abc123'));
        }
    }

    /**
     * @dataProvider errorStatusProvider
     */
    public function testErrorStatusCodesMapToExceptions(int $status, string $exceptionClass): void
    {
        $http = new FakeHttpClient(new Response($status, 'server said so'));

        $this->expectException($exceptionClass);

        $this->client($http)->deliver('abc123');
    }

    /**
     * @return array<string,array{0:int,1:class-string}>
     */
    public function errorStatusProvider(): array
    {
        return [
            'bad parameters' => [400, ParametersException::class],
            'unauthorized' => [401, InvalidAuthException::class],
            'forbidden' => [403, ForbiddenException::class],
            'not found' => [404, NotFoundException::class],
            'request expired' => [408, RequestExpiredException::class],
            'server error' => [500, NoServerRespondingException::class],
            'unexpected code' => [418, NoServerRespondingException::class],
        ];
    }

    public function testParametersExceptionCarriesTheServerExplanation(): void
    {
        $http = new FakeHttpClient(new Response(400, 'unknown record reference'));

        $this->expectExceptionMessage('unknown record reference');

        $this->client($http)->deliver('abc123');
    }

    public function testConnectionFailureBecomesNoServerResponding(): void
    {
        $http = FakeHttpClient::failing();

        try {
            $this->client($http)->deliver('abc123');
            self::fail('Expected NoServerRespondingException');
        } catch (NoServerRespondingException $e) {
            self::assertSame(1001, $e->getCode());
            self::assertNotNull($e->getPrevious(), 'the original cause should be kept');
            self::assertSame('Could not resolve host', $e->getPrevious()->getMessage());
        }
    }

    public function testHostTrailingSlashIsNotDoubled(): void
    {
        $http = new FakeHttpClient(new Response(200, 'ok'));

        (new WatermarkingClient(self::TOKEN, self::SECRET, 'https://example.test///', $http))
            ->deliver('abc123');

        self::assertSame('https://example.test/watermarking/deliver', $http->lastRequest()['url']);
    }

    public function testDefaultHostPointsAtElibri(): void
    {
        self::assertSame('https://www.elibri.com.pl', WatermarkingClient::DEFAULT_HOST);
    }
}
