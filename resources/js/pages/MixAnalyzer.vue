<template>
    <MainLayout content-class="max-w-[1250px]">
        <div class="w-full">
            <MixAnalyzer />
        </div>
    </MainLayout>
</template>

<script setup lang="ts">
import MixAnalyzer from '../components/MixAnalyzer.vue';
import MainLayout from '@/layouts/MainLayout.vue';
</script>