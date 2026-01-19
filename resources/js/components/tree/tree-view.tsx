import React, { useMemo } from 'react';
import { TreeNode, TreeNodeData } from './tree-node';

interface TreeViewProps {
    data: any[]; // Raw flat data from backend
    selectedIds: number[];
    onSelectionChange: (ids: number[]) => void;
}

export function TreeView({ data, selectedIds, onSelectionChange }: TreeViewProps) {
    const treeData = useMemo(() => {
        const buildTree = (items: any[], parentId: number | null = null): TreeNodeData[] => {
            return items
                .filter(item => item.parent_id === parentId)
                .map(item => ({
                    id: item.id,
                    name: item.display_name || item.name,
                    children: buildTree(items, item.id)
                }));
        };
        return buildTree(data);
    }, [data]);

    const handleToggle = (id: number) => {
        let newSelectedIds = [...selectedIds];
        if (newSelectedIds.includes(id)) {
            newSelectedIds = newSelectedIds.filter(selectedId => selectedId !== id);
        } else {
            newSelectedIds.push(id);
        }
        onSelectionChange(newSelectedIds);
    };

    return (
        <div className="border rounded-md p-4">
            {treeData.map(node => (
                <TreeNode
                    key={node.id}
                    node={node}
                    selectedIds={selectedIds}
                    onToggle={handleToggle}
                />
            ))}
            {treeData.length === 0 && (
                <div className="text-center text-muted-foreground p-4">
                    No permissions found.
                </div>
            )}
        </div>
    );
}
