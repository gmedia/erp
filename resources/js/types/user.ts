export interface User {
    id: number;
    name: string;
    email: string;
    // Optional avatar URL for user profile images
    avatar?: string;
    email_verified_at?: string;
    must_verify_email?: boolean;
    created_at: string;
    updated_at: string;
}
