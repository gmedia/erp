import { toast } from 'sonner';

// Error types for better error handling
export enum ErrorType {
  NETWORK = 'network',
  VALIDATION = 'validation',
  UNAUTHORIZED = 'unauthorized',
  FORBIDDEN = 'forbidden',
  NOT_FOUND = 'not_found',
  SERVER = 'server',
  UNKNOWN = 'unknown',
}

export interface ApiError {
  type: ErrorType;
  message: string;
  details?: Record<string, string[]>;
  statusCode?: number;
}

// Parse axios error into structured error
export function parseApiError(error: any): ApiError {
  if (!error?.response) {
    // Network error
    return {
      type: ErrorType.NETWORK,
      message: 'Network connection failed. Please check your internet connection.',
    };
  }

  const { status, data } = error.response;

  switch (status) {
    case 400:
      return {
        type: ErrorType.VALIDATION,
        message: data?.message || 'Invalid request data.',
        details: data?.errors,
        statusCode: status,
      };

    case 401:
      return {
        type: ErrorType.UNAUTHORIZED,
        message: 'Authentication required. Please log in again.',
        statusCode: status,
      };

    case 403:
      return {
        type: ErrorType.FORBIDDEN,
        message: 'You do not have permission to perform this action.',
        statusCode: status,
      };

    case 404:
      return {
        type: ErrorType.NOT_FOUND,
        message: data?.message || 'The requested resource was not found.',
        statusCode: status,
      };

    case 422:
      return {
        type: ErrorType.VALIDATION,
        message: data?.message || 'Validation failed.',
        details: data?.errors,
        statusCode: status,
      };

    case 500:
    case 502:
    case 503:
    case 504:
      return {
        type: ErrorType.SERVER,
        message: 'Server error. Please try again later.',
        statusCode: status,
      };

    default:
      return {
        type: ErrorType.UNKNOWN,
        message: data?.message || 'An unexpected error occurred.',
        statusCode: status,
      };
  }
}

// Handle and display error to user
export function handleApiError(error: any, fallbackMessage: string): ApiError {
  const parsedError = parseApiError(error);

  // Show appropriate toast based on error type
  switch (parsedError.type) {
    case ErrorType.VALIDATION:
      toast.error(parsedError.message);
      break;

    case ErrorType.UNAUTHORIZED:
    case ErrorType.FORBIDDEN:
      toast.error(parsedError.message);
      // Could trigger logout or redirect here
      break;

    case ErrorType.NETWORK:
    case ErrorType.SERVER:
      toast.error(parsedError.message);
      break;

    case ErrorType.NOT_FOUND:
      toast.error(parsedError.message);
      break;

    default:
      toast.error(fallbackMessage);
  }

  return parsedError;
}

// Create user-friendly error message for specific operations
export function createOperationErrorMessage(operation: string, entityName: string, error: ApiError): string {
  switch (error.type) {
    case ErrorType.VALIDATION:
      return `Please check your input and try again.`;

    case ErrorType.NETWORK:
      return `Failed to ${operation} ${entityName}. Please check your connection.`;

    case ErrorType.UNAUTHORIZED:
      return `You need to be logged in to ${operation} ${entityName}.`;

    case ErrorType.FORBIDDEN:
      return `You don't have permission to ${operation} ${entityName}.`;

    case ErrorType.NOT_FOUND:
      return `${entityName} not found or may have been deleted.`;

    case ErrorType.SERVER:
      return `Server error while trying to ${operation} ${entityName}. Please try again later.`;

    default:
      return `Failed to ${operation} ${entityName}. Please try again.`;
  }
}
