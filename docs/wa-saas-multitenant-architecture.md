# WA System SaaS Multi-Tenant Architecture

Dokumen ini menjadi blueprint migrasi WA System dari single-tenant/single operational WA account menjadi module/service WhatsApp multi-tenant yang dikontrol dari central SaaS Usaha-Up.

## Tujuan

- Central SaaS tetap berada di project Usaha-Up.
- WA System menjadi module/service WhatsApp yang mengikuti tenant dan module entitlement dari Usaha-Up.
- Satu tenant Usaha-Up bisa punya satu atau banyak nomor WhatsApp di WA System.
- Nomor WA production yang sekarang sudah connected tetap menjadi default WA account dan tidak dipaksa scan QR ulang.
- Semua data operasional terisolasi berdasarkan `tenant_id` dan `wa_account_id`.
- Implementasi dilakukan bertahap agar risiko downtime kecil.

## Keputusan Arsitektur

### Model Deployment

Gunakan:

```text
Usaha-Up = central SaaS/source of truth
WA System = remote module/service WhatsApp
Central tenant identity = tenants di Usaha-Up
WA tenant mapping = tenant_id / central_tenant_id di WA System
Multi nomor WhatsApp = wa_accounts di WA System
Node sender = multi-session gateway
```

Jangan membuat central tenant baru yang terpisah di WA System. Tenant, subscription, module access, dan provisioning harus mengacu ke Usaha-Up.

Database WA System tetap boleh satu database operasional untuk semua tenant pada tahap awal, dengan isolasi ketat di aplikasi. Database per tenant bisa dipertimbangkan nanti untuk enterprise/compliance.

## Central Usaha-Up Integration

Project central:

```text
/var/www/kurmigo-usahaup
```

Komponen central yang sudah tersedia dan harus menjadi acuan:

```text
app/Models/Central/Tenant.php
app/Models/Central/TenantUser.php
app/Models/Central/TenantModule.php
app/Models/Central/Module.php
app/Models/Central/Subscription.php
app/Services/Central/TenantModuleProvisioner.php
app/Services/Tenant/RemoteModuleLaunchSigner.php
app/Http/Middleware/ResolveTenant.php
```

Implikasi:

- Usaha-Up membuat dan mengelola tenant.
- Usaha-Up mengaktifkan module WA System untuk tenant.
- Usaha-Up meluncurkan WA System dengan signed launch/context.
- WA System menyimpan mapping tenant dari Usaha-Up, bukan menjadi master tenant sendiri.
- API token WA System harus terkait tenant/module yang diberi akses oleh Usaha-Up.

### Strategi URL Tenant

Tahap awal:

```text
https://wa.kurmigo.id
```

Tenant context ditentukan dari signed launch/module context dari Usaha-Up atau user/module session yang dibuat oleh launch tersebut.

Tahap berikutnya, jika perlu branding tenant:

```text
https://wa.kurmigo.id/t/{tenant_slug}
```

Tahap advanced:

```text
https://{tenant_slug}.wa.kurmigo.id
```

Rekomendasi eksekusi: mulai dari Usaha-Up signed launch context. Path tenant/subdomain hanya dipakai jika memang dibutuhkan untuk direct access/branding setelah module context stabil.

## Entity Target

### tenant_mappings atau tenants

WA System boleh punya tabel lokal untuk cache/mapping tenant Usaha-Up. Tabel ini bukan source of truth central.

```text
id
central_tenant_id
central_tenant_uuid
name_snapshot
slug_snapshot
status
plan_snapshot
timezone
settings
synced_at
created_at
updated_at
```

### tenant_users atau module_users

```text
id
tenant_id
central_tenant_user_id
user_id
role
is_default
created_at
updated_at
```

Role awal di WA System mengikuti role/permission dari Usaha-Up, lalu dipetakan ke:

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

Saat migrasi pertama, buat atau mapping tenant default dari Usaha-Up:

```text
name: Kurmigo Internal
slug: kurmigo-internal
status: active
plan: internal
```

Semua data existing WA System dibackfill ke mapping tenant default ini.

Nomor WA yang sekarang connected dibuat sebagai WA account default:

```text
tenant: Kurmigo Internal dari Usaha-Up
label: Main WhatsApp
session_id: current-active-session
status: connected
is_default: true
is_active: true
rate_limit_per_hour: 100
```

## Migration Sequence Aman

### Phase 1: Schema Additive

1. Pastikan module WA System terdaftar di Usaha-Up.
2. Buat tabel mapping tenant lokal di WA System bila belum ada.
3. Buat tabel user/module access lokal bila WA System butuh cache akses.
4. Buat tabel `wa_accounts`.
5. Tambahkan nullable `tenant_id` ke tabel operasional.
6. Tambahkan nullable `wa_account_id` ke tabel operasional yang terkait nomor WA.
7. Tambahkan index, tetapi jangan aktifkan constraint ketat dulu.

Alasan nullable dulu: migration bisa deploy tanpa memecahkan query lama.

### Phase 2: Default Backfill

1. Ambil tenant default dari Usaha-Up atau buat tenant Kurmigo Internal di Usaha-Up jika belum ada.
2. Insert mapping tenant default di WA System.
3. Insert wa_account default.
4. Backfill semua data existing ke `tenant_id` default.
5. Backfill semua data kirim/inbox/approval/schedule ke `wa_account_id` default.
6. Jalankan audit:
   ```bash
   php8.2 artisan wa:smoke-deploy --json
   ```
7. Pastikan nomor existing masih connected:
   ```bash
   php8.2 artisan wa:health-check --json --no-alert
   ```

### Phase 3: Tenant Context

1. Tambahkan middleware `SetTenantContext`.
2. Web request mengambil tenant dari signed launch/session Usaha-Up.
3. API request mengambil tenant dari `api_clients.tenant_id`.
4. Direct login WA System hanya boleh untuk operator/internal yang sudah punya tenant mapping.
5. Semua controller mulai filter data berdasarkan tenant context.

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
| API client belum punya tenant | Token bisa akses data global | Wajib backfill `api_clients.tenant_id` dari mapping Usaha-Up |
| Central Usaha-Up dan WA mapping tidak sinkron | Tenant salah akses module | Signed launch + sync/audit tenant mapping |
| Job lama tidak punya tenant | Worker salah konteks | Graceful fallback ke tenant default hanya untuk job legacy |
| QR session salah tenant | Tenant bisa scan/memakai nomor tenant lain | Endpoint QR wajib authorize tenant + wa_account owner |

## Smoke Test Wajib SaaS

Tambahkan command pada `[AH]`:

```bash
php8.2 artisan wa:saas-smoke --json
```

Minimal check:

- tenant default dari Usaha-Up ada dan tersinkron ke WA System
- wa_account default ada
- nomor existing health connected
- signed launch/context dari Usaha-Up tervalidasi
- tenant A tidak bisa baca tenant B
- API token tenant A tidak bisa send dari wa_account tenant B
- dummy send tetap `Queue::fake()` dan tidak kirim WA live
- migration rollback path terdokumentasi

## Acceptance Wajib Setiap Deploy SaaS

Sebelum deploy dianggap aman:

1. `php8.2 artisan wa:smoke-deploy --json` hasil `ok=true`.
2. `php8.2 artisan wa:health-check --json --no-alert` hasil `whatsapp.connected=true`.
3. Data existing masih muncul di tenant default yang berasal dari Usaha-Up.
4. Nomor WA existing tidak force reset.
5. Tidak ada folder session WhatsApp yang dihapus/rename.
6. Kanban diupdate dengan commit hash dan hasil smoke.

## Urutan Backlog

Eksekusi rekomendasi:

1. `[AB] Tenant Foundation & Default Tenant Migration` dengan Usaha-Up sebagai source of truth
2. `[AC] WA Account Model & Existing Session Mapping`
3. `[AF] Tenant Data Isolation & Permission Hardening`
4. `[AD] Multi-Session Node Gateway`
5. `[AE] Tenant & WA Account Management UI`
6. `[AG] SaaS Plan Limits & Rate Policy Per WA Account`
7. `[AH] SaaS Smoke Tests & Rollout Checklist`

Urutan ini menjaga nomor existing tetap aman sebelum multi-session dibuka untuk tenant baru.
