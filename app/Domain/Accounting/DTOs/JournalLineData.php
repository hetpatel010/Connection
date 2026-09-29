<?php

namespace App\Domain\Accounting\DTOs;

readonly class JournalLineData
{
    public function __construct(
        public string $accountId,
        public int $debitAmount = 0,
        public int $creditAmount = 0,
        public ?string $description = null,
    ) {}
}
