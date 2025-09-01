<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import DocumentController from '@/actions/App/Http/Controllers/DocumentController';
import { create } from '@/routes/documents';
import { type BreadcrumbItem } from '@/types';
import { Head, Form } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import InputError from '@/components/InputError.vue';
import { ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Documents',
        href: '/documents',
    },
    {
        title: 'Add URL',
        href: create().url,
    },
];

const tags = ref<string>('');

const formatTags = (tagString: string): string[] => {
    return tagString
        .split(',')
        .map(tag => tag.trim())
        .filter(tag => tag.length > 0);
};
</script>

<template>
    <Head title="Add New URL" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="grid gap-4 md:max-w-2xl">
                <Card>
                    <CardHeader>
                        <CardTitle>Add New URL</CardTitle>
                        <CardDescription>
                            Add a URL to save it with metadata, tags, and automatic content extraction.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Form 
                            v-bind="DocumentController.store.form()" 
                            class="space-y-6" 
                            v-slot="{ errors, processing, recentlySuccessful, setData }"
                        >
                            <div class="grid gap-2">
                                <Label for="url">URL *</Label>
                                <Input
                                    id="url"
                                    name="url"
                                    type="url"
                                    placeholder="https://example.com/article"
                                    required
                                />
                                <InputError :message="errors.url" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="title">Title (Optional)</Label>
                                <Input
                                    id="title"
                                    name="title"
                                    type="text"
                                    placeholder="Leave empty to auto-extract from URL"
                                />
                                <InputError :message="errors.title" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="tags">Tags (Optional)</Label>
                                <Input
                                    id="tags"
                                    v-model="tags"
                                    type="text"
                                    placeholder="javascript, tutorial, programming"
                                    @input="setData('tags', formatTags(tags))"
                                />
                                <p class="text-sm text-muted-foreground">
                                    Separate tags with commas
                                </p>
                                <InputError :message="errors.tags" />
                            </div>

                            <div class="flex items-center gap-4">
                                <Button :disabled="processing">
                                    {{ processing ? 'Saving...' : 'Save URL' }}
                                </Button>

                                <Transition
                                    enter-active-class="transition ease-in-out"
                                    enter-from-class="opacity-0"
                                    leave-active-class="transition ease-in-out"
                                    leave-to-class="opacity-0"
                                >
                                    <p v-show="recentlySuccessful" class="text-sm text-green-600">
                                        URL saved successfully!
                                    </p>
                                </Transition>
                            </div>
                        </Form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-lg">What happens next?</CardTitle>
                    </CardHeader>
                    <CardContent class="text-sm text-muted-foreground space-y-2">
                        <p>After saving, the app will automatically:</p>
                        <ul class="list-disc list-inside space-y-1 ml-2">
                            <li>Extract the title and metadata from the URL</li>
                            <li>Generate a summary of the content</li>
                            <li>Capture a screenshot of the page</li>
                            <li>Extract and convert content to Markdown</li>
                        </ul>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>