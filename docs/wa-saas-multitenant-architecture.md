# WA System SaaS Multi-Tenant Architecture

Dokumen ini menjadi blueprint migrasi WA System dari single-tenant/single operational WA account menjadi SaaS multi-tenant dan multi nomor WhatsApp.

## Tujuan

- Satu aplikasi dan satu database bisa melayani banyak tenant.
- Satu tenant bisa punya satu atau banyak nomor WhatsApp.
- Nomor WA production yang sekarang sudah connected tetap menjadi default WA account dan tidak dipaksa scan QR ulang.
- Semua data operasional terisolasi berdasarkan `tenant_id` dan `wa_account_id`.
- Implementasi dilakukan bertahap agar risiko downtime kecil.

## Keputusan Arsitektur

### Model Deployment

Gunakan:

```text
Single Laravel app
Single primary database
Multi tenant dengan tenant_id
Multi nomor WhatsApp dengan wa_account_id
Node sender multi-session
```

Jangan langsung membuat database baru per tenant. Database per tenant bisa dipertimbangkan nanti untuk enterprise/compliance, tetapi tahap awal SaaS lebih aman dengan satu database dan isolasi ketat di aplikasi.

### Strategi URL Tenant

Tahap awal:

```text
https://wa.kurmigo.id
```

Tenant context ditentukan dari user login dan tenant switcher.

Tahap berikutnya, jika perlu branding tenant:

```text
https://wa.kurmigo.id/t/{tenant_slug}
```

Tahap advanced:

```text
https://{tenant_slug}.wa.kurmigo.id
```

Rekomendasi eksekusi: mulai dari login-based tenant context, lalu tambah path tenant setelah data model stabil.

## Entity Target

### tenants

```text
id
name
slug
status
plan
timezone
settings
created_at
updated_at
```

### tenant_users

```text
id
tenant_id
user_id
role
is_default
created_at
updated_at
```

Role awal:

- `owner`
- `admin`
- `operator`
- `viewer`

### wa_accounts

```text
id
tenant_id
label
phone_number
session_id
session_path
status
last_connected_at
last_disconnected_at
rate_limit_per_hour
is_default
is_active
metadata
created_at
updated_at
```

Catatan penting:

- `session_id` harus stabil.
- `session_path` untuk nomor existing harus mengarah ke session yang sudah ada.
- Jangan rename atau hapus folder session existing saat migrasi.

## Current Production Audit

Folder session WhatsApp existing ditemukan di:

```text
wa-sender/.wwebjs_auth/session-wa-blast-client-*
```

Karena ada beberapa folder session historis, migrasi `[AC]` harus mengidentifikasi session mana yang sedang aktif sebelum menetapkan `wa_accounts.session_id`.

PM2 production saat audit:

```text
wa-blast  online
wa-queue  online
```

Health production saat audit:

```text
node.running=true
whatsapp.connected=true
queue.pending_jobs=0
queue.failed_jobs=0
```

## Tabel Yang Perlu Tenant Scope

| Tabel | tenant_id | wa_account_id | Catatan |
| --- | --- | --- | --- |
| users | tidak langsung | tidak | Relasi tenant via `tenant_users` |
| contact_groups | wajib | opsional | Grup kontak milik tenant |
| contacts | wajib | opsional | Ikut tenant, group tetap dalam tenant sama |
| wa_logs | wajib | wajib | Riwayat kirim harus tahu tenant dan nomor pengirim |
| wa_schedules | wajib | wajib | Campaign/schedule berjalan per nomor WA |
| wa_inbox | wajib | wajib | Incoming message harus tahu nomor penerima tenant |
| follow_up_reminders | wajib | opsional | Turunan inbox/lead |
| api_clients | wajib | opsional | Token milik tenant, scope bisa dibatasi wa_account |
| api_message_requests | wajib | wajib | Request API harus tahu tenant dan nomor WA |
| approval_requests | wajib | wajib | Approval dikirim dari nomor WA tertentu |
| jobs | lewat payload | lewat payload | Job payload harus membawa tenant_id dan wa_account_id |
| failed_jobs | lewat payload | lewat payload | Untuk audit/troubleshooting |

## Tenant Default

Saat migrasi pertama, buat tenant default:

```text
name: Kurmigo Internal
slug: kurmigo-internal
status: active
plan: internal
```

Semua data existing dibackfill ke tenant ini.

Nomor WA yang sekarang connected dibuat sebagai WA account default:

```text
tenant: Kurmigo Internal
label: Main WhatsApp
session_id: current-active-session
status: connected
is_default: true
is_active: true
rate_limit_per_hour: 100
```

## Migration Sequence Aman

### Phase 1: Schema Additive

1. Buat tabel `tenants`.
2. Buat tabel `tenant_users`.
3. Buat tabel `wa_accounts`.
4. Tambahkan nullable `tenant_id` ke tabel operasional.
5. Tambahkan nullable `wa_account_id` ke tabel operasional yang terkait nomor WA.
6. Tambahkan index, tetapi jangan aktifkan constraint ketat dulu.

Alasan nullable dulu: migration bisa deploy tanpa memecahkan query lama.

### Phase 2: Default Backfill

1. Insert tenant default.
2. Insert wa_account default.
3. Backfill semua data existing ke `tenant_id` default.
4. Backfill semua data kirim/inbox/approval/schedule ke `wa_account_id` default.
5. Jalankan audit:
   ```bash
   php8.2 artisan wa:smoke-deploy --json
   ```
6. Pastikan nomor existing masih connected:
   ```bash
   php8.2 artisan wa:health-check --json --no-alert
   ```

### Phase 3: Tenant Context

1. Tambahkan middleware `SetTenantContext`.
2. Web request mengambil tenant dari:
   - tenant default user
   - tenant switcher session
   - path `/t/{tenant_slug}` jika sudah aktif
3. API request mengambil tenant dari `api_clients.tenant_id`.
4. Semua controller mulai filter data berdasarkan tenant context.

### Phase 4: WA Account Context

1. Compose, schedule, API send, approval request wajib memilih atau resolve `wa_account_id`.
2. Jika tidak ada `wa_account_id`, fallback ke default wa_account milik tenant.
3. Job queue membawa `tenant_id` dan `wa_account_id`.
4. Rate limiter dipisah per `wa_account_id`.

### Phase 5: Constraint Tightening

Setelah audit data bersih:

1. Ubah `tenant_id` menjadi not nullable pada tabel wajib.
2. Ubah `wa_account_id` menjadi not nullable pada tabel operasional WA.
3. Tambahkan foreign key jika aman.
4. Tambahkan unique constraint per tenant, misalnya:
   - `tenants.slug`
   - `api_clients(tenant_id, slug)`
   - `contact_groups(tenant_id, name)`

### Phase 6: Multi-Session Node

1. Node sender menerima `wa_account_id` atau `session_id`.
2. Endpoint status bisa menampilkan status semua session.
3. Endpoint QR hanya untuk session milik tenant terkait.
4. Send message memakai session sesuai `wa_account_id`.
5. Session existing tetap dipertahankan.

## Boundary Data

### Web UI

Semua query list/detail harus filter:

```text
where tenant_id = currentTenant.id
```

Untuk data spesifik nomor:

```text
where tenant_id = currentTenant.id
where wa_account_id in currentTenant.wa_accounts
```

### API

`ApiClientAuth` harus:

1. Validasi token aktif.
2. Attach `api_client`.
3. Attach `tenant`.
4. Batasi `wa_account_id` agar hanya milik tenant token tersebut.

### Queue

Job WA harus membawa:

```text
tenant_id
wa_account_id
api_message_request_id
wa_schedule_id
```

Worker tidak boleh resolve tenant dari global/default saat memproses job lama.

## Risiko dan Mitigasi

| Risiko | Dampak | Mitigasi |
| --- | --- | --- |
| Session existing kehapus/rename | Nomor harus scan QR ulang | Jangan ubah `wa-sender/.wwebjs_auth`; map session existing dulu |
| Query lupa filter tenant | Data bocor antar tenant | Tenant scope, code audit, smoke permission |
| Rate limit masih global | Tenant saling menghambat | Cache key rate limiter pakai `wa_account_id` |
| API client belum punya tenant | Token bisa akses data global | Wajib backfill `api_clients.tenant_id` |
| Job lama tidak punya tenant | Worker salah konteks | Graceful fallback ke tenant default hanya untuk job legacy |
| QR session salah tenant | Tenant bisa scan/memakai nomor tenant lain | Endpoint QR wajib authorize tenant + wa_account owner |

## Smoke Test Wajib SaaS

Tambahkan command pada `[AH]`:

```bash
php8.2 artisan wa:saas-smoke --json
```

Minimal check:

- tenant default ada
- wa_account default ada
- nomor existing health connected
- tenant A tidak bisa baca tenant B
- API token tenant A tidak bisa send dari wa_account tenant B
- dummy send tetap `Queue::fake()` dan tidak kirim WA live
- migration rollback path terdokumentasi

## Acceptance Wajib Setiap Deploy SaaS

Sebelum deploy dianggap aman:

1. `php8.2 artisan wa:smoke-deploy --json` hasil `ok=true`.
2. `php8.2 artisan wa:health-check --json --no-alert` hasil `whatsapp.connected=true`.
3. Data existing masih muncul di tenant default.
4. Nomor WA existing tidak force reset.
5. Tidak ada folder session WhatsApp yang dihapus/rename.
6. Kanban diupdate dengan commit hash dan hasil smoke.

## Urutan Backlog

Eksekusi rekomendasi:

1. `[AB] Tenant Foundation & Default Tenant Migration`
2. `[AC] WA Account Model & Existing Session Mapping`
3. `[AF] Tenant Data Isolation & Permission Hardening`
4. `[AD] Multi-Session Node Gateway`
5. `[AE] Tenant & WA Account Management UI`
6. `[AG] SaaS Plan Limits & Rate Policy Per WA Account`
7. `[AH] SaaS Smoke Tests & Rollout Checklist`

Urutan ini menjaga nomor existing tetap aman sebelum multi-session dibuka untuk tenant baru.
