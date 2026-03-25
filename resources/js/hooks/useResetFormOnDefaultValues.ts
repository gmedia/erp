import { useEffect } from 'react';

export const useResetFormOnDefaultValues = <TDefaultValues,>(
    form: { reset: (values: TDefaultValues) => void },
    defaultValues: TDefaultValues,
) => {
    useEffect(() => {
        form.reset(defaultValues);
    }, [form, defaultValues]);
};
