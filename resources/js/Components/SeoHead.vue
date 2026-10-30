<script setup lang="ts">
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import type { SiteMeta } from '@/types';

const props = defineProps<{
    meta?: SiteMeta;
}>();

const jsonLdString = computed(() => {
    if (!props.meta?.schema) {
        return null;
    }
    return JSON.stringify(props.meta.schema);
});
</script>

<template>
    <Head :title="meta?.title">
        <meta v-if="meta?.description" name="description" :content="meta.description" />
        <meta name="robots" :content="meta?.robots || 'index, follow'" />
        <link v-if="meta?.canonical" rel="canonical" :href="meta.canonical" />

        <!-- Open Graph / Facebook -->
        <meta v-if="meta?.og_type" property="og:type" :content="meta.og_type" />
        <meta v-if="meta?.og_title" property="og:title" :content="meta.og_title" />
        <meta v-if="meta?.og_description" property="og:description" :content="meta.og_description" />
        <meta v-if="meta?.og_image" property="og:image" :content="meta.og_image" />
        <meta v-if="meta?.og_url" property="og:url" :content="meta.og_url" />

        <!-- Twitter Card -->
        <meta v-if="meta?.twitter_card" name="twitter:card" :content="meta.twitter_card" />
        <meta v-if="meta?.og_title" name="twitter:title" :content="meta.og_title" />
        <meta v-if="meta?.og_description" name="twitter:description" :content="meta.og_description" />
        <meta v-if="meta?.og_image" name="twitter:image" :content="meta.og_image" />

        <!-- Schema.org JSON-LD (Updated on Inertia Page Transitions) -->
        <component
            :is="'script'"
            v-if="jsonLdString"
            type="application/ld+json"
            v-text="jsonLdString"
        />
    </Head>
</template>
