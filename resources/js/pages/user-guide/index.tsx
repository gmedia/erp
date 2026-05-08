'use client';

import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { BookOpen, Loader2 } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Helmet } from 'react-helmet-async';
import Markdown from 'react-markdown';
import remarkGfm from 'remark-gfm';

interface GuideItem {
    slug: string;
    title: string;
}

interface GuideContent {
    slug: string;
    title: string;
    content: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'User Guide', href: '/user-guide' },
];

async function fetchWithAuth(url: string) {
    const apiToken = localStorage.getItem('api_token') || '';
    return fetch(url, {
        headers: {
            Authorization: `Bearer ${apiToken}`,
            'X-Requested-With': 'XMLHttpRequest',
        },
    });
}

export default function UserGuidePage() {
    const [guides, setGuides] = useState<GuideItem[]>([]);
    const [activeSlug, setActiveSlug] = useState<string | null>(null);
    const [content, setContent] = useState<GuideContent | null>(null);
    const [loading, setLoading] = useState(true);
    const [contentLoading, setContentLoading] = useState(false);

    useEffect(() => {
        fetchWithAuth('/api/user-guide')
            .then((r) => r.json())
            .then((json) => {
                const data: GuideItem[] = json.data || [];
                setGuides(data);
                if (data.length > 0) {
                    setActiveSlug(data[0].slug);
                }
                setLoading(false);
            });
    }, []);

    useEffect(() => {
        if (!activeSlug) return;
        setContentLoading(true);
        fetchWithAuth(`/api/user-guide/${activeSlug}`)
            .then((r) => r.json())
            .then((json) => {
                setContent(json.data || null);
                setContentLoading(false);
            });
    }, [activeSlug]);

    if (loading) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <div className="flex h-[60vh] items-center justify-center">
                    <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Helmet>
                <title>User Guide</title>
            </Helmet>

            <div className="px-4 py-6">
                <Heading
                    title="User Guide"
                    description="Panduan penggunaan aplikasi ERP"
                />

                <div className="flex flex-col lg:flex-row lg:space-x-12">
                    <aside className="w-full max-w-xl lg:w-56">
                        <nav className="flex flex-col space-y-1">
                            {guides.map((guide) => (
                                <Button
                                    key={guide.slug}
                                    size="sm"
                                    variant="ghost"
                                    className={cn(
                                        'w-full justify-start gap-2',
                                        {
                                            'bg-muted':
                                                activeSlug === guide.slug,
                                        },
                                    )}
                                    onClick={() => setActiveSlug(guide.slug)}
                                >
                                    <BookOpen className="h-4 w-4 shrink-0" />
                                    <span className="truncate text-left">
                                        {guide.title.replace(
                                            /^(User Guide:|Panduan Pengguna:)\s*/i,
                                            '',
                                        )}
                                    </span>
                                </Button>
                            ))}
                        </nav>
                    </aside>

                    <Separator className="my-6 lg:hidden" />

                    <div className="min-w-0 flex-1">
                        {contentLoading ? (
                            <div className="flex items-center justify-center py-12">
                                <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />
                            </div>
                        ) : content ? (
                            <article className="prose prose-neutral dark:prose-invert prose-headings:scroll-mt-20 prose-h1:text-2xl prose-h2:border-b prose-h2:pb-2 prose-h2:text-lg prose-h3:text-base prose-table:text-sm prose-th:bg-muted/50 prose-th:px-3 prose-th:py-2 prose-td:border prose-td:px-3 prose-td:py-2 prose-code:rounded prose-code:bg-muted prose-code:px-1.5 prose-code:py-0.5 prose-code:text-sm prose-pre:bg-muted max-w-none">
                                <Markdown remarkPlugins={[remarkGfm]}>
                                    {content.content}
                                </Markdown>
                            </article>
                        ) : (
                            <div className="flex flex-col items-center justify-center py-12 text-muted-foreground">
                                <BookOpen className="mb-4 h-12 w-12" />
                                <p>Pilih panduan dari menu di samping</p>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
