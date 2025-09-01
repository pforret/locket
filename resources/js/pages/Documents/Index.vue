<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { index, create, show } from '@/routes/documents';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import Icon from '@/components/Icon.vue';

interface Tag {
    id: number;
    name: string;
    slug: string;
}

interface Document {
    id: number;
    title: string;
    url: string;
    image: string | null;
    screenshot: string | null;
    author: string | null;
    source: string | null;
    summary: string | null;
    created_at: string;
    updated_at: string;
    tags: Tag[];
}

interface Props {
    documents: {
        data: Document[];
        links: any[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Documents',
        href: index().url,
    },
];

const formatDate = (dateString: string): string => {
    return new Date(dateString).toLocaleDateString();
};

const getDomain = (url: string): string => {
    try {
        return new URL(url).hostname;
    } catch {
        return url;
    }
};
</script>

<template>
    <Head title="Documents" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold">Saved URLs</h1>
                <Button as-child>
                    <Link :href="create().url">
                        <Icon name="Plus" class="mr-2 h-4 w-4" />
                        Add URL
                    </Link>
                </Button>
            </div>

            <div v-if="documents.data.length === 0" class="text-center py-12">
                <div class="mx-auto max-w-md">
                    <Icon name="BookOpen" class="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                    <h3 class="text-lg font-semibold mb-2">No documents saved yet</h3>
                    <p class="text-muted-foreground mb-4">
                        Start building your personal knowledge base by saving your first URL.
                    </p>
                    <Button as-child>
                        <Link :href="create().url">
                            <Icon name="Plus" class="mr-2 h-4 w-4" />
                            Add Your First URL
                        </Link>
                    </Button>
                </div>
            </div>

            <div v-else class="grid gap-4 grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
                <Card 
                    v-for="document in documents.data" 
                    :key="document.id"
                    class="hover:shadow-md transition-shadow cursor-pointer"
                    @click="$inertia.visit(show(document.id).url)"
                >
                    <CardHeader>
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1 min-w-0">
                                <CardTitle class="text-lg line-clamp-2">
                                    {{ document.title || 'Untitled' }}
                                </CardTitle>
                                <CardDescription class="flex items-center gap-2 mt-1">
                                    <span>{{ getDomain(document.url) }}</span>
                                    <span>•</span>
                                    <span>{{ formatDate(document.created_at) }}</span>
                                    <span v-if="document.author">•</span>
                                    <span v-if="document.author">{{ document.author }}</span>
                                </CardDescription>
                            </div>
                            <div v-if="document.image" class="flex-shrink-0">
                                <img 
                                    :src="document.image" 
                                    :alt="document.title"
                                    class="w-20 h-20 object-cover rounded"
                                    loading="lazy"
                                />
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent v-if="document.summary || document.tags.length > 0">
                        <div v-if="document.summary" class="text-sm text-muted-foreground mb-3 line-clamp-2">
                            {{ document.summary }}
                        </div>
                        <div v-if="document.tags.length > 0" class="flex flex-wrap gap-1">
                            <Badge 
                                v-for="tag in document.tags" 
                                :key="tag.id"
                                variant="secondary"
                                class="text-xs"
                            >
                                {{ tag.name }}
                            </Badge>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Pagination -->
            <div v-if="documents.last_page > 1" class="flex justify-center mt-6">
                <div class="flex gap-2">
                    <Button
                        v-for="link in documents.links"
                        :key="link.label"
                        variant="outline"
                        size="sm"
                        :disabled="!link.url"
                        :class="{ 'bg-primary text-primary-foreground': link.active }"
                        @click="link.url && $inertia.visit(link.url)"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>