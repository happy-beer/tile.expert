<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ParameterType;
use Doctrine\Persistence\ManagerRegistry;

final class OrderRepository extends ServiceEntityRepository implements OrderRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function findById(int $id): ?Order
    {
        $entity = $this->find($id);

        return $entity instanceof Order ? $entity : null;
    }

    /**
     * @return array<int, array{bucket: string, count: int}>
     */
    public function aggregateByPeriod(string $groupBy, int $page, int $limit): array
    {
        $period = $this->normalizeGroupBy($groupBy);
        $page = max(1, $page);
        $limit = max(1, min(500, $limit));
        $offset = ($page - 1) * $limit;

        $bucketExpr = $this->buildBucketExpression($period);

        $rows = $this->getEntityManager()->getConnection()->executeQuery(
            "SELECT {$bucketExpr} AS bucket, COUNT(*)::int AS count
             FROM orders
             GROUP BY 1
             ORDER BY 1 DESC
             LIMIT :limit OFFSET :offset",
            [
                'limit' => $limit,
                'offset' => $offset,
            ],
            [
                'limit' => ParameterType::INTEGER,
                'offset' => ParameterType::INTEGER,
            ],
        )->fetchAllAssociative();

        return array_map(
            static fn (array $row): array => [
                'bucket' => (string) $row['bucket'],
                'count' => (int) $row['count'],
            ],
            $rows,
        );
    }

    public function countBucketsByPeriod(string $groupBy): int
    {
        $period = $this->normalizeGroupBy($groupBy);
        $bucketExpr = $this->buildBucketExpression($period);

        return (int) $this->getEntityManager()->getConnection()->fetchOne(
            "SELECT COUNT(*)::int
             FROM (
                SELECT {$bucketExpr} AS bucket
                FROM orders
                GROUP BY 1
             ) grouped",
        );
    }

    private function normalizeGroupBy(string $groupBy): string
    {
        $groupBy = strtolower(trim($groupBy));
        $allowed = ['day', 'month', 'year'];

        if (!in_array($groupBy, $allowed, true)) {
            throw new \InvalidArgumentException('groupBy must be one of: day, month, year.');
        }

        return $groupBy;
    }

    private function buildBucketExpression(string $period): string
    {
        return match ($period) {
            'day' => "to_char(date_trunc('day', create_date), 'YYYY-MM-DD')",
            'month' => "to_char(date_trunc('month', create_date), 'YYYY-MM')",
            'year' => "to_char(date_trunc('year', create_date), 'YYYY')",
        };
    }

    public function findSearchableOrders(int $limit = 5000): array
    {
        $limit = max(1, min(20000, $limit));

        $rows = $this->getEntityManager()->getConnection()->executeQuery(
            'SELECT id, name, email, description, locale, status, create_date
             FROM orders
             ORDER BY id DESC
             LIMIT :limit',
            ['limit' => $limit],
            ['limit' => ParameterType::INTEGER],
        )->fetchAllAssociative();

        return array_map(
            static fn (array $row): array => [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'email' => $row['email'] !== null ? (string) $row['email'] : null,
                'description' => $row['description'] !== null ? (string) $row['description'] : null,
                'locale' => (string) $row['locale'],
                'status' => (int) $row['status'],
                'createdAt' => (string) $row['create_date'],
            ],
            $rows,
        );
    }

    public function searchByText(string $query, int $page, int $limit): array
    {
        $page = max(1, $page);
        $limit = max(1, min(100, $limit));
        $offset = ($page - 1) * $limit;
        $q = '%' . mb_strtolower(trim($query)) . '%';

        $rows = $this->getEntityManager()->getConnection()->executeQuery(
            'SELECT id, name, email, description, locale, status, create_date
             FROM orders
             WHERE lower(name) LIKE :q
                OR lower(COALESCE(email, \'\')) LIKE :q
                OR lower(COALESCE(description, \'\')) LIKE :q
             ORDER BY create_date DESC, id DESC
             LIMIT :limit OFFSET :offset',
            [
                'q' => $q,
                'limit' => $limit,
                'offset' => $offset,
            ],
            [
                'q' => ParameterType::STRING,
                'limit' => ParameterType::INTEGER,
                'offset' => ParameterType::INTEGER,
            ],
        )->fetchAllAssociative();

        return array_map(
            static fn (array $row): array => [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'email' => $row['email'] !== null ? (string) $row['email'] : null,
                'description' => $row['description'] !== null ? (string) $row['description'] : null,
                'locale' => (string) $row['locale'],
                'status' => (int) $row['status'],
                'createdAt' => (string) $row['create_date'],
            ],
            $rows,
        );
    }

    public function countSearchByText(string $query): int
    {
        $q = '%' . mb_strtolower(trim($query)) . '%';

        return (int) $this->getEntityManager()->getConnection()->fetchOne(
            'SELECT COUNT(*)::int
             FROM orders
             WHERE lower(name) LIKE :q
                OR lower(COALESCE(email, \'\')) LIKE :q
                OR lower(COALESCE(description, \'\')) LIKE :q',
            ['q' => $q],
            ['q' => ParameterType::STRING],
        );
    }
}
