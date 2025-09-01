<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { index, show, update } from '@/routes/documents';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Input } from '@/components/ui/input';
import Icon from '@/components/Icon.vue';
import { marked } from 'marked';
import { computed, ref } from 'vue';

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
    content: string | null;
    published_at: string | null;
    created_at: string;
    updated_at: string;
    tags: Tag[];
}

interface Props {
    document: Document;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Documents',
        href: index().url,
    },
    {
        title: 'Document Details',
        href: show(props.document.id).url,
    },
];

const formatDate = (dateString: string | null): string => {
    if (!dateString) return 'Unknown';
    return new Date(dateString).toLocaleDateString();
};

const getDomain = (url: string): string => {
    try {
        return new URL(url).hostname;
    } catch {
        return url;
    }
};

// Configure marked for better rendering
marked.setOptions({
    breaks: true,
    gfm: true,
});

const renderedContent = computed(() => {
    if (!props.document.content) return '';
    return marked(props.document.content);
});

// Tag editing functionality
const isEditingTags = ref(false);
const newTagInput = ref('');
const editableTags = ref<string[]>([]);

const initializeEditableTags = () => {
    editableTags.value = props.document.tags.map(tag => tag.name);
};

const startEditingTags = () => {
    initializeEditableTags();
    isEditingTags.value = true;
};

const cancelEditingTags = () => {
    isEditingTags.value = false;
    newTagInput.value = '';
};

const addTag = () => {
    const tag = newTagInput.value.trim();
    if (tag && !editableTags.value.includes(tag)) {
        editableTags.value.push(tag);
        newTagInput.value = '';
    }
};

const removeTag = (tagToRemove: string) => {
    editableTags.value = editableTags.value.filter(tag => tag !== tagToRemove);
};

const form = useForm({
    tags: [] as string[]
});

const saveTags = () => {
    form.tags = [...editableTags.value];
    form.put(update(props.document.id).url, {
        onSuccess: () => {
            isEditingTags.value = false;
            newTagInput.value = '';
        }
    });
};
</script>

<template>
    <Head :title="document.title || 'Document'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4">
            <!-- Header -->
            <div class="flex justify-between items-start gap-4">
                <div class="flex-1 min-w-0">
                    <h1 class="text-3xl font-bold tracking-tight">
                        {{ document.title || 'Untitled Document' }}
                    </h1>
                    <div class="flex items-center gap-2 mt-2 text-sm text-muted-foreground">
                        <span>{{ getDomain(document.url) }}</span>
                        <span>•</span>
                        <span>{{ formatDate(document.created_at) }}</span>
                        <span v-if="document.author">•</span>
                        <span v-if="document.author">{{ document.author }}</span>
                    </div>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" as-child>
                        <a :href="document.url" target="_blank" rel="noopener noreferrer">
                            <Icon name="ExternalLink" class="mr-2 h-4 w-4" />
                            Visit Original
                        </a>
                    </Button>
                    <Button variant="outline" as-child>
                        <Link :href="index().url">
                            <Icon name="ArrowLeft" class="mr-2 h-4 w-4" />
                            Back to List
                        </Link>
                    </Button>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-3">
                <!-- Main Content -->
                <div class="md:col-span-2 space-y-6">
                    <!-- Summary -->
                    <Card v-if="document.summary">
                        <CardHeader>
                            <CardTitle class="text-lg">Summary</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-muted-foreground leading-relaxed">
                                {{ document.summary }}
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Full Content -->
                    <Card v-if="document.content">
                        <CardHeader>
                            <CardTitle class="text-lg">Content</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div 
                                class="prose prose-sm max-w-none dark:prose-invert prose-pre:bg-gray-100 dark:prose-pre:bg-gray-800 prose-pre:p-4 prose-pre:rounded-lg prose-pre:overflow-x-auto prose-code:bg-gray-100 dark:prose-code:bg-gray-800 prose-code:px-1 prose-code:py-0.5 prose-code:rounded"
                                v-html="renderedContent"
                            />
                        </CardContent>
                    </Card>

                    <!-- No Content Message -->
                    <Card v-if="!document.summary && !document.content">
                        <CardContent class="text-center py-8">
                            <Icon name="FileText" class="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                            <h3 class="text-lg font-semibold mb-2">Content Not Available</h3>
                            <p class="text-muted-foreground">
                                Content extraction is in progress or was not successful for this URL.
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar -->
                <div class="space-y-4">
                    <!-- Image/Screenshot -->
                    <Card v-if="document.image || document.screenshot">
                        <CardContent class="p-4">
                            <img 
                                :src="document.image || document.screenshot" 
                                :alt="document.title"
                                class="w-full rounded object-cover"
                                loading="lazy"
                            />
                        </CardContent>
                    </Card>

                    <!-- Metadata -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-lg">Details</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div>
                                <dt class="text-sm font-medium text-muted-foreground">URL</dt>
                                <dd class="mt-1">
                                    <a 
                                        :href="document.url" 
                                        target="_blank" 
                                        rel="noopener noreferrer"
                                        class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200 break-all"
                                    >
                                        {{ document.url }}
                                    </a>
                                </dd>
                            </div>

                            <Separator />

                            <div>
                                <dt class="text-sm font-medium text-muted-foreground">Source</dt>
                                <dd class="mt-1 text-sm">{{ document.source || 'Unknown' }}</dd>
                            </div>

                            <div v-if="document.published_at">
                                <dt class="text-sm font-medium text-muted-foreground">Published</dt>
                                <dd class="mt-1 text-sm">{{ formatDate(document.published_at) }}</dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-muted-foreground">Saved</dt>
                                <dd class="mt-1 text-sm">{{ formatDate(document.created_at) }}</dd>
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <dt class="text-sm font-medium text-muted-foreground">Tags</dt>
                                    <Button 
                                        v-if="!isEditingTags" 
                                        variant="ghost" 
                                        size="sm"
                                        @click="startEditingTags"
                                    >
                                        <Icon name="Edit" class="h-3 w-3" />
                                        Edit
                                    </Button>
                                </div>
                                
                                <!-- Display mode -->
                                <dd v-if="!isEditingTags" class="flex flex-wrap gap-1">
                                    <Badge 
                                        v-for="tag in document.tags" 
                                        :key="tag.id"
                                        variant="secondary"
                                        class="text-xs"
                                    >
                                        {{ tag.name }}
                                    </Badge>
                                    <span v-if="document.tags.length === 0" class="text-sm text-muted-foreground">
                                        No tags
                                    </span>
                                </dd>
                                
                                <!-- Edit mode -->
                                <div v-else class="space-y-3">
                                    <div class="flex flex-wrap gap-1">
                                        <Badge 
                                            v-for="tag in editableTags" 
                                            :key="tag"
                                            variant="secondary"
                                            class="text-xs cursor-pointer hover:bg-destructive hover:text-destructive-foreground"
                                            @click="removeTag(tag)"
                                        >
                                            {{ tag }}
                                            <Icon name="X" class="ml-1 h-2 w-2" />
                                        </Badge>
                                    </div>
                                    
                                    <div class="flex gap-2">
                                        <Input 
                                            v-model="newTagInput"
                                            placeholder="Add new tag..."
                                            class="text-sm"
                                            @keyup.enter="addTag"
                                        />
                                        <Button 
                                            size="sm" 
                                            variant="outline"
                                            @click="addTag"
                                            :disabled="!newTagInput.trim()"
                                        >
                                            Add
                                        </Button>
                                    </div>
                                    
                                    <div class="flex gap-2">
                                        <Button 
                                            size="sm" 
                                            @click="saveTags"
                                            :disabled="form.processing"
                                        >
                                            <Icon v-if="form.processing" name="Loader2" class="mr-2 h-3 w-3 animate-spin" />
                                            Save Changes
                                        </Button>
                                        <Button 
                                            size="sm" 
                                            variant="outline"
                                            @click="cancelEditingTags"
                                            :disabled="form.processing"
                                        >
                                            Cancel
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style>
.prose img {
    border-radius: 0.5rem;
}
</style>