<script setup lang="ts">
import MainLayout from '@/layouts/MainLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <MainLayout mainClass="items-start pt-10" contentClass="w-full max-w-none">
        <Head title="About" />
        <div class="mx-auto w-full max-w-3xl text-center">
            <h1 class="text-3xl font-semibold">About</h1>
            <p class="mt-3 text-neutral-300">
                Mixlens helps you analyze mixes with fast, focused feedback.
            </p>
        </div>
    </MainLayout>
</template>
