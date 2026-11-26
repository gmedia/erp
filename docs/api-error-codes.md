# Kode Error API (`error_code`)

Respons error domain menyertakan `error_code` yang stabil di samping `message`.

```json
{ "message": "Only draft budgets can be deleted.", "error_code": "budget.not_draft" }
```

- Format: `modul.alasan`, huruf kecil, snake_case (mis. `budget.not_draft`).
- `message` tetap berisi teks terlokalisasi (`X-Locale`); klien yang hanya membaca `message` tetap berfungsi.
- Kode ditambahkan secara aditif. Pesan sukses (`message` tanpa error) tidak memakai `error_code`.
- Status code tidak berubah dari sebelumnya.
- Sumber kode: `App\Exceptions\ApiErrorException` (dipanggil di controller).

## Daftar error_code (gelombang 1)

| error_code | HTTP | Endpoint | Pesan (en) |
|---|---|---|---|
| `budget.not_draft` | 422 | `DELETE /api/budgets/{budget}`, `POST /api/budgets/{budget}/approve` | Only draft budgets can be deleted / approved. |
| `budget.not_approved` | 422 | `POST /api/budgets/{budget}/lock` | Only approved budgets can be locked. |
| `account.has_child_accounts` | 422 | `DELETE /api/accounts/{account}` | Cannot delete account with child accounts. |
| `account.has_journal_entries` | 422 | `DELETE /api/accounts/{account}` | Cannot delete account with journal entries. |
| `journal_entry.posted_cannot_be_deleted` | 403 | `DELETE /api/journal-entries/{journal_entry}` | Posted journal entries cannot be deleted. |
| `entity_approval_history.entity_type_not_supported` | 400 | `GET /api/entity-states/{entityType}/{entityId}/approvals` | Entity type is not supported. |
| `user_guide.not_found` | 404 | `GET /api/user-guide/{slug}` | Guide not found. |
| `notification.not_found` | 404 | `POST /api/notifications/{notification}/read` | Notification not found. |
| `my_approval.not_authorized_for_step` | 403 | `POST /api/my-approvals/{approvalRequest}/approve`, `.../reject` | You are not authorized for this approval step. |
| `vehicle_loan.not_draft` | 422 | `PUT /api/vehicle-loans/{vehicle_loan}` | Only draft vehicle loans can be updated. |
| `vehicle_loan.not_cancellable` | 422 | `DELETE /api/vehicle-loans/{vehicle_loan}` | Only draft or rejected vehicle loans can be cancelled. |
| `vehicle_loan.approval_request_not_found` | 404 | `POST /api/vehicle-loans/{vehicle_loan}/review-assignment` | Approval request not found. |
| `vehicle_loan.only_requester_can_perform_action` | 403 | aksi pemohon pada loan/pickup (`VehicleLoanController`) | Only the requester can perform this action. |
| `entity_state.no_active_pipeline` | 404 | `GET /api/entity-states/{entityType}/{entityId}` | No active pipeline for this entity. |
| `entity_state.state_not_found` | 404 | `POST /api/entity-states/{entityType}/{entityId}/transition` | Entity state not found. |
| `entity_state.transition_forbidden` | 403 | `POST /api/entity-states/{entityType}/{entityId}/transition` | Transition not allowed for your role. |
| `entity_state.entity_not_found` | 404 | entity state endpoints | Entity not found. |
| `permission.denied` | 403 | rute `permission:...,true` (`CheckPermission`) dan `entity-state-permission` | You do not have permission to perform this action. |

Catatan: endpoint mengikuti `routes/api/*.php` (parameter route dalam `{}`); pesan en mengikuti `lang/en.json`.

## Menambah kode baru

1. Pakai `ApiErrorException::translated($status, '<modul>.<alasan>', '<modul>_api.<key>')`.
2. Tambah baris ke tabel di atas.
3. Test `tests/Feature/Locale/ApiErrorCodesDocsTest.php` memastikan setiap kode di tabel muncul di kode sumber.
