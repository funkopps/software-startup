<script setup lang="ts">
import MainLayout from '@/layouts/MainLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <MainLayout mainClass="items-start pt-10" contentClass="w-full max-w-none">
        <Head title="Contact" />
        <div class="mx-auto w-full max-w-3xl text-center">
            <h1 class="text-3xl font-semibold">Contact</h1>
            <p class="mt-3 text-neutral-300">
                Email us at support@mixlens.ai or reach out on our socials.
            </p>
        </div>
    </MainLayout>
</template>
