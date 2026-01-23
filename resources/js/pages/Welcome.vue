<script setup lang="ts">
import MainLayout from '@/layouts/MainLayout.vue';
import { Head } from '@inertiajs/vue3';
import MixAnalyzer from '@/components/MixAnalyzer.vue';
</script>

<template>
    <MainLayout mainClass="items-start pt-10" contentClass="max-w-none">
        <Head title="Welcome" />
        <div class="mx-auto flex w-full max-w-5xl flex-col items-center gap-10">
            <img
                src="/logos/mixlens-logo.png"
                alt="Mixlens"
                class="h-40 w-auto"
            />
        </div>
        <section class="mx-auto w-full max-w-5xl">
            <MixAnalyzer />
        </section>
    </MainLayout>
</template>
