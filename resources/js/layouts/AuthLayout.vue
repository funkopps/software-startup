<script setup lang="ts">
import { home } from '@/routes';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex min-h-svh flex-col bg-[#171717] text-white">
        <header class="bg-[#0f0f0f]">
            <div class="mx-auto flex h-20 w-full max-w-6xl items-center px-6">
                <Link :href="home()" class="mx-auto inline-flex items-center">
                    <img
                        src="/logos/mindwave-logo.png"
                        alt="Mindwave"
                        class="h-12 w-auto"
                    />
                </Link>
            </div>
            <div class="h-[2px] bg-gradient-to-r from-amber-500 via-rose-500 to-violet-500" />
        </header>
        <section v-if="$slots.header" class="px-6 pt-24 pb-8">
            <div class="mx-auto w-full max-w-sm text-center">
                <slot name="header" />
            </div>
        </section>
        <main class="flex flex-1 items-start justify-center px-6 pb-10">
            <div class="w-full max-w-sm">
                <slot />
            </div>
        </main>
        <footer class="h-8 bg-[#0f0f0f]" />
    </div>
</template>
