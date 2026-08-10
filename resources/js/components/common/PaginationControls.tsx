'use client';

import {
    Pagination,
    PaginationContent,
    PaginationItem,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import React from 'react';

export function PaginationControls({
    pagination,
    onPageChange,
    onPageSizeChange,
    renderPageNumbers,
}: Readonly<{
    pagination: {
        page: number;
        per_page: number;
        total: number;
        last_page: number;
        from: number;
        to: number;
    };
    onPageChange: (page: Readonly<number>) => void;
    onPageSizeChange: (per_page: string) => void;
    renderPageNumbers: () => React.ReactNode;
}>) {
    return (
        <div className="flex flex-col gap-2 py-2 text-xs text-muted-foreground sm:flex-row sm:items-center sm:justify-between sm:text-sm">
            <div className="flex flex-wrap items-center gap-x-3 gap-y-1.5">
                <p className="whitespace-nowrap">Rows per page</p>
                <Select
                    value={String(pagination.per_page)}
                    onValueChange={onPageSizeChange}
                >
                    <SelectTrigger className="h-8 w-[70px] border-border bg-background">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent className="border-border bg-background text-foreground">
                        <SelectItem value="10">10</SelectItem>
                        <SelectItem value="15">15</SelectItem>
                        <SelectItem value="25">25</SelectItem>
                        <SelectItem value="50">50</SelectItem>
                        <SelectItem value="100">100</SelectItem>
                    </SelectContent>
                </Select>
                <p className="text-foreground/80">
                    Showing {pagination.from} to {pagination.to} of{' '}
                    {pagination.total} entries
                </p>
            </div>

            <Pagination className="mx-0 w-auto justify-end">
                <PaginationContent>
                    <PaginationItem>
                        <PaginationPrevious
                            href="#"
                            onClick={(e) => {
                                e.preventDefault();
                                if (pagination.page > 1) {
                                    onPageChange(pagination.page - 1);
                                }
                            }}
                            aria-disabled={pagination.page <= 1}
                            className={
                                pagination.page <= 1
                                    ? 'pointer-events-none opacity-50'
                                    : ''
                            }
                        />
                    </PaginationItem>

                    {renderPageNumbers()}

                    <PaginationItem>
                        <PaginationNext
                            href="#"
                            onClick={(e) => {
                                e.preventDefault();
                                if (pagination.page < pagination.last_page) {
                                    onPageChange(pagination.page + 1);
                                }
                            }}
                            aria-disabled={
                                pagination.page >= pagination.last_page
                            }
                            className={
                                pagination.page >= pagination.last_page
                                    ? 'pointer-events-none opacity-50'
                                    : ''
                            }
                        />
                    </PaginationItem>
                </PaginationContent>
            </Pagination>
        </div>
    );
}
