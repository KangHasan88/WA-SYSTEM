# WA System Token Rotation SOP

## Prinsip

- Token mentah hanya boleh tampil sekali saat dibuat atau dirotasi.
- Database hanya menyimpan `token_hash`, bukan token mentah.
- Token disimpan di `.env`, secret manager, atau konfigurasi lokal modul terkait.
- Jangan menulis token di Kanban, chat, log, GitHub, atau dokumentasi.

## API Client Token

Rotasi lewat UI:

1. Buka `/api-clients`.
2. Pilih client modul.
3. Klik rotate.
4. Salin token baru saat tampil sekali.
5. Update `.env` modul pemakai token.
6. Jalankan health/auth check modul.
7. Pastikan `last_used_at` bergerak setelah modul memakai token baru.

Rotasi lewat CLI:

```bash
php8.2 artisan wa:api-client-token "Kurmigo DMS" --slug=dms --scope=read:status,send:message,approval:request,approval:read --rate=60
```

## Webhook Token

Generate token:

```bash
php8.2 artisan wa:webhook-token --show-env
```

Set hasilnya ke `.env`:

```text
WA_WEBHOOK_TOKEN=...
```

Lalu update Node sender agar mengirim header:

```text
X-WA-Webhook-Token: ...
```

Jika Node berjalan satu host/local, webhook tetap diterima dari private/local IP. Token membuat skenario reverse proxy/public menjadi lebih aman.

## Audit

Jalankan:

```bash
php8.2 artisan wa:secrets-audit --json
```

Audit ini hanya menampilkan status/panjang secret dan metadata token. Nilai secret tidak dicetak.
