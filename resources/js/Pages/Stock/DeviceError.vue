<script setup>
</script>
<template>
  <div class="min-h-screen bg-gradient-to-br from-rose-100 to-slate-100 flex items-center justify-center px-4">
    <div class="card max-w-2xl w-full p-10 text-center">
      <div class="w-20 h-20 bg-rose-100 rounded-2xl flex items-center justify-center mx-auto mb-6">
        <i class="fas fa-exclamation-triangle text-rose-500 text-3xl"></i>
      </div>

      <h1 class="text-3xl font-bold text-slate-800 mb-4">Device Not Registered</h1>

      <div class="bg-rose-50 border border-rose-200 rounded-xl p-5 mb-6">
        <p class="text-rose-700 text-lg leading-relaxed">
          No Device ID found.<br>
          Please contact your administrator.
        </p>
      </div>

      <div class="pt-6 border-t border-slate-100">
        <p class="text-xs text-slate-400">Stock Management System - Authentication Error</p>
      </div>
    </div>
  </div>
</template>
