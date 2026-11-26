import { formatDateTimeByRegionalSettings } from '@/utils/date-format';

import { useVehicleLoanPageText } from './useVehicleLoanPageText';

export interface VehicleLoanSummary {
    code: string;
    requester_name: string | null;
    driver_name: string | null;
    plate_number: string | null;
    purpose: string | null;
    start_at: string | null;
    end_at: string | null;
}

interface VehicleLoanSummaryCardProps {
    loan: VehicleLoanSummary;
}

/** Ringkasan peminjaman terpilih di halaman pickup & return. */
export function VehicleLoanSummaryCard({ loan }: VehicleLoanSummaryCardProps) {
    const tPage = useVehicleLoanPageText();

    return (
        <div className="space-y-1 rounded-lg border bg-muted/30 p-3 text-sm">
            <p className="font-medium">{loan.code}</p>
            <p>
                {tPage('detail.requester', 'Peminjam')}:{' '}
                {loan.requester_name ?? '-'}
            </p>
            <p>
                {tPage('detail.driver', 'Pengemudi')}: {loan.driver_name ?? '-'}
            </p>
            <p>
                {tPage('detail.vehicle', 'Kendaraan')}:{' '}
                {loan.plate_number ?? '-'}
            </p>
            <p>
                {tPage('detail.purpose', 'Tujuan')}: {loan.purpose ?? '-'}
            </p>
            <p>
                {tPage('detail.start', 'Mulai')}:{' '}
                {formatDateTimeByRegionalSettings(loan.start_at)}
            </p>
            <p>
                {tPage('detail.end', 'Selesai')}:{' '}
                {formatDateTimeByRegionalSettings(loan.end_at)}
            </p>
        </div>
    );
}
