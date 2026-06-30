<?php

namespace App\Console\Commands;

use App\Models\Contact;
use App\Services\PhoneNumberSanitizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditContactData extends Command
{
    protected $signature = 'wa:contacts-audit {--json : Output JSON}';
    protected $description = 'Audit WhatsApp contact data hygiene';

    public function handle(): int
    {
        $sanitizer = app(PhoneNumberSanitizer::class);
        $contacts = Contact::with('group:id,name')->get();

        $invalid = [];
        foreach ($contacts as $contact) {
            if ($sanitizer->normalize($contact->number) === null) {
                $invalid[] = [
                    'id' => $contact->id,
                    'number' => $contact->number,
                    'group' => $contact->group?->name,
                ];
            }
        }

        $duplicates = Contact::query()
            ->select('number', DB::raw('COUNT(*) as total'), DB::raw('GROUP_CONCAT(group_id ORDER BY group_id) as group_ids'))
            ->groupBy('number')
            ->having('total', '>', 1)
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'number' => $row->number,
                'total' => (int) $row->total,
                'group_ids' => array_map('intval', explode(',', (string) $row->group_ids)),
            ])
            ->values()
            ->all();

        $statusCounts = Contact::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($total) => (int) $total)
            ->all();

        $result = [
            'ok' => $invalid === [],
            'total_contacts' => $contacts->count(),
            'invalid_count' => count($invalid),
            'duplicate_numbers_count' => count($duplicates),
            'status_counts' => $statusCounts,
            'invalid' => $invalid,
            'duplicates' => $duplicates,
        ];

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return $invalid === [] ? self::SUCCESS : self::FAILURE;
        }

        $this->info('Total kontak: ' . $result['total_contacts']);
        $this->info('Nomor invalid: ' . $result['invalid_count']);
        $this->info('Nomor duplikat antar grup: ' . $result['duplicate_numbers_count']);

        return $invalid === [] ? self::SUCCESS : self::FAILURE;
    }
}
