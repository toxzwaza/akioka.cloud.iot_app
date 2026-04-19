<script setup>
import MainLayout from "@/Layouts/MainLayout.vue";
</script>

<template>
    <MainLayout :title="'Settings'">
        <template #content>
            <div class="page-header">
                <div>
                    <h1 class="section-title">
                        <i class="fas fa-cog text-primary-500 mr-2"></i>
                        Settings
                    </h1>
                    <p class="section-subtitle">Device configuration</p>
                </div>
            </div>

            <div class="card p-8 text-center">
                <div class="w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-sliders-h text-slate-400 text-2xl"></i>
                </div>
                <h2 class="text-lg font-semibold text-slate-600 mb-2">Device Settings</h2>
                <p class="text-sm text-slate-400">Configuration options will appear here when devices are registered.</p>
            </div>
        </template>
    </MainLayout>
</template>
