# Refactor Backend - Commit Documentation

## Commit Details

| Field | Value |
|-------|-------|
| **Hash** | `c3b482f779272b03cc4549a8ed35007f8d6aadc8` |
| **Author** | oppytut <11549752+oppytut@users.noreply.github.com> |
| **Date** | 2026-01-19 09:35:22 |
| **Subject** | refactor: migrate employee department and position fields to foreign keys for better data integrity and relationships. |

---

## Summary

Migrasi field `department` dan `position` pada Employee dari string menjadi foreign key (`department_id` dan `position_id`) untuk meningkatkan integritas data dan relasi antar tabel.

---

## Files Changed (20 files)

| File | Changes |
|------|---------|
| `IndexEmployeesAction.php` | 12 lines |
| `EmployeeCreateCommand.php` | 33 lines |
| `EmployeeFilterService.php` | 12 lines |
| `EmployeeExport.php` | 16 lines |
| `ExportEmployeeRequest.php` | 6 lines |
| `StoreEmployeeRequest.php` | 25 lines |
| `UpdateEmployeeRequest.php` | 25 lines |
| `EmployeeResource.php` | 8 lines |
| `EmployeeFactory.php` | 8 lines |
| `refactor.md` | 125 lines |
| `EmployeeControllerTest.php` | 72 lines |
| `EmployeeCreateCommandTest.php` | 12 lines |
| `EmployeeExportTest.php` | 66 lines |
| `IndexEmployeesActionTest.php` | 16 lines |
| `EmployeeFilterServiceTest.php` | 33 lines |
| `EmployeeTest.php` | 8 lines |
| `StoreEmployeeRequestTest.php` | 8 lines |
| `UpdateEmployeeRequestTest.php` | 51 lines |
| `ExportEmployeeRequestTest.php` | 6 lines |
| `EmployeeResourceTest.php` | 10 lines |

**Total**: 407 insertions(+), 145 deletions(-)
