'use client';

import { PaginationControls } from '@/components/common/PaginationControls';
import { PaginationItem, PaginationLink } from '@/components/ui/pagination';

interface DataTablePaginationProps {
    pagination: {
        page: number;
        per_page: number;
        total: number;
        last_page: number;
        from: number;
        to: number;
    };
    onPageChange: (page: number) => void;
    onPageSizeChange: (per_page: number) => void;
}

export function DataTablePagination({
    pagination,
    onPageChange,
    onPageSizeChange,
}: Readonly<DataTablePaginationProps>) {
    const renderPageNumbers = () => {
        const pages = [];
        const maxPages = 5;
        const startPage = Math.max(
            1,
            pagination.page - Math.floor(maxPages / 2),
        );
        const endPage = Math.min(
            pagination.last_page,
            startPage + maxPages - 1,
        );

        for (let i = startPage; i <= endPage; i++) {
            pages.push(
                <PaginationItem key={i}>
                    <PaginationLink
                        href="#"
                        isActive={i === pagination.page}
                        onClick={(e) => {
                            e.preventDefault();
                            onPageChange(i);
                        }}
                    >
                        {i}
                    </PaginationLink>
                </PaginationItem>,
            );
        }
        return pages;
    };

    return (
        <PaginationControls
            pagination={pagination}
            onPageChange={onPageChange}
            onPageSizeChange={(perPage) => onPageSizeChange(Number(perPage))}
            renderPageNumbers={renderPageNumbers}
        />
    );
}
