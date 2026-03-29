# tile.expert service

Symfony + Docker backend for:
- parsing tile prices from `tile.expert`
- orders statistics
- SOAP order creation
- single order retrieval
- full-text search (Manticore + PostgreSQL fallback)

updated DB file - docker/postgres/init/01_schema_updated.sql

## Project structure

```text
tile-app/
├── docker/
├── src/         ← Symfony app
├── docker-compose.yml
├── Makefile
├── README.md
```

## Stack
- PHP 8.3 / Symfony 7.4
- PostgreSQL 16
- Redis 7
- Manticore Search
- Nginx
- Docker Compose

## Build and run

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php bin/console doctrine:migrations:migrate -n
```

Application will be available at:

```text
http://localhost:${APP_PORT:-8080}
```

## Quick start

1. Copy env file:

```bash
cp .env.example .env
```

2. Build and start containers:

```bash
docker compose up -d --build
```

3. Install PHP dependencies (inside container):

```bash
docker compose exec app composer install
```

4. Run migrations:

```bash
docker compose exec app php bin/console doctrine:migrations:migrate -n
```

## Useful commands

```bash
make up
make down
make logs
make ps
make bash
```

Manual reindex command:

```bash
docker compose exec app php bin/console app:search:reindex --limit=10000
```

Run tests:

```bash
docker compose exec app php bin/phpunit
```

## API documentation (Swagger)

- Swagger UI: [http://localhost:${APP_PORT:-8080}/api/doc](http://localhost:8080/api/doc)
- OpenAPI JSON: [http://localhost:${APP_PORT:-8080}/api/doc.json](http://localhost:8080/api/doc.json)

OpenAPI dump from CLI:

```bash
docker compose exec app php bin/console nelmio:apidoc:dump --format=json
```

## Endpoints

### 1) GET `/api/price`
Returns tile price in EUR from source site.

Query params:
- `factory` (required)
- `collection` (required)
- `article` (required)

Example:

```bash
curl "http://localhost:${APP_PORT:-8080}/api/price?factory=marca-corona&collection=arteseta&article=k263-arteseta-camoscio-s000628660"
```

Response example:

```json
{
  "price": 59.99,
  "factory": "marca-corona",
  "collection": "arteseta",
  "article": "k263-arteseta-camoscio-s000628660"
}
```

### 2) GET `/api/orders/stats`
Returns orders aggregation grouped by day/month/year with pagination metadata.

Query params:
- `groupBy`: `day | month | year` (default `month`)
- `page` (default `1`)
- `limit` (default `20`, max `500`)

Example:

```bash
curl "http://localhost:${APP_PORT:-8080}/api/orders/stats?groupBy=month&page=1&limit=20"
```

### 3) POST `/api/orders/soap`
Creates order (and `orders_article` rows) from SOAP XML.

Headers:
- `Content-Type: text/xml; charset=UTF-8`

Example:

```bash
curl -X POST "http://localhost:${APP_PORT:-8080}/api/orders/soap" \
  -H "Content-Type: text/xml; charset=UTF-8" \
  --data-binary '<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
  <soapenv:Body>
    <createOrder>
      <order>
        <hash>aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa</hash>
        <token>bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb</token>
        <status>1</status>
        <vat_type>0</vat_type>
        <pay_type>1</pay_type>
        <locale>it</locale>
        <currency>EUR</currency>
        <measure>m</measure>
        <name>SOAP order</name>
        <create_date>2026-03-29 10:00:00</create_date>
        <step>1</step>
        <orders_article>
          <item>
            <amount>2</amount>
            <price>11.11</price>
            <weight>1</weight>
            <packaging_count>1</packaging_count>
            <pallet>1</pallet>
            <packaging>1</packaging>
            <swimming_pool>false</swimming_pool>
          </item>
        </orders_article>
      </order>
    </createOrder>
  </soapenv:Body>
</soapenv:Envelope>'
```

### 4) GET `/api/orders/{id}`
Returns one order by id.

Example:

```bash
curl "http://localhost:${APP_PORT:-8080}/api/orders/123"
```

### 5) GET `/api/search`
Search over orders via Manticore.
If Manticore is unavailable, service falls back to PostgreSQL `LIKE`.

Query params:
- `q` (required, min 2 chars)
- `page` (default `1`)
- `limit` (default `20`, max `100`)

Example:

```bash
curl "http://localhost:${APP_PORT:-8080}/api/search?q=marca&page=1&limit=20"
```

### 6) GET/POST `/api/search/reindex`
Manual reindex from PostgreSQL to Manticore.

Query params:
- `limit` (default `10000`, max `50000`)

Example:

```bash
curl -X POST "http://localhost:${APP_PORT:-8080}/api/search/reindex?limit=10000"
```

Response example:

```json
{
  "status": "success",
  "indexed": 10000,
  "limit": 10000
}
```

## Environment variables

Main variables (from root `.env` / docker-compose):
- `APP_PORT`
- `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`
- `REDIS_PORT`
- `MANTICORE_MYSQL_PORT`, `MANTICORE_HTTP_PORT`
- `APP_ENV`, `APP_DEBUG`
- `MANTICORE_URL`
