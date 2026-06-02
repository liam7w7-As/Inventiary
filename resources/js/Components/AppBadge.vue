<template>
    <span :class="['badge', `badge--${variant}`]">
        <slot />
    </span>
</template>

<script setup>
defineProps({
    variant: { type: String, default: 'gray' },
})
</script>

<style scoped>
.badge {
    display: inline-flex;
    align-items: center;
    padding: 0.15rem 0.55rem;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
    white-space: nowrap;
    letter-spacing: 0.01em;
}
.badge--success { background: rgba(16,185,129,0.1); color: #059669; }
.badge--warning { background: rgba(245,158,11,0.1); color: #d97706; }
.badge--danger { background: rgba(239,68,68,0.1); color: #dc2626; }
.badge--info { background: rgba(59,130,246,0.1); color: #2563eb; }
.badge--gray { background: #f1f5f9; color: #64748b; }
</style>
