import axios from '@/lib/axios';
import { toast } from 'sonner';

type SubmitOptions = {
    url: string;
    body: FormData;
    setLoading: (loading: boolean) => void;
    successMessage: string;
    failureMessage: string;
    onSuccess: () => void;
};

/**
 * Kirim FormData multipart untuk halaman Vehicle Loan (pickup/return) dengan
 * pola loading, toast sukses, dan toast gagal yang seragam.
 */
export async function submitVehicleLoanFormData({
    url,
    body,
    setLoading,
    successMessage,
    failureMessage,
    onSuccess,
}: SubmitOptions): Promise<void> {
    setLoading(true);
    try {
        await axios.post(url, body);
        toast.success(successMessage);
        onSuccess();
    } catch {
        toast.error(failureMessage);
    } finally {
        setLoading(false);
    }
}
