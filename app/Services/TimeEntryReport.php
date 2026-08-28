<?php

namespace App\Services;

use App\Models\CompanySetting;
use App\Models\TimeEntry;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfDocument;
use Illuminate\Support\Collection;

class TimeEntryReport
{
    public function __construct(
        private readonly ?int $userId = null,
        private readonly ?string $desde = null,
        private readonly ?string $hasta = null,
    ) {}

    /**
     * @return Collection<int, TimeEntry>
     */
    public function entries(): Collection
    {
        return TimeEntry::closedQuery($this->userId, $this->desde, $this->hasta)
            ->with('user')
            ->orderBy('started_at')
            ->get();
    }

    /**
     * @return Collection<int, array{user: User, hours: float, pay: float}>
     */
    public function summary(): Collection
    {
        return TimeEntry::summarize($this->entries());
    }

    public function pdf(): PdfDocument
    {
        return Pdf::loadView('pdf.time-entries.monthly-report', [
            'entries' => $this->entries(),
            'summary' => $this->summary(),
            'company' => CompanySetting::query()->first(),
            'desde' => $this->desde,
            'hasta' => $this->hasta,
        ]);
    }
}
