<template>
    <AppModal :show="show" :title="title" size="sm" @close="$emit('cancel')">
        <div class="confirm-body">
            <div :class="['confirm-icon', `confirm-icon--${variant}`]">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
            </div>
            <p class="confirm-message">{{ message }}</p>
        </div>
        <template #footer>
            <button class="confirm-cancel" @click="$emit('cancel')">Cancelar</button>
            <button :class="['confirm-btn', `confirm-btn--${variant}`]" @click="$emit('confirm')">
                {{ confirmText }}
            </button>
        </template>
    </AppModal>
</template>

<script setup>
import AppModal from './AppModal.vue'

defineProps({
    show: Boolean,
    title: { type: String, default: '¿Estás seguro?' },
    message: String,
    confirmText: { type: String, default: 'Eliminar' },
    variant: { type: String, default: 'danger' },
})

defineEmits(['confirm', 'cancel'])
</script>

<style scoped>
.confirm-body { display: flex; flex-direction: column; align-items: center; gap: 0.75rem; text-align: center; padding: 0.5rem 0; }
.confirm-icon {
    width: 48px; height: 48px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
}
.confirm-icon--danger { background: rgba(239,68,68,0.1); color: #ef4444; }
.confirm-icon--warning { background: rgba(245,158,11,0.1); color: #f59e0b; }
.confirm-message { font-size: 0.85rem; color: #475569; margin: 0; line-height: 1.5; }

.confirm-cancel {
    padding: 0.5rem 1rem; border-radius: 8px; border: 1.5px solid #e2e8f0;
    background: #fff; color: #475569; font-size: 0.82rem; font-weight: 600;
    cursor: pointer; font-family: inherit; transition: all 0.15s;
}
.confirm-cancel:hover { background: #f8fafc; }

.confirm-btn {
    padding: 0.5rem 1rem; border-radius: 8px; border: none;
    color: #fff; font-size: 0.82rem; font-weight: 600;
    cursor: pointer; font-family: inherit; transition: all 0.15s;
}
.confirm-btn--danger { background: #ef4444; }
.confirm-btn--danger:hover { background: #dc2626; }
.confirm-btn--warning { background: #f59e0b; }
.confirm-btn--warning:hover { background: #d97706; }
</style>
