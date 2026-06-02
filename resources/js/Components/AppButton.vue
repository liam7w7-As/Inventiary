<template>
    <button
        :type="type"
        class="app-btn"
        :class="[`app-btn--${variant}`, { 'app-btn--loading': loading }]"
        :disabled="disabled || loading"
    >
        <svg v-if="loading" class="app-btn__spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity="0.25"></circle>
            <path fill="currentColor" opacity="0.75" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <slot />
    </button>
</template>

<script setup>
defineProps({
    type: { type: String, default: 'button' },
    variant: { type: String, default: 'primary' },
    loading: Boolean,
    disabled: Boolean,
})
</script>

<style scoped>
.app-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.45rem;
    padding: 0.55rem 1.15rem;
    border-radius: 8px;
    font-size: 0.82rem;
    font-weight: 600;
    border: 1.5px solid transparent;
    cursor: pointer;
    transition: all 0.15s;
    font-family: inherit;
    white-space: nowrap;
}
.app-btn:disabled { opacity: 0.55; cursor: not-allowed; }

.app-btn--primary {
    background: linear-gradient(135deg, #3b82f6, #6366f1);
    color: #fff;
}
.app-btn--primary:hover:not(:disabled) {
    box-shadow: 0 4px 14px rgba(59,130,246,0.3);
    transform: translateY(-1px);
}

.app-btn--secondary {
    background: #fff;
    color: #475569;
    border-color: #e2e8f0;
}
.app-btn--secondary:hover:not(:disabled) {
    background: #f8fafc;
    border-color: #cbd5e1;
}

.app-btn--danger {
    background: #ef4444;
    color: #fff;
}
.app-btn--danger:hover:not(:disabled) {
    background: #dc2626;
    box-shadow: 0 4px 14px rgba(239,68,68,0.3);
}

.app-btn__spinner {
    width: 16px;
    height: 16px;
    animation: spin 1s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>
