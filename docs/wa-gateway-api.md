# WA Gateway API Integration

Dokumen ini menjadi rujukan integrasi modul Kurmigo ke WA Blast / WA Gateway.

Base URL production:

```text
https://wa.kurmigo.id/api/v1
```

## Auth

Semua endpoint v1 memakai Bearer token dari API Client Registry.

Header:

```http
Authorization: Bearer <api_token>
Accept: application/json
Content-Type: application/json
```

Scope yang tersedia:

| Scope | Fungsi |
| --- | --- |
| `read:status` | Cek token dan status service |
| `send:message` | Kirim pesan WA via queue |
| `approval:request` | Buat request approval via WA |
| `approval:read` | Polling status approval |

## Error Format

Unauthorized:

```json
{
  "success": false,
  "message": "Unauthorized API client."
}
```

Validation error:

```json
{
  "success": false,
  "message": "Validation failed.",
  "errors": {
    "to": ["The to field is required."]
  }
}
```

Rate limit:

```json
{
  "success": false,
  "message": "API client rate limit exceeded.",
  "limit_per_minute": 60
}
```

## Auth Check

```http
GET /auth-check
```

Required scope: `read:status`

Curl:

```bash
curl -sS https://wa.kurmigo.id/api/v1/auth-check \
  -H "Authorization: Bearer $WA_API_TOKEN" \
  -H "Accept: application/json"
```

## System Status

```http
GET /status
```

Required scope: `read:status`

Response berisi status Laravel, queue, Node sender, koneksi WhatsApp, dan counter pesan API.

Contoh response:

```json
{
  "success": true,
  "status": "ok",
  "whatsapp": {
    "connected": true,
    "qr_available": false
  },
  "queue": {
    "jobs_total": 0,
    "failed_jobs_total": 0
  }
}
```

## Send Message

```http
POST /messages/send
```

Required scope: `send:message`

Payload:

```json
{
  "to": "628123456789",
  "message": "Halo, ini pesan dari modul Kurmigo.",
  "title": "Optional title",
  "image": null
}
```

Response sukses:

```json
{
  "success": true,
  "message": "Message queued.",
  "message_id": "wam_20260629120000_xxxxx",
  "status": "queued",
  "scheduled_at": "2026-06-29T12:00:36+07:00"
}
```

Catatan:

- Nomor boleh diawali `0`, `62`, atau `+62`; sistem akan normalisasi ke format `62...`.
- Pengiriman masuk queue, bukan dikirim langsung.
- Sistem membatasi total dispatch ke 100 penerima per jam dengan jarak aman 36-55 detik.

Curl:

```bash
curl -sS https://wa.kurmigo.id/api/v1/messages/send \
  -H "Authorization: Bearer $WA_API_TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "to": "628123456789",
    "message": "Halo dari modul Kurmigo"
  }'
```

## Request Approval

```http
POST /approvals/request
```

Required scope: `approval:request`

Payload:

```json
{
  "to": "628123456789",
  "source": "codex-local",
  "action": "Deploy perubahan ke VPS production",
  "risk": "high",
  "expires_in_minutes": 15,
  "payload": {
    "ticket": "WA-123"
  },
  "dry_run": false
}
```

Response sukses:

```json
{
  "success": true,
  "approval_id": "apv_20260629121000_xxxxx",
  "code": "123456",
  "status": "pending",
  "risk": "high",
  "dry_run": false,
  "expires_at": "2026-06-29T12:25:00.000000Z",
  "scheduled_at": "2026-06-29T12:10:36+07:00",
  "reply_format": {
    "approve": "YES 123456",
    "reject": "NO 123456"
  }
}
```

Gunakan `dry_run: true` untuk test integrasi tanpa mengirim WhatsApp.

## Poll Approval Status

```http
GET /approvals/{approval_id}
```

Required scope: `approval:read`

Response:

```json
{
  "success": true,
  "approval_id": "apv_20260629121000_xxxxx",
  "status": "pending",
  "source": "codex-local",
  "action": "Deploy perubahan ke VPS production",
  "risk": "high",
  "to_number": "6281*****789",
  "dry_run": false,
  "created_at": "2026-06-29T12:10:00.000000Z",
  "expires_at": "2026-06-29T12:25:00.000000Z",
  "approved_at": null,
  "rejected_at": null,
  "remaining_seconds": 840
}
```

Status:

| Status | Arti |
| --- | --- |
| `pending` | Menunggu reply WA |
| `approved` | Owner reply `YES <code>` |
| `rejected` | Owner reply `NO <code>` |
| `expired` | Sudah lewat `expires_at` |

Endpoint ini tidak mengembalikan `payload` agar data sensitif tidak bocor.

## Recommended Module Pattern

1. Simpan token per modul dengan scope minimum.
2. Untuk notifikasi biasa, panggil `/messages/send` dan simpan `message_id`.
3. Untuk aksi berisiko, panggil `/approvals/request`.
4. Poll `/approvals/{approval_id}` setiap 3-10 detik sampai final.
5. Jalankan aksi hanya jika status `approved`.
6. Jika status `rejected`, `expired`, atau timeout, hentikan aksi dan log alasannya.

## Recommended Client Mapping

| Modul | Source | Scope minimum | Use case |
| --- | --- | --- | --- |
| DMS | `dms` | `send:message`, `approval:request`, `approval:read`, `read:status` | Notifikasi dokumen, approval proses berisiko |
| Koperasi | `koperasi` | `send:message`, `read:status` | Notifikasi transaksi dan reminder |
| HRIS | `hris` | `send:message`, `approval:request`, `approval:read`, `read:status` | Notifikasi absensi/cuti dan approval aksi admin |
| Codex | `codex-local` | `approval:request`, `approval:read`, `read:status` | Approval sebelum command berisiko |

Best practice:

- Buat satu API client per modul, jangan share token antar modul.
- Gunakan scope minimum sesuai kebutuhan modul.
- Rotate token jika developer pindah role atau token pernah muncul di log.
- Jangan simpan token di repository; gunakan `.env`, secret manager, atau config lokal yang tidak di-commit.

## Retry Behavior

Untuk caller:

- `401`: token salah/expired; jangan retry otomatis, perbaiki secret.
- `403`: scope kurang; jangan retry otomatis, update scope client.
- `422`: payload invalid; jangan retry otomatis, perbaiki request.
- `429`: rate limit API client; retry setelah 60 detik.
- `5xx`: retry 2-3 kali dengan backoff 10, 30, 60 detik.

Untuk pengiriman WA:

- API mengembalikan `202` ketika pesan masuk queue.
- Queue worker akan menjalankan pengiriman sesuai slot rate limit 100 penerima per jam.
- Cek `/status` untuk melihat backlog queue dan koneksi WhatsApp.

## PHP Example

```php
$baseUrl = 'https://wa.kurmigo.id/api/v1';
$token = getenv('WA_GATEWAY_TOKEN');

$response = Http::withToken($token)
    ->acceptJson()
    ->post($baseUrl . '/messages/send', [
        'to' => '628123456789',
        'message' => 'Halo dari modul Kurmigo',
    ]);

if (!$response->successful()) {
    report('WA Gateway failed: ' . $response->body());
}
```

## Approval Helper Dari Laptop

File helper lokal:

```text
work/codex-wa-approval.cmd
work/codex-wa-approval.ps1
work/codex-wa-approval.config.example.json
```

Contoh:

```powershell
.\codex-wa-approval.cmd -Action "Deploy perubahan production" -Risk high -ExpiresInMinutes 15
```

Exit code helper:

| Code | Arti |
| --- | --- |
| `0` | Approved / dry-run OK |
| `20` | Rejected |
| `21` | Expired |
| `22` | Timeout |
| `30` | API error |
| `40` | Config/input error |
