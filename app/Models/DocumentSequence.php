<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class DocumentSequence extends Model
{
    public function ensureExists(
        int $companyId,
        string $documentType
    ): void {
        $sql = "
            INSERT IGNORE INTO document_sequences
            (
                company_id,
                document_type,
                next_number,
                last_issued_number
            )
            VALUES
            (
                :company_id,
                :document_type,
                1,
                NULL
            )
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute([
            'company_id' => $companyId,
            'document_type' => $documentType,
        ]);
    }

    public function findByCompanyAndType(
        int $companyId,
        string $documentType
    ): ?array {
        $sql = "
            SELECT *
            FROM document_sequences
            WHERE company_id = :company_id
            AND document_type = :document_type
            LIMIT 1
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute([
            'company_id' => $companyId,
            'document_type' => $documentType,
        ]);

        $sequence = $statement->fetch();

        if ($sequence === false) {
            return null;
        }

        return $sequence;
    }

    public function lockForUpdate(
        int $companyId,
        string $documentType
    ): ?array {
        $sql = "
            SELECT *
            FROM document_sequences
            WHERE company_id = :company_id
            AND document_type = :document_type
            LIMIT 1
            FOR UPDATE
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute([
            'company_id' => $companyId,
            'document_type' => $documentType,
        ]);

        $sequence = $statement->fetch();

        if ($sequence === false) {
            return null;
        }

        return $sequence;
    }

    public function setStartingNumber(
        int $sequenceId,
        int $nextNumber
    ): bool {
        $sql = "
            UPDATE document_sequences
            SET
                next_number = :next_number,
                updated_at = NOW()
            WHERE id = :id
            AND last_issued_number IS NULL
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute([
            'next_number' => $nextNumber,
            'id' => $sequenceId,
        ]);

        return $statement->rowCount() === 1;
    }

    public function advance(
        int $sequenceId,
        int $issuedNumber,
        int $nextNumber
    ): bool {
        $sql = "
            UPDATE document_sequences
            SET
                last_issued_number = :issued_number,
                next_number = :next_number,
                updated_at = NOW()
            WHERE id = :id
            AND next_number = :expected_number
        ";

        $statement = $this->db->prepare($sql);

        $statement->execute([
            'issued_number' => $issuedNumber,
            'next_number' => $nextNumber,
            'id' => $sequenceId,
            'expected_number' => $issuedNumber,
        ]);

        return $statement->rowCount() === 1;
    }
}