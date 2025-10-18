/**
 * Centralised map of export API endpoints for the three entities.
 * Used by DataTable components to build the export URL.
 */
export const EXPORT_ENDPOINTS = {
  employee: '/api/employees/export',
  position: '/api/positions/export',
  department: '/api/departments/export',
} as const;

export type ExportEntity = keyof typeof EXPORT_ENDPOINTS;
