<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Service\SoapOrderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SoapOrderController extends AbstractController
{
    #[Route('/api/orders/soap', name: 'api_orders_soap_create', methods: ['POST'])]
    public function __invoke(Request $request, SoapOrderService $soapOrderService): Response
    {
        try {
            [$orderPayload, $articlesPayload] = $this->extractPayload((string) $request->getContent());
        } catch (\Throwable $e) {
            return $this->soapFault('Client', $e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        try {
            $orderId = $soapOrderService->createOrderWithArticles($orderPayload, $articlesPayload);
        } catch (\InvalidArgumentException $e) {
            return $this->soapFault('Client', $e->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return $this->soapFault('Server', $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $xml = $this->soapEnvelope(
            sprintf(
                '<response><status>success</status><orderId>%d</orderId><articlesCount>%d</articlesCount></response>',
                $orderId,
                count($articlesPayload),
            ),
        );

        return new Response($xml, Response::HTTP_OK, [
            'Content-Type' => 'text/xml; charset=UTF-8',
        ]);
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<int, array<string, mixed>>}
     */
    private function extractPayload(string $xml): array
    {
        $xml = trim($xml);
        if ($xml === '') {
            throw new \InvalidArgumentException('SOAP request body is empty.');
        }

        $doc = new \DOMDocument('1.0', 'UTF-8');
        if (@$doc->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS) !== true) {
            throw new \InvalidArgumentException('Malformed XML body.');
        }

        $xpath = new \DOMXPath($doc);
        $bodyNode = $xpath->query('//*[local-name()="Body"]')->item(0);
        if (!$bodyNode instanceof \DOMElement) {
            throw new \InvalidArgumentException('SOAP Body node was not found.');
        }

        $orderNode = $xpath->query('.//*[local-name()="order"]', $bodyNode)->item(0);

        $orderPayload = [];
        if ($orderNode instanceof \DOMElement) {
            $orderPayload = $this->directLeafFields($orderNode, ['orders_article', 'order_article', 'articles', 'order_articles']);
        }

        if ($orderPayload === []) {
            foreach ($bodyNode->childNodes as $child) {
                if ($child instanceof \DOMElement) {
                    $orderPayload = $this->directLeafFields($child, ['orders_article', 'order_article', 'articles', 'order_articles']);
                    if ($orderPayload !== []) {
                        break;
                    }
                }
            }
        }

        if ($orderPayload === []) {
            throw new \InvalidArgumentException('Order payload was not found in SOAP body.');
        }

        $articlesPayload = [];

        if ($orderNode instanceof \DOMElement) {
            $articlesPayload = array_merge(
                $articlesPayload,
                $this->extractArticlesFromContainers($orderNode),
                $this->extractDirectArticleNodes($orderNode),
            );
        }

        $articlesPayload = array_merge(
            $articlesPayload,
            $this->extractArticlesFromContainers($bodyNode),
            $this->extractDirectArticleNodes($bodyNode),
        );

        $articlesPayload = $this->deduplicateRows($articlesPayload);

        return [$orderPayload, $articlesPayload];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function deduplicateRows(array $rows): array
    {
        $result = [];
        $seen = [];

        foreach ($rows as $row) {
            ksort($row);
            $key = md5((string) json_encode($row, JSON_UNESCAPED_UNICODE));
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $result[] = $row;
        }

        return $result;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function extractArticlesFromContainers(\DOMElement $root): array
    {
        $containers = ['orders_article', 'order_articles', 'articles'];
        $rows = [];

        foreach ($root->childNodes as $child) {
            if (!$child instanceof \DOMElement) {
                continue;
            }

            $name = strtolower($child->localName ?: $child->nodeName);
            if (!in_array($name, $containers, true)) {
                continue;
            }

            $containerDirect = $this->directLeafFields($child);
            if ($this->looksLikeArticleRow($containerDirect)) {
                $rows[] = $containerDirect;
            }

            foreach ($child->childNodes as $itemNode) {
                if (!$itemNode instanceof \DOMElement) {
                    continue;
                }

                $row = $this->directLeafFields($itemNode);
                if ($this->looksLikeArticleRow($row)) {
                    $rows[] = $row;
                }
            }
        }

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function extractDirectArticleNodes(\DOMElement $root): array
    {
        $rows = [];
        $names = ['order_article', 'article', 'item'];

        foreach ($root->childNodes as $child) {
            if (!$child instanceof \DOMElement) {
                continue;
            }

            $name = strtolower($child->localName ?: $child->nodeName);
            if (!in_array($name, $names, true)) {
                continue;
            }

            $row = $this->directLeafFields($child);
            if ($this->looksLikeArticleRow($row)) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * @param array<string> $ignoreNames
     * @return array<string, mixed>
     */
    private function directLeafFields(\DOMElement $node, array $ignoreNames = []): array
    {
        $out = [];
        $ignore = array_map('strtolower', $ignoreNames);

        foreach ($node->childNodes as $child) {
            if (!$child instanceof \DOMElement) {
                continue;
            }

            $name = strtolower($child->localName ?: $child->nodeName);
            if (in_array($name, $ignore, true)) {
                continue;
            }

            if ($this->hasElementChildren($child)) {
                continue;
            }

            $out[$name] = trim((string) $child->textContent);
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function looksLikeArticleRow(array $row): bool
    {
        foreach (['article_id', 'amount', 'price', 'price_eur', 'weight', 'packaging_count', 'pallet', 'packaging', 'swimming_pool'] as $key) {
            if (array_key_exists($key, $row)) {
                return true;
            }
        }

        return false;
    }

    private function hasElementChildren(\DOMElement $node): bool
    {
        foreach ($node->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                return true;
            }
        }

        return false;
    }

    private function soapFault(string $code, string $message, int $status): Response
    {
        $faultXml = sprintf(
            '<soap:Fault><faultcode>%s</faultcode><faultstring>%s</faultstring></soap:Fault>',
            htmlspecialchars($code, ENT_XML1 | ENT_COMPAT, 'UTF-8'),
            htmlspecialchars($message, ENT_XML1 | ENT_COMPAT, 'UTF-8'),
        );

        return new Response($this->soapEnvelope($faultXml), $status, [
            'Content-Type' => 'text/xml; charset=UTF-8',
        ]);
    }

    private function soapEnvelope(string $innerXml): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    {$innerXml}
  </soap:Body>
</soap:Envelope>
XML;
    }
}
