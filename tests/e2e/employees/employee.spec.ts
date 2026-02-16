import { generateModuleTests } from '../shared-test-factories';
import { createEmployee, searchEmployee, editEmployee } from './helpers';

generateModuleTests({
    entityName: 'Employee',
    entityNamePlural: 'Employees',
    route: '/employees',
    apiPath: '/api/employees',

    // Callbacks
    createEntity: createEmployee,
    searchEntity: searchEmployee,
    editEntity: editEmployee,
    editUpdates: {
        name: 'Updated Employee Name',
        salary: '6000000',
    },

    // DataTable config
    sortableColumns: [
        'Name',
        'Email',
        'Phone',
        'Department',
        'Position',
        'Branch',
        'Salary',
        'Hire Date',
    ],

    // View config
    viewType: 'dialog',
    viewDialogTitle: 'View Employee',

    // Export config
    exportApiPath: '/api/employees/export',
    expectedExportColumns: [
        'ID',
        'Name',
        'Email',
        'Phone',
        'Department',
        'Position',
        'Branch',
        'Salary',
        'Hire Date',
        'Created At',
    ],

    // Filter config
    filterTests: [
        {
            filterName: 'Department',
            filterType: 'combobox',
            filterValue: 'Engineering',
            expectedText: 'Engineering',
        },
        {
            filterName: 'Position',
            filterType: 'combobox',
            filterValue: 'Senior Developer',
            expectedText: 'Senior Developer',
        },
        {
            filterName: 'Branch',
            filterType: 'combobox',
            filterValue: 'Head Office',
            expectedText: 'Head Office',
        },
    ],
});
