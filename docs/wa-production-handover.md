# WA System Production Handover

Dokumen ini adalah SOP operasional WA System production untuk operator/admin.

## Ringkasan Sistem

- URL aplikasi: `https://wa.kurmigo.id/wa-blast`
- Laravel app path VPS: `/var/www/kurmigo-wa`
- Service PM2:
  - `wa-blast`: Node WhatsApp sender
  - `wa-queue`: Laravel queue worker
- Target rate kirim aman: maksimal 100 penerima per jam, dengan jeda otomatis.
- Smoke test aman: `php8.2 artisan wa:smoke-deploy --json`

## PIC dan Eskalasi

Gunakan daftar ini sebagai struktur PIC. Isi nama/nomor aktual di dokumen internal yang tidak dipush ke GitHub.

| Level | Peran | Tanggung jawab | Kapan dieskalasi |
| --- | --- | --- | --- |
| L1 | Operator WA | Scan QR, cek status, cek riwayat pengiriman, restart ringan | WA disconnected, delivery gagal berulang |
| L2 | Admin Aplikasi | Cek API client, approval, queue, schedule, data kontak | Endpoint API error, queue menumpuk, token perlu rotasi |
| L3 | DevOps/Developer | Debug log, rollback, patch kode, migrasi database | Error setelah deploy, service tidak bisa start |
| Owner | Business Owner | Approval risiko tinggi dan keputusan pause blast | Ada risiko akun WA diblokir atau incident customer-facing |

## SOP Harian

1. Buka dashboard WA Blast.
2. Pastikan indikator status menunjukkan WhatsApp siap/connected.
3. Jalankan health check dari VPS:
   ```bash
   cd /var/www/kurmigo-wa
   php8.2 artisan wa:health-check --json --no-alert
   ```
4. Pastikan:
   - `ok=true`
   - `node.running=true`
   - `whatsapp.connected=true`
   - `queue.pending_jobs` tidak tinggi
   - `queue.failed_jobs=0`
5. Cek PM2:
   ```bash
   pm2 status
   ```
6. Cek riwayat pengiriman dan delivery report bila ada komplain.

## SOP Scan QR / Reconnect WhatsApp

Gunakan SOP ini jika status WA disconnected atau QR muncul.

1. Buka `https://wa.kurmigo.id/wa-blast`.
2. Lihat indikator status kanan atas.
3. Jika QR muncul, scan dengan WhatsApp pada nomor resmi operasional.
4. Jika QR tidak muncul tetapi status disconnected:
   - centang `Reset Session (ganti nomor WA)` hanya jika benar-benar ingin reset session
   - klik `Force Reset WhatsApp`
   - tunggu service restart
   - scan QR baru jika muncul
5. Validasi:
   ```bash
   cd /var/www/kurmigo-wa
   php8.2 artisan wa:health-check --json --no-alert
   ```
6. Jika masih disconnected setelah 2 kali reset, eskalasi ke L2/L3.

## SOP Restart Service PM2

Restart ringan jika service hang, memory tidak wajar, atau health check gagal.

```bash
pm2 status
pm2 restart wa-blast
pm2 restart wa-queue
pm2 status
```

Setelah restart:

```bash
cd /var/www/kurmigo-wa
php8.2 artisan wa:health-check --json --no-alert
php8.2 artisan wa:smoke-deploy --json
```

Kriteria aman:

- `wa-blast` online
- `wa-queue` online
- smoke `ok=true`
- `safe_mode.live_whatsapp_send=false`

## SOP Cek Status API dan Queue

Health internal:

```bash
cd /var/www/kurmigo-wa
php8.2 artisan wa:health-check --json --no-alert
```

Smoke setelah deploy atau restart:

```bash
php8.2 artisan wa:smoke-deploy --json
```

Queue:

```bash
php8.2 artisan queue:monitor database:default --max=100
php8.2 artisan queue:failed
```

API status via client token aktif:

```bash
curl -k -H "Authorization: Bearer <TOKEN_DARI_SECURE_VAULT>" \
  -H "Accept: application/json" \
  https://wa.kurmigo.id/api/v1/status
```

Jangan tulis token ke Kanban, chat, log publik, atau GitHub.

## SOP Delivery Issue Dasar

1. Buka `Delivery Report`.
2. Filter status gagal/error.
3. Cek apakah nomor valid dan sudah format Indonesia.
4. Cek queue:
   ```bash
   cd /var/www/kurmigo-wa
   php8.2 artisan wa:health-check --json --no-alert
   ```
5. Jika queue menumpuk:
   ```bash
   pm2 restart wa-queue
   php8.2 artisan queue:restart
   ```
6. Jika WhatsApp disconnected, ikuti SOP scan QR/reconnect.
7. Jangan retry massal sebelum penyebabnya jelas.

## SOP Cleanup History dan Backup

Preview cleanup tanpa hapus:

```bash
cd /var/www/kurmigo-wa
php8.2 artisan wa:retention-cleanup --json
```

Cleanup terjadwal production:

```bash
php8.2 artisan wa:retention-cleanup --purge --json
```

Restore atau audit backup:

- Ikuti `docs/wa-retention-restore.md`
- Jangan hapus backup sebelum owner menyetujui
- Catat tanggal cleanup dan hasilnya di Kanban

## SOP Approval Via WhatsApp

1. Modul aplikasi membuat approval request lewat API.
2. Operator/owner menerima pesan approval.
3. Balas format:
   - `YES <kode>` untuk approve
   - `NO <kode>` untuk reject
4. Cek status approval:
   ```bash
   curl -k -H "Authorization: Bearer <TOKEN_DARI_SECURE_VAULT>" \
     -H "Accept: application/json" \
     https://wa.kurmigo.id/api/v1/approvals/<approval_id>
   ```
5. Untuk smoke parser tanpa kirim WA:
   ```bash
   cd /var/www/kurmigo-wa
   php8.2 artisan wa:webhook-smoke --json
   ```

## SOP Token dan Security

Audit secret:

```bash
cd /var/www/kurmigo-wa
php8.2 artisan wa:secrets-audit --json
```

Rotasi token API client:

```bash
php8.2 artisan wa:api-client-token <slug-client>
```

Generate webhook token:

```bash
php8.2 artisan wa:webhook-token --show-env
```

Aturan:

- Token hanya disimpan di secure vault/internal password manager.
- Token mentah tidak ditulis ke Kanban atau GitHub.
- Jika token dicurigai bocor, rotate segera dan update aplikasi pemakai.

## SOP Deploy dan Rollback

Gunakan dokumen:

- `docs/wa-deploy-checklist.md`

Minimum setiap deploy:

```bash
cd /var/www/kurmigo-wa
git status --short
git diff --check
php8.2 artisan wa:smoke-deploy --json
```

Jika deploy gagal:

1. Stop perubahan lanjutan.
2. Revert commit atau restore backup file.
3. Jalankan cache clear dan restart worker.
4. Jalankan `wa:smoke-deploy --json`.
5. Catat incident dan rollback di Kanban.

## Checklist Handover Operator

- Operator bisa login/buka halaman WA Blast.
- Operator bisa melihat status connected/disconnected.
- Operator bisa scan QR dan force reset dengan benar.
- Operator bisa restart `wa-blast` dan `wa-queue`.
- Operator bisa menjalankan `wa:health-check --json --no-alert`.
- Operator bisa menjalankan `wa:smoke-deploy --json`.
- Operator tahu lokasi Delivery Report.
- Operator tahu kapan harus eskalasi ke L2/L3.
- Operator tidak menyimpan token di tempat publik.

## Command Cepat

```bash
cd /var/www/kurmigo-wa
pm2 status
php8.2 artisan wa:health-check --json --no-alert
php8.2 artisan wa:smoke-deploy --json
php8.2 artisan wa:webhook-smoke --json
php8.2 artisan wa:retention-cleanup --json
php8.2 artisan wa:secrets-audit --json
```
