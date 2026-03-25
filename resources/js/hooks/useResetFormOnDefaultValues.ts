import { useEffect } from 'react';

type UseResetFormOnDefaultValuesOptions = {
    enabled?: boolean;
};

export const useResetFormOnDefaultValues = <TDefaultValues,>(
    form: { reset: (values: TDefaultValues) => void },
    defaultValues: TDefaultValues,
    options?: UseResetFormOnDefaultValuesOptions,
) => {
    useEffect(() => {
        if (options?.enabled === false) {
            return;
        }

        form.reset(defaultValues);
    }, [form, defaultValues, options?.enabled]);
};
