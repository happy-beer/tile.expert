<?php

declare(strict_types=1);

namespace App\Integration;

use App\Exception\ExternalResourceNotFoundException;
use App\Exception\PriceParsingException;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class TileExpertClient
{
    public function __construct(private readonly HttpClientInterface $httpClient)
    {
    }

    public function fetchProductHtml(string $factory, string $collection, string $article): string
    {
        $url = sprintf(
            'https://tile.expert/it/tile/%s/%s/a/%s',
            rawurlencode($factory),
            rawurlencode($collection),
            rawurlencode($article),
        );

        $response = $this->httpClient->request('GET', $url, [
            'timeout' => 15,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (compatible; tile-app/1.0; +https://tile.expert)',
                'Accept-Language' => 'it-IT,it;q=0.9,en;q=0.8',
            ],
        ]);

        $statusCode = $response->getStatusCode();
        if ($statusCode === 404) {
            throw new ExternalResourceNotFoundException('Tile not found on source website.');
        }

        if ($statusCode >= 400) {
            throw new \RuntimeException(sprintf('Source website returned HTTP %d.', $statusCode));
        }

        return $response->getContent(false);
    }

    public function extractPriceEuro(string $html, ?string $article = null): float
    {
        $crawler = new Crawler($html);

        $priceFromAppStore = $this->extractPriceFromAppStore($crawler, $article);
        if ($priceFromAppStore !== null) {
            return $priceFromAppStore;
        }

        throw new PriceParsingException('Unable to parse EUR price from source HTML.');
    }

    private function extractPriceFromAppStore(Crawler $crawler, ?string $article): ?float
    {
        if ($article === null || $article === '') {
            return null;
        }

        $targetArticle = $this->normalizePathValue($article);

        $nodes = $crawler->filter('[data-js-react-on-rails-store="appStore"]');
        if ($nodes->count() === 0) {
            return null;
        }

        foreach ($nodes as $node) {
            $payloadCandidates = [];

            if ($node->hasAttribute('data-js-react-on-rails-store-data')) {
                $payloadCandidates[] = (string) $node->getAttribute('data-js-react-on-rails-store-data');
            }
            if ($node->hasAttribute('data-json')) {
                $payloadCandidates[] = (string) $node->getAttribute('data-json');
            }
            if ($node->textContent !== null && trim($node->textContent) !== '') {
                $payloadCandidates[] = $node->textContent;
            }

            foreach ($payloadCandidates as $payload) {
                $decoded = $this->decodeJsonPayload($payload);
                if (!is_array($decoded)) {
                    continue;
                }

                $elements = $decoded['slider']['elements'] ?? null;
                if (!is_array($elements)) {
                    continue;
                }

                foreach ($elements as $element) {
                    if (!is_array($element)) {
                        continue;
                    }

                    $elementUrl = $element['url'] ?? null;
                    if (!is_string($elementUrl)) {
                        continue;
                    }

                    if ($this->normalizePathValue($elementUrl) !== $targetArticle) {
                        continue;
                    }

                    $rawPrice = $element['priceEuroIt'] ?? null;
                    if (!is_scalar($rawPrice)) {
                        continue;
                    }

                    $price = $this->parsePriceString((string) $rawPrice);
                    if ($price !== null) {
                        return $price;
                    }
                }
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeJsonPayload(string $payload): ?array
    {
        $decodedHtml = html_entity_decode($payload, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $candidate = trim($decodedHtml);
        if ($candidate === '') {
            return null;
        }

        for ($i = 0; $i < 2; $i++) {
            try {
                $data = json_decode($candidate, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                return null;
            }

            if (is_array($data)) {
                return $data;
            }

            if (!is_string($data)) {
                return null;
            }

            $candidate = trim($data);
            if ($candidate === '') {
                return null;
            }
        }

        return null;
    }

    private function normalizePathValue(string $value): string
    {
        $normalized = rawurldecode(trim($value));
        $normalized = trim($normalized, "/ \t\n\r\0\x0B");

        return mb_strtolower($normalized);
    }

    private function extractCandidate(Crawler $crawler, string $selector, ?string $attr): ?string
    {
        $node = $crawler->filter($selector);
        if ($node->count() === 0) {
            return null;
        }

        if ($attr !== null) {
            $value = $node->first()->attr($attr);
            return $value !== null ? trim($value) : null;
        }

        return trim($node->first()->text(''));
    }

    private function parsePriceString(string $raw): ?float
    {
        $normalized = str_replace(["\u{00A0}", ' '], '', trim($raw));
        $normalized = preg_replace('/[^\d,.\-]/u', '', $normalized);
        if ($normalized === null || $normalized === '' || $normalized === '-') {
            return null;
        }

        $lastComma = strrpos($normalized, ',');
        $lastDot = strrpos($normalized, '.');

        if ($lastComma !== false && $lastDot !== false) {
            if ($lastComma > $lastDot) {
                $normalized = str_replace('.', '', $normalized);
                $normalized = str_replace(',', '.', $normalized);
            } else {
                $normalized = str_replace(',', '', $normalized);
            }
        } elseif ($lastComma !== false) {
            $normalized = str_replace(',', '.', $normalized);
        }

        if (!is_numeric($normalized)) {
            return null;
        }

        $price = (float) $normalized;
        if ($price <= 0) {
            return null;
        }

        return round($price, 2);
    }
}
