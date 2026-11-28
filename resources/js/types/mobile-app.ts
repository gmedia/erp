export interface MobileAppRelease {
    id: number;
    version_name: string;
    version_code: number;
    min_supported_version_code: number;
    notes: string | null;
    file_size: number;
    sha256: string;
    is_active: boolean;
    uploaded_by: number | null;
    uploader_name: string | null;
    created_at: string | null;
    updated_at: string | null;
}

export interface MobileAppReleaseMeta {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
    from?: number;
    to?: number;
}

export interface MobileAppReleaseListResponse {
    data: MobileAppRelease[];
    meta: MobileAppReleaseMeta;
}

export interface MobileAppReleaseUploadInput {
    file: File;
    version_name: string;
    version_code: number;
    min_supported_version_code: number;
    notes: string | null;
}
