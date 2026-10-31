export interface User {
    id: number;
    name: string;
    email: string;
    role?: 'super_admin' | 'admin' | 'content_manager' | 'analyst' | 'user' | string;
    is_active?: boolean;
    email_verified_at?: string;
    created_at?: string;
}

export interface HomepageSection {
    id: number;
    section_key: string;
    title: string;
    subtitle: string | null;
    is_enabled: boolean;
    sort_order: number;
    items_limit: number;
    curated_game_ids: number[] | null;
    display_options?: Record<string, any> | null;
    created_at?: string;
    updated_at?: string;
}

export interface HomepageSlide {
    id: number;
    title: string;
    subtitle: string | null;
    cta_text: string | null;
    cta_url: string | null;
    background_image: string | null;
    game_id: number | null;
    game?: Game;
    is_enabled: boolean;
    sort_order: number;
    created_at?: string;
    updated_at?: string;
}

export interface PlatformSetting {
    id: number;
    key: string;
    group: string;
    value: any;
    type: string;
    description: string | null;
}

export interface AdminAuditLog {
    id: number;
    user_id: number | null;
    user_email: string | null;
    action: string;
    resource_type: string | null;
    resource_id: string | null;
    description: string;
    details: Record<string, any> | null;
    ip_address: string | null;
    created_at: string;
}

export interface Category {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    icon: string | null;
    thumbnail: string | null;
    status?: 'active' | 'inactive';
    sort_order?: number;
    games_count?: number;
    published_games_count?: number;
}

export interface Tag {
    id: number;
    name: string;
    slug: string;
    games_count?: number;
}

export type ReviewStatus = 'not_submitted' | 'pending' | 'changes_requested' | 'approved' | 'rejected';

export interface GameReview {
    id: number;
    game_id: number;
    user_id: number;
    user?: User;
    action: 'submit' | 'approve' | 'approve_and_publish' | 'request_changes' | 'reject' | 'return_to_draft' | string;
    from_status: ReviewStatus | string;
    to_status: ReviewStatus | string;
    notes: string | null;
    metadata?: Record<string, any> | null;
    created_at: string;
}

export interface StagingFolderInfo {
    folder: string;
    path: string;
    size_bytes: number;
    size_formatted: string;
    file_count: number;
    has_index: boolean;
    entry_file: string;
    modified_at: string;
    is_imported: boolean;
    existing_game?: {
        id: number;
        title: string;
        slug: string;
        status: string;
    } | null;
}

export interface StagingInspectionResult {
    is_valid: boolean;
    folder: string;
    entry_file: string;
    file_count: number;
    size_bytes: number;
    size_formatted: string;
    fingerprint: string | null;
    metadata: {
        title: string | null;
        description: string | null;
        instructions: string | null;
        author?: string | null;
        version?: string | null;
        [key: string]: any;
    };
    suggested_category: {
        id: number;
        name: string;
        slug: string;
    } | null;
    thumbnail: {
        detected: boolean;
        preview_url: string | null;
        relative_path: string | null;
    };
    errors: string[];
    warnings: string[];
    is_duplicate?: boolean;
    duplicate_game?: {
        id: number;
        title: string;
        slug: string;
    } | null;
}

export interface Game {
    id: number;
    title: string;
    slug: string;
    description: string | null;
    instructions: string | null;
    thumbnail: string | null;
    category_id: number;
    category?: Category;
    tags?: Tag[];
    game_type: 'single_file' | 'multi_file' | 'original';
    entry_file: string;
    storage_path: string | null;
    status: 'draft' | 'published' | 'archived';
    review_status?: ReviewStatus;
    submitted_at?: string | null;
    submitted_by?: number | null;
    submitted_by_user?: User | null;
    reviewed_at?: string | null;
    reviewed_by?: number | null;
    reviewed_by_user?: User | null;
    review_notes?: string | null;
    reviews?: GameReview[];
    is_featured: boolean;
    is_trending: boolean;
    is_popular: boolean;
    plays_count: number;
    rating?: number | null;
    rating_count?: number;
    playing_now?: number;
    seo_title: string | null;
    seo_description: string | null;
    embed_url: string;
    sandbox_attributes: string;
    fingerprint?: string | null;
    scan_metadata?: Record<string, any> | null;
    created_at?: string;
    updated_at?: string;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface PaginatedData<T> {
    data: T[];
    links: PaginationLink[];
    current_page: number;
    first_page_url: string;
    from: number | null;
    last_page: number;
    last_page_url: string;
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number | null;
    total: number;
}

export interface SiteMeta {
    title: string;
    description: string;
    canonical?: string;
    robots?: string;
    og_title?: string;
    og_description?: string;
    og_image?: string;
    og_url?: string;
    og_type?: string;
    twitter_card?: string;
    breadcrumbs?: Array<{ name: string; url: string }>;
    schema?: Record<string, unknown> | Array<Record<string, unknown>>;
}

export interface PublicAdProvider {
    key: string;
    name: string;
    enabled: boolean;
    configured: boolean;
    publisher_id: string | null;
    script_url: string | null;
    test_mode: boolean;
    preload_ad_breaks?: string;
    integration_model?: string;
    requires_sdk?: boolean;
}

export interface PublicAdPlacement {
    key: string;
    name: string;
    provider: string;
    slot_id: string | null;
    placement_type: 'display' | 'banner' | 'interstitial' | 'rewarded';
    show_on_desktop: boolean;
    show_on_mobile: boolean;
    frequency_limit: number;
    provider_config?: Record<string, any>;
}

export interface PublicAdConfig {
    ads_enabled: boolean;
    consent_required: boolean;
    consent_mode: string;
    consent_policy_version: string;
    default_provider: string;
    providers: Record<string, PublicAdProvider>;
    placements: Record<string, PublicAdPlacement>;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    appName: string;
    categoriesNav: Category[];
    megaMenuGames?: Game[];
    pendingReviewCount?: number;
    auth: {
        user: User;
    };
    meta?: SiteMeta;
    adConfig?: PublicAdConfig;
    flash?: {
        success?: string | null;
        error?: string | null;
        warning?: string | null;
    };
};
