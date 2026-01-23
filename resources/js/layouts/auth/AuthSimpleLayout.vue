<script setup lang="ts">
import { home } from '@/routes';
import { Link } from '@inertiajs/vue3';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="flex min-h-svh flex-col bg-background text-foreground">
        <header
            class="h-16 w-full rounded-b-xl"
            style="background: radial-gradient(120% 80% at 0% 0%, rgba(255, 142, 61, 0.6) 0%, rgba(255, 142, 61, 0) 55%), radial-gradient(120% 80% at 100% 0%, rgba(167, 76, 255, 0.6) 0%, rgba(167, 76, 255, 0) 55%), radial-gradient(140% 120% at 50% 100%, rgba(255, 106, 0, 0.5) 0%, rgba(255, 106, 0, 0) 60%), linear-gradient(120deg, #5a2a82 0%, #ff7a2d 55%, #7a2cbf 100%);"
        />
        <div class="flex flex-1 flex-col px-6 py-10">
            <div class="flex justify-center pb-6 pt-16">
                <Link :href="home()" class="text-8xl font-bold text-white">
                    mindwave
                </Link>
            </div>
            <div class="flex flex-1 items-center justify-center">
                <div class="w-full max-w-sm">
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
