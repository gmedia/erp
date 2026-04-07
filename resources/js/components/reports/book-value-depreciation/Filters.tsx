import {
    createAssetReportScopeFilterFields,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createBookValueReportFilterFields(): FieldDescriptor[] {
    return createAssetReportScopeFilterFields();
}
