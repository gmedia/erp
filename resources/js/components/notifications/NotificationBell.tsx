import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import axios from '@/lib/axios';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Bell } from 'lucide-react';
import { Link } from 'react-router-dom';

interface AppNotification {
    id: string;
    data: {
        title?: string;
        body?: string;
        target_url?: string;
    };
    read_at: string | null;
    created_at: string;
}

export function NotificationBell() {
    const queryClient = useQueryClient();

    const { data } = useQuery({
        queryKey: ['notifications', 'unread-count'],
        queryFn: async () => {
            const response = await axios.get<{ count: number }>(
                '/api/notifications/unread-count',
            );
            return response.data.count;
        },
        refetchInterval: 60_000,
    });

    const { data: inbox } = useQuery({
        queryKey: ['notifications', 'inbox'],
        queryFn: async () => {
            const response = await axios.get<{ data: AppNotification[] }>(
                '/api/notifications?unread_only=1&per_page=5',
            );
            return response.data.data;
        },
        enabled: false,
    });

    const readAll = useMutation({
        mutationFn: () => axios.post('/api/notifications/read-all'),
        onSuccess: () => {
            void queryClient.invalidateQueries({
                queryKey: ['notifications'],
            });
        },
    });

    const markRead = useMutation({
        mutationFn: (id: string) => axios.post(`/api/notifications/${id}/read`),
        onSuccess: () => {
            void queryClient.invalidateQueries({
                queryKey: ['notifications'],
            });
        },
    });

    const count = data ?? 0;

    return (
        <DropdownMenu
            onOpenChange={(open) => {
                if (open) {
                    void queryClient.refetchQueries({
                        queryKey: ['notifications', 'inbox'],
                    });
                }
            }}
        >
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    className="relative h-9 w-9 cursor-pointer"
                    aria-label="Notifications"
                    data-testid="notification-bell"
                >
                    <Bell className="!size-5 opacity-80" />
                    {count > 0 && (
                        <span className="absolute -top-0.5 -right-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-semibold text-white">
                            {count > 9 ? '9+' : count}
                        </span>
                    )}
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-80 p-2 md:w-96">
                <DropdownMenuLabel className="flex items-center justify-between">
                    <span>Notifications</span>
                    {count > 0 && (
                        <button
                            type="button"
                            className="text-xs font-normal text-muted-foreground underline"
                            onClick={() => readAll.mutate()}
                        >
                            Mark all read
                        </button>
                    )}
                </DropdownMenuLabel>
                <DropdownMenuSeparator />
                {!inbox?.length ? (
                    <p className="px-2 py-4 text-center text-sm text-muted-foreground">
                        No unread notifications
                    </p>
                ) : (
                    <div className="max-h-80 space-y-1 overflow-y-auto">
                        {inbox.map((item) => (
                            <div
                                key={item.id}
                                className="flex items-start gap-2 rounded-md px-2 py-2 hover:bg-accent"
                            >
                                <div className="min-w-0 flex-1">
                                    <p className="text-sm font-medium">
                                        {item.data.title ?? 'Notification'}
                                    </p>
                                    <p className="line-clamp-2 text-xs text-muted-foreground">
                                        {item.data.body}
                                    </p>
                                </div>
                                {item.data.target_url && (
                                    <Link
                                        to={item.data.target_url}
                                        className="text-xs text-blue-600 underline"
                                        onClick={() => markRead.mutate(item.id)}
                                    >
                                        Open
                                    </Link>
                                )}
                            </div>
                        ))}
                    </div>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
