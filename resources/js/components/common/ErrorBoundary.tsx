'use client';

import React, { Component, ErrorInfo, ReactNode } from 'react';

import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { AlertTriangle, RefreshCw } from 'lucide-react';

interface Props {
    children: ReactNode;
    fallback?: ReactNode;
    onError?: (error: Error, errorInfo: ErrorInfo) => void;
}

interface State {
    hasError: boolean;
    error?: Error;
}

/**
 * ErrorBoundary - Catches JavaScript errors anywhere in the child component tree,
 * logs those errors, and displays a fallback UI instead of the component tree that crashed.
 */
export class ErrorBoundary extends Component<Props, State> {
    public state: State = {
        hasError: false,
    };

    public static getDerivedStateFromError(error: Error): State {
        return { hasError: true, error };
    }

    public componentDidCatch(error: Error, errorInfo: ErrorInfo) {
        console.error('ErrorBoundary caught an error:', error, errorInfo);
        this.props.onError?.(error, errorInfo);
    }

    private handleRetry = () => {
        this.setState({ hasError: false, error: undefined });
    };

    public render() {
        if (this.state.hasError) {
            if (this.props.fallback) {
                return this.props.fallback;
            }

            return (
                <Card className="mx-auto mt-8 w-full max-w-md">
                    <CardHeader className="text-center">
                        <div className="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-destructive/10">
                            <AlertTriangle className="h-6 w-6 text-destructive" />
                        </div>
                        <CardTitle className="text-destructive">
                            Something went wrong
                        </CardTitle>
                        <CardDescription>
                            An unexpected error occurred. Please try refreshing
                            the page.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="text-center">
                        {process.env.NODE_ENV === 'development' &&
                            this.state.error && (
                                <details className="mb-4 text-left">
                                    <summary className="cursor-pointer text-sm font-medium">
                                        Error Details (Development Only)
                                    </summary>
                                    <pre className="mt-2 text-xs whitespace-pre-wrap text-muted-foreground">
                                        {this.state.error.toString()}
                                    </pre>
                                </details>
                            )}
                        <Button onClick={this.handleRetry} variant="outline">
                            <RefreshCw className="mr-2 h-4 w-4" />
                            Try Again
                        </Button>
                    </CardContent>
                </Card>
            );
        }

        return this.props.children;
    }
}

/**
 * Hook-based error boundary for functional components
 * Note: This is a wrapper around the class-based ErrorBoundary
 */
export function withErrorBoundary<P extends object>(
    Component: React.ComponentType<P>,
    errorBoundaryProps?: Omit<Props, 'children'>,
) {
    const WrappedComponent = (props: P) => (
        <ErrorBoundary {...errorBoundaryProps}>
            <Component {...props} />
        </ErrorBoundary>
    );

    WrappedComponent.displayName = `withErrorBoundary(${Component.displayName || Component.name})`;

    return WrappedComponent;
}

/**
 * Simple error fallback component for inline usage
 */
export function ErrorFallback({
    error,
    retry,
}: {
    error?: Error;
    retry?: () => void;
}) {
    return (
        <div className="flex flex-col items-center justify-center p-8 text-center">
            <AlertTriangle className="mb-4 h-12 w-12 text-destructive" />
            <h3 className="mb-2 text-lg font-semibold text-destructive">
                Something went wrong
            </h3>
            <p className="mb-4 max-w-md text-muted-foreground">
                An unexpected error occurred. Please try again.
            </p>
            {process.env.NODE_ENV === 'development' && error && (
                <details className="mb-4 text-left">
                    <summary className="cursor-pointer text-sm font-medium">
                        Error Details (Development Only)
                    </summary>
                    <pre className="mt-2 text-xs whitespace-pre-wrap text-muted-foreground">
                        {error.toString()}
                    </pre>
                </details>
            )}
            {retry && (
                <Button onClick={retry} variant="outline" size="sm">
                    <RefreshCw className="mr-2 h-4 w-4" />
                    Try Again
                </Button>
            )}
        </div>
    );
}
