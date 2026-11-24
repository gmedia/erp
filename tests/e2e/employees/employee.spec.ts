import { generateModuleTests } from '../shared-test-factories';
import { createEmployee, editEmployee, searchEmployee } from './helpers';

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
    sortableColumns: ['Name', 'Email', 'Department', 'Salary'],

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
            filterValue: 'Teknik',
            expectedText: 'Teknik',
        },
        {
            filterName: 'Position',
            filterType: 'combobox',
            filterValue: 'Pengembang Senior',
            expectedText: 'Pengembang Senior',
        },
        {
            filterName: 'Branch',
            filterType: 'combobox',
            filterValue: 'Kantor Pusat',
            expectedText: 'Kantor Pusat',
        },
    ],
});
