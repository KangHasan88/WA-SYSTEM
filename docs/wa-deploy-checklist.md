# WA System Deploy Checklist

Dokumen ini dipakai setiap kali deploy WA System ke production.

## Prinsip Aman

- Jangan jalankan pengiriman WA live untuk smoke test.
- Gunakan `wa:smoke-deploy --json` untuk verifikasi end-to-end aman.
- Pastikan semua secret tetap di `.env` dan tidak ditulis ke log, Kanban, atau GitHub.
- Backup file yang diedit langsung di VPS sebelum overwrite manual.
- Setelah deploy, update Kanban dengan commit hash, hasil smoke, dan catatan risiko.

## Pre-Deploy

1. Cek working tree:
   ```bash
   git status --short
   ```
2. Cek syntax file PHP yang berubah:
   ```bash
   find app routes database -name "*.php" -print0 | xargs -0 -n1 php8.2 -l
   ```
3. Cek diff whitespace:
   ```bash
   git diff --check
   ```
4. Jalankan smoke aman:
   ```bash
   php8.2 artisan wa:smoke-deploy --json
   ```
5. Pastikan hasil smoke:
   - `ok=true`
   - `live_whatsapp_send=false`
   - `auth_check_status_200=true`
   - `send_dummy_queued_without_live_send=true`
   - `approval_request_dry_run_accepted=true`
   - `approval_webhook_parser_smoke=true`

## Deploy

1. Pull atau copy file rilis sesuai metode deploy yang dipakai.
2. Jalankan migration jika ada:
   ```bash
   php8.2 artisan migrate --force
   ```
3. Refresh cache aplikasi:
   ```bash
   php8.2 artisan optimize:clear
   php8.2 artisan config:cache
   php8.2 artisan view:cache
   ```
   Jalankan `route:cache` hanya jika semua route legacy sudah valid.
4. Restart worker/service terkait jika ada perubahan job atau Node sender:
   ```bash
   php8.2 artisan queue:restart
   pm2 status
   ```
5. Jalankan smoke ulang:
   ```bash
   php8.2 artisan wa:smoke-deploy --json
   ```

## Post-Deploy

1. Cek halaman utama:
   ```bash
   curl -k -s -o /dev/null -w "wa-blast %{http_code}\n" https://wa.kurmigo.id/wa-blast
   ```
2. Cek health gateway:
   ```bash
   php8.2 artisan wa:health-check --json --no-alert
   ```
3. Cek queue:
   ```bash
   php8.2 artisan queue:monitor database:default --max=100
   ```
4. Cek log error terbaru:
   ```bash
   tail -n 120 storage/logs/laravel.log
   ```
5. Update Kanban:
   - card yang dikerjakan
   - commit hash
   - hasil smoke
   - catatan rollback bila diperlukan

## Rollback

1. Jika rilis dari Git:
   ```bash
   git log --oneline -5
   git revert <commit-hash>
   ```
2. Jika rilis dari copy manual, restore backup file yang dibuat sebelum overwrite.
3. Refresh cache:
   ```bash
   php8.2 artisan optimize:clear
   php8.2 artisan view:cache
   php8.2 artisan view:clear
   ```
4. Restart worker:
   ```bash
   php8.2 artisan queue:restart
   ```
5. Jalankan smoke:
   ```bash
   php8.2 artisan wa:smoke-deploy --json
   ```
6. Catat hasil rollback di Kanban.

## Catatan Smoke Test

Command `wa:smoke-deploy --json` memakai `Queue::fake()` dan transaksi database untuk memastikan:

- API client smoke hanya sementara.
- dummy send tidak mengeksekusi job WA live.
- approval request memakai `dry_run=true`.
- data smoke di-rollback setelah command selesai.
