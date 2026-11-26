import { useToastTranslator } from '@/utils/i18nFallback';

/**
 * Teks UI halaman Vehicle Loans. Kunci dibaca dari `vehicle_loan_page.*`
 * (lang/en.json + lang/id.json); fallback = teks Indonesia sumber.
 */
export function useVehicleLoanPageText() {
    return useToastTranslator('vehicle_loan_page');
}
