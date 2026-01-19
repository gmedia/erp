import React from 'react';
import { Checkbox } from '@/components/ui/checkbox';
import { cn } from '@/lib/utils';
import { ChevronRight, ChevronDown } from 'lucide-react';
import { Button } from '@/components/ui/button';

export interface TreeNodeData {
    id: number;
    name: string;
    children?: TreeNodeData[];
}

interface TreeNodeProps {
    node: TreeNodeData;
    selectedIds: number[];
    onToggle: (id: number) => void;
    level?: number;
}

export function TreeNode({ node, selectedIds, onToggle, level = 0 }: TreeNodeProps) {
    const [isExpanded, setIsExpanded] = React.useState(true);
    const hasChildren = node.children && node.children.length > 0;
    const isSelected = selectedIds.includes(node.id);

    const handleExpandClick = (e: React.MouseEvent) => {
        e.stopPropagation();
        setIsExpanded(!isExpanded);
    };

    const handleCheckChange = (checked: boolean) => {
        onToggle(node.id);
    };

    return (
        <div className="select-none">
            <div
                className={cn(
                    "flex items-center py-1 px-2 hover:bg-muted/50 rounded-sm cursor-pointer",
                )}
                style={{ paddingLeft: `${level * 20 + 8}px` }}
                onClick={() => handleCheckChange(!isSelected)}
            >
                <div className="w-4 h-4 mr-2 flex items-center justify-center">
                    {hasChildren && (
                        <Button
                            variant="ghost"
                            size="icon"
                            className="h-4 w-4 p-0 hover:bg-transparent"
                            onClick={handleExpandClick}
                        >
                            {isExpanded ? (
                                <ChevronDown className="h-4 w-4 text-muted-foreground" />
                            ) : (
                                <ChevronRight className="h-4 w-4 text-muted-foreground" />
                            )}
                        </Button>
                    )}
                </div>
                
                <Checkbox
                    checked={isSelected}
                    onCheckedChange={handleCheckChange}
                    className="mr-2"
                    onClick={(e) => e.stopPropagation()}
                />
                
                <span className="text-sm font-medium">{node.name}</span>
            </div>

            {hasChildren && isExpanded && (
                <div>
                    {node.children!.map((child) => (
                        <TreeNode
                            key={child.id}
                            node={child}
                            selectedIds={selectedIds}
                            onToggle={onToggle}
                            level={level + 1}
                        />
                    ))}
                </div>
            )}
        </div>
    );
}
