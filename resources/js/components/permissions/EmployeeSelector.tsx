import AsyncSelectField from '@/components/common/AsyncSelectField';
import { useFormContext } from 'react-hook-form';

export function EmployeeSelector() {
    useFormContext(); // Just to ensure context exists, though AsyncSelectField manages its own connection via name

    return (
        <AsyncSelectField
            name="employee_id"
            label="Select Employee"
            url="/api/employees"
            placeholder="Search for an employee..."
            labelFn={(item) => (typeof item.name === 'string' ? item.name : '')}
            valueFn={(item) => String(item.id)}
        />
    );
}
