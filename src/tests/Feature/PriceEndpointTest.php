<?php

declare(strict_types=1);

namespace App\Tests\Feature;

use App\Integration\TileExpertClient;
use App\Service\PriceService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class PriceEndpointTest extends WebTestCase
{
    public function testPriceEndpointReturnsParsedValueUsingMockedExternalRequest(): void
    {
        $client = static::createClient();

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

        $mockHttpClient = new MockHttpClient([
            new MockResponse($html, ['http_code' => 200]),
        ]);

        static::getContainer()->set(
            PriceService::class,
            new PriceService(new TileExpertClient($mockHttpClient)),
        );

        $client->request('GET', '/api/price?factory=marca-corona&collection=arteseta&article=k263-arteseta-camoscio-s000628660');

        self::assertResponseIsSuccessful();

        $data = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(59.99, $data['price']);
        self::assertSame('marca-corona', $data['factory']);
        self::assertSame('arteseta', $data['collection']);
        self::assertSame('k263-arteseta-camoscio-s000628660', $data['article']);
    }
}
