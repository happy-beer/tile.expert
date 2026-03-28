<?php

declare(strict_types=1);

namespace App\Tests\Unit\Integration;

use App\Exception\ExternalResourceNotFoundException;
use App\Exception\PriceParsingException;
use App\Integration\TileExpertClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class TileExpertClientTest extends TestCase
{
    public function testExtractPriceEuroFromAppStorePayload(): void
    {
        $payload = [
            'slider' => [
                'elements' => [
                    [
                        'url' => 'k263-arteseta-camoscio-s000628660',
                        'priceEuroIt' => '59,99',
                    ],
                ],
            ],
        ];

        $html = sprintf(
            '<div data-js-react-on-rails-store="appStore" data-js-react-on-rails-store-data="%s"></div>',
            htmlspecialchars((string) json_encode($payload, JSON_THROW_ON_ERROR), ENT_QUOTES | ENT_HTML5),
        );

        $client = new TileExpertClient(new MockHttpClient());

        self::assertSame(59.99, $client->extractPriceEuro($html, 'k263-arteseta-camoscio-s000628660'));
    }

    public function testExtractPriceEuroThrowsWhenPriceCannotBeParsed(): void
    {
        $client = new TileExpertClient(new MockHttpClient());

        $this->expectException(PriceParsingException::class);
        $client->extractPriceEuro('<html><body>No appStore payload</body></html>', 'missing-article');
    }

    public function testFetchProductHtmlThrowsNotFoundFor404(): void
    {
        $mockClient = new MockHttpClient([
            new MockResponse('', ['http_code' => 404]),
        ]);

        $client = new TileExpertClient($mockClient);

        $this->expectException(ExternalResourceNotFoundException::class);
        $client->fetchProductHtml('marca-corona', 'arteseta', 'k263-arteseta-camoscio-s000628660');
    }
}
