# WA Retention Backup & Restore

Backup retention dibuat oleh command:

```bash
php8.2 artisan wa:retention-cleanup --purge
```

Lokasi backup:

```text
storage/app/backups/wa-retention/YYYYMMDD-HHMMSS/
```

Isi folder:

- `manifest.json`: ringkasan cutoff, jumlah row kandidat, jumlah row yang dihapus, dan path backup.
- `wa_logs.jsonl`
- `wa_inbox.jsonl`
- `wa_schedules.jsonl`
- `approval_requests.jsonl`

Setiap baris `.jsonl` adalah satu row database dalam format JSON.

## Restore Manual

1. Pilih folder backup yang benar dari `storage/app/backups/wa-retention/`.
2. Baca `manifest.json` untuk memastikan cutoff dan tabel yang ingin direstore.
3. Restore hanya ke staging/dummy dulu.
4. Import baris JSON ke tabel target dengan script satu kali yang memakai `DB::table($table)->updateOrInsert(['id' => $id], $row)`.
5. Setelah staging valid, ulangi restore terbatas di production untuk row yang benar-benar diperlukan.

## Retention Default

- `wa_logs`: 90 hari
- `wa_inbox`: 365 hari
- `wa_schedules`: 180 hari
- `approval_requests`: 365 hari
- folder backup retention: 30 hari
- runtime logs/sessions: 14 hari

Command retention selalu membuat backup sebelum purge. Data hari berjalan tidak masuk cutoff karena cutoff memakai awal hari dan umur data dalam hari.
