import { Button } from '@/components/ui/button';
import { Zap } from 'lucide-react';
import { TreeView } from '@/components/tree/tree-view';
import { Permission } from '@/types/permission';

interface PermissionManagerProps {
    permissions: Permission[];
    selectedPermissions: number[];
    onSelectionChange: (ids: number[]) => void;
    onSave: () => void;
    loading: boolean;
}

export function PermissionManager({ 
    permissions, 
    selectedPermissions, 
    onSelectionChange, 
    onSave, 
    loading 
}: PermissionManagerProps) {
    return (
        <div className="space-y-4">
             <div className="flex items-center justify-between">
                <h3 className="text-lg font-medium">Permissions</h3>
                <Button onClick={onSave} disabled={loading} data-testid="save-permissions-btn">
                    {loading && <Zap className="mr-2 h-4 w-4 animate-spin" />}
                    Save Changes
                </Button>
            </div>
            <div className="border rounded-md p-4">
                <TreeView 
                    data={permissions} 
                    selectedIds={selectedPermissions} 
                    onSelectionChange={onSelectionChange}
                />
            </div>
        </div>
    );
}
