export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
}

export interface Category {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    icon: string | null;
    thumbnail: string | null;
    games_count?: number;
}

export interface Tag {
    id: number;
    name: string;
    slug: string;
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
    is_featured: boolean;
    is_trending: boolean;
    is_popular: boolean;
    plays_count: number;
    seo_title: string | null;
    seo_description: string | null;
    embed_url: string;
    sandbox_attributes: string;
    created_at?: string;
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
    og_title?: string;
    og_description?: string;
    og_image?: string;
    og_url?: string;
    og_type?: string;
    twitter_card?: string;
    schema?: Record<string, unknown>;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    appName: string;
    categoriesNav: Category[];
    auth: {
        user: User;
    };
    meta?: SiteMeta;
};
