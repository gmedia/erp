'use client';

import { Button } from '@/components/ui/button';
import { ScrollArea } from '@/components/ui/scroll-area';
import { cn } from '@/lib/utils';
import { BookOpen, ChevronRight, Loader2 } from 'lucide-react';
import { useEffect, useState } from 'react';
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

function useApiToken() {
    return localStorage.getItem('api_token') || '';
}

async function fetchGuides(apiToken: string): Promise<GuideItem[]> {
    const response = await fetch('/api/user-guide', {
        headers: {
            Authorization: `Bearer ${apiToken}`,
            'X-Requested-With': 'XMLHttpRequest',
        },
    });
    const json = await response.json();
    return json.data || [];
}

async function fetchGuideContent(
    slug: string,
    apiToken: string,
): Promise<GuideContent | null> {
    const response = await fetch(`/api/user-guide/${slug}`, {
        headers: {
            Authorization: `Bearer ${apiToken}`,
            'X-Requested-With': 'XMLHttpRequest',
        },
    });
    if (!response.ok) return null;
    const json = await response.json();
    return json.data || null;
}

export default function UserGuidePage() {
    const apiToken = useApiToken();
    const [guides, setGuides] = useState<GuideItem[]>([]);
    const [activeSlug, setActiveSlug] = useState<string | null>(null);
    const [content, setContent] = useState<GuideContent | null>(null);
    const [loading, setLoading] = useState(true);
    const [contentLoading, setContentLoading] = useState(false);

    useEffect(() => {
        if (!apiToken) return;
        fetchGuides(apiToken).then((data) => {
            setGuides(data);
            if (data.length > 0) {
                setActiveSlug(data[0].slug);
            }
            setLoading(false);
        });
    }, [apiToken]);

    useEffect(() => {
        if (!activeSlug || !apiToken) return;
        setContentLoading(true);
        fetchGuideContent(activeSlug, apiToken).then((data) => {
            setContent(data);
            setContentLoading(false);
        });
    }, [activeSlug, apiToken]);

    if (loading) {
        return (
            <div className="flex h-[80vh] items-center justify-center">
                <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
            </div>
        );
    }

    return (
        <div className="flex h-[calc(100vh-4rem)] flex-col">
            <div className="border-b px-6 py-4">
                <div className="flex items-center gap-3">
                    <BookOpen className="h-6 w-6 text-primary" />
                    <div>
                        <h1 className="text-2xl font-bold">User Guide</h1>
                        <p className="text-sm text-muted-foreground">
                            Panduan penggunaan aplikasi ERP
                        </p>
                    </div>
                </div>
            </div>

            <div className="flex min-h-0 flex-1">
                <aside className="w-64 shrink-0 border-r bg-muted/30">
                    <ScrollArea className="h-full">
                        <nav className="space-y-1 p-4">
                            {guides.map((guide) => (
                                <Button
                                    key={guide.slug}
                                    variant={
                                        activeSlug === guide.slug
                                            ? 'secondary'
                                            : 'ghost'
                                    }
                                    className={cn(
                                        'w-full justify-start gap-2 text-left',
                                        activeSlug === guide.slug &&
                                            'font-medium',
                                    )}
                                    onClick={() => setActiveSlug(guide.slug)}
                                >
                                    <ChevronRight
                                        className={cn(
                                            'h-4 w-4 shrink-0 transition-transform',
                                            activeSlug === guide.slug &&
                                                'rotate-90',
                                        )}
                                    />
                                    <span className="truncate">
                                        {guide.title}
                                    </span>
                                </Button>
                            ))}
                        </nav>
                    </ScrollArea>
                </aside>

                <main className="flex-1 overflow-hidden">
                    <ScrollArea className="h-full">
                        <div className="mx-auto max-w-4xl px-8 py-6">
                            {contentLoading ? (
                                <div className="flex items-center justify-center py-12">
                                    <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />
                                </div>
                            ) : content ? (
                                <article className="prose prose-neutral dark:prose-invert prose-headings:scroll-mt-20 prose-h1:text-3xl prose-h2:border-b prose-h2:pb-2 prose-h2:text-xl prose-h3:text-lg prose-table:text-sm prose-th:bg-muted/50 prose-th:px-3 prose-th:py-2 prose-td:px-3 prose-td:py-2 max-w-none">
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
                    </ScrollArea>
                </main>
            </div>
        </div>
    );
}
