<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Promotion extends Model
{
    public function create(array $data): int
    {
        $sql = "
            INSERT INTO promotions
            (
                company_id,
                name,
                code,
                discount_type,
                discount_value,
                maximum_discount_amount,
                minimum_order_amount,
                starts_on,
                ends_on,
                max_uses,
                used_count,
                is_active,
                notes,
                created_by_user_id
            )
            VALUES
            (
                :company_id,
                :name,
                :code,
                :discount_type,
                :discount_value,
                :maximum_discount_amount,
                :minimum_order_amount,
                :starts_on,
                :ends_on,
                :max_uses,
                0,
                :is_active,
                :notes,
                :created_by_user_id
            )
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute([
            'company_id' =>
            $data['company_id'],

            'name' =>
            $data['name'],

            'code' =>
            $data['code'],

            'discount_type' =>
            $data['discount_type'],

            'discount_value' =>
            $data['discount_value'],

            'maximum_discount_amount' =>
            $data['maximum_discount_amount'],

            'minimum_order_amount' =>
            $data['minimum_order_amount'],

            'starts_on' =>
            $data['starts_on'],

            'ends_on' =>
            $data['ends_on'],

            'max_uses' =>
            $data['max_uses'],

            'is_active' =>
            $data['is_active'],

            'notes' =>
            $data['notes'],

            'created_by_user_id' =>
            $data['created_by_user_id'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(
        int $id,
        int $companyId,
        array $data
    ): bool {
        $sql = "
            UPDATE promotions
            SET
                name = :name,
                code = :code,
                discount_type =
                    :discount_type,
                discount_value =
                    :discount_value,
                maximum_discount_amount =
                    :maximum_discount_amount,
                minimum_order_amount =
                    :minimum_order_amount,
                starts_on = :starts_on,
                ends_on = :ends_on,
                max_uses = :max_uses,
                is_active = :is_active,
                notes = :notes,
                updated_at = NOW()
            WHERE id = :id
            AND company_id = :company_id
        ";

        $statement = $this->db->prepare($sql);

        return $statement->execute([
            'name' =>
            $data['name'],

            'code' =>
            $data['code'],

            'discount_type' =>
            $data['discount_type'],

            'discount_value' =>
            $data['discount_value'],

            'maximum_discount_amount' =>
            $data['maximum_discount_amount'],

            'minimum_order_amount' =>
            $data['minimum_order_amount'],

            'starts_on' =>
            $data['starts_on'],

            'ends_on' =>
            $data['ends_on'],

            'max_uses' =>
            $data['max_uses'],

            'is_active' =>
            $data['is_active'],

            'notes' =>
            $data['notes'],

            'id' => $id,
            'company_id' => $companyId,
        ]);
    }

    public function allByCompany(
        int $companyId,
        array $filters = []
    ): array {
        $sql = "
            SELECT
                promotions.*,

                users.name
                    AS created_by_user_name,

                COALESCE(
                    usage_statistics.active_usages,
                    0
                ) AS active_usages,

                COALESCE(
                    usage_statistics.total_discount,
                    0
                ) AS total_discount

            FROM promotions

            INNER JOIN users
                ON users.id =
                    promotions.created_by_user_id

            LEFT JOIN (
                SELECT
                    promotion_id,

                    SUM(
                        status = 'used'
                    ) AS active_usages,

                    SUM(
                        CASE
                            WHEN status = 'used'
                                THEN discount_amount
                            ELSE 0
                        END
                    ) AS total_discount

                FROM promotion_usages

                GROUP BY promotion_id
            ) AS usage_statistics
                ON usage_statistics.promotion_id =
                    promotions.id

            WHERE promotions.company_id =
                :company_id
        ";

        $parameters = [
            'company_id' => $companyId,
        ];

        if (
            isset($filters['active']) &&
            is_int($filters['active'])
        ) {
            $sql .= "
                AND promotions.is_active =
                    :is_active
            ";

            $parameters['is_active'] =
                $filters['active'];
        }

        if (
            isset($filters['search']) &&
            is_string($filters['search']) &&
            $filters['search'] !== ''
        ) {
            $search =
                '%' . $filters['search'] . '%';

            $sql .= "
                AND (
                    promotions.name
                        LIKE :search_name

                    OR promotions.code
                        LIKE :search_code
                )
            ";

            $parameters['search_name'] =
                $search;

            $parameters['search_code'] =
                $search;
        }

        $sql .= "
            ORDER BY
                promotions.is_active DESC,
                promotions.starts_on DESC,
                promotions.id DESC
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    public function availableByCompany(
        int $companyId
    ): array {
        $sql = "
            SELECT *
            FROM promotions
            WHERE company_id = :company_id
            AND is_active = 1
            AND starts_on <= CURDATE()

            AND (
                ends_on IS NULL
                OR ends_on >= CURDATE()
            )

            AND (
                max_uses IS NULL
                OR used_count < max_uses
            )

            ORDER BY name ASC
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute([
            'company_id' => $companyId,
        ]);

        return $statement->fetchAll();
    }

    public function findByIdAndCompany(
        int $id,
        int $companyId
    ): ?array {
        $statement = $this->db->prepare(
            "
            SELECT
                promotions.*,
                users.name
                    AS created_by_user_name
            FROM promotions
            INNER JOIN users
                ON users.id =
                    promotions.created_by_user_id
            WHERE promotions.id = :id
            AND promotions.company_id =
                :company_id
            LIMIT 1
            "
        );

        $statement->execute([
            'id' => $id,
            'company_id' => $companyId,
        ]);

        $promotion = $statement->fetch();

        return $promotion === false
            ? null
            : $promotion;
    }

    public function findForUpdate(
        int $id,
        int $companyId
    ): ?array {
        $statement = $this->db->prepare(
            "
            SELECT *
            FROM promotions
            WHERE id = :id
            AND company_id = :company_id
            LIMIT 1
            FOR UPDATE
            "
        );

        $statement->execute([
            'id' => $id,
            'company_id' => $companyId,
        ]);

        $promotion = $statement->fetch();

        return $promotion === false
            ? null
            : $promotion;
    }

    public function codeExists(
        int $companyId,
        string $code,
        ?int $excludeId = null
    ): bool {
        $sql = "
            SELECT id
            FROM promotions
            WHERE company_id = :company_id
            AND code = :code
        ";

        $parameters = [
            'company_id' => $companyId,
            'code' => $code,
        ];

        if ($excludeId !== null) {
            $sql .= "
                AND id <> :exclude_id
            ";

            $parameters['exclude_id'] =
                $excludeId;
        }

        $sql .= " LIMIT 1";

        $statement = $this->db->prepare($sql);

        $statement->execute($parameters);

        return $statement->fetch() !== false;
    }

    public function setActive(
        int $id,
        int $companyId,
        bool $active
    ): bool {
        $statement = $this->db->prepare(
            "
            UPDATE promotions
            SET
                is_active = :is_active,
                updated_at = NOW()
            WHERE id = :id
            AND company_id = :company_id
            "
        );

        return $statement->execute([
            'is_active' => $active ? 1 : 0,
            'id' => $id,
            'company_id' => $companyId,
        ]);
    }

    public function incrementUsage(
        int $id,
        int $companyId
    ): bool {
        $statement = $this->db->prepare(
            "
            UPDATE promotions
            SET
                used_count =
                    used_count + 1,
                updated_at = NOW()
            WHERE id = :id
            AND company_id = :company_id
            AND (
                max_uses IS NULL
                OR used_count < max_uses
            )
            "
        );

        $statement->execute([
            'id' => $id,
            'company_id' => $companyId,
        ]);

        return $statement->rowCount() === 1;
    }

    public function decrementUsage(
        int $id,
        int $companyId
    ): bool {
        $statement = $this->db->prepare(
            "
            UPDATE promotions
            SET
                used_count =
                    GREATEST(
                        used_count - 1,
                        0
                    ),
                updated_at = NOW()
            WHERE id = :id
            AND company_id = :company_id
            "
        );

        return $statement->execute([
            'id' => $id,
            'company_id' => $companyId,
        ]);
    }

    public function attachToSale(
        int $saleId,
        int $companyId,
        array $promotion,
        float $discountAmount
    ): bool {
        $statement = $this->db->prepare(
            "
            UPDATE sales
            SET
                promotion_id =
                    :promotion_id,

                promotion_name =
                    :promotion_name,

                promotion_code =
                    :promotion_code,

                promotion_discount_amount =
                    :promotion_discount_amount,

                updated_at = NOW()

            WHERE id = :sale_id
            AND company_id = :company_id
            "
        );

        return $statement->execute([
            'promotion_id' =>
            $promotion['id'],

            'promotion_name' =>
            $promotion['name'],

            'promotion_code' =>
            $promotion['code'],

            'promotion_discount_amount' =>
            $discountAmount,

            'sale_id' => $saleId,
            'company_id' => $companyId,
        ]);
    }

    public function createUsage(
        int $companyId,
        int $promotionId,
        int $saleId,
        string $promotionName,
        ?string $promotionCode,
        float $discountAmount
    ): int {
        $statement = $this->db->prepare(
            "
            INSERT INTO promotion_usages
            (
                company_id,
                promotion_id,
                sale_id,
                promotion_name,
                promotion_code,
                discount_amount,
                status
            )
            VALUES
            (
                :company_id,
                :promotion_id,
                :sale_id,
                :promotion_name,
                :promotion_code,
                :discount_amount,
                'used'
            )
            "
        );

        $statement->execute([
            'company_id' => $companyId,
            'promotion_id' => $promotionId,
            'sale_id' => $saleId,
            'promotion_name' => $promotionName,
            'promotion_code' => $promotionCode,
            'discount_amount' => $discountAmount,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function usageForSaleForUpdate(
        int $saleId,
        int $companyId
    ): ?array {
        $statement = $this->db->prepare(
            "
            SELECT *
            FROM promotion_usages
            WHERE sale_id = :sale_id
            AND company_id = :company_id
            AND status = 'used'
            LIMIT 1
            FOR UPDATE
            "
        );

        $statement->execute([
            'sale_id' => $saleId,
            'company_id' => $companyId,
        ]);

        $usage = $statement->fetch();

        return $usage === false
            ? null
            : $usage;
    }

    public function cancelUsage(
        int $usageId,
        int $companyId
    ): bool {
        $statement = $this->db->prepare(
            "
            UPDATE promotion_usages
            SET
                status = 'cancelled',
                cancelled_at = NOW()
            WHERE id = :id
            AND company_id = :company_id
            AND status = 'used'
            "
        );

        $statement->execute([
            'id' => $usageId,
            'company_id' => $companyId,
        ]);

        return $statement->rowCount() === 1;
    }
}
