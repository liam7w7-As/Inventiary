<template>
    <teleport to="body">
        <transition name="modal">
            <div v-if="show" class="modal-backdrop" @mousedown.self="$emit('close')">
                <div :class="['modal-panel', `modal-panel--${size}`]" @keydown.esc="$emit('close')">
                    <div class="modal-header">
                        <h3 class="modal-title">{{ title }}</h3>
                        <button class="modal-close" @click="$emit('close')">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                        </button>
                    </div>
                    <div class="modal-body">
                        <slot />
                    </div>
                    <div v-if="$slots.footer" class="modal-footer">
                        <slot name="footer" />
                    </div>
                </div>
            </div>
        </transition>
    </teleport>
</template>

<script setup>
import { watch, onMounted, onUnmounted } from 'vue'

const props = defineProps({
    show: Boolean,
    title: String,
    size: { type: String, default: 'md' },
})

const emit = defineEmits(['close'])

function onEsc(e) {
    if (e.key === 'Escape' && props.show) emit('close')
}

onMounted(() => document.addEventListener('keydown', onEsc))
onUnmounted(() => document.removeEventListener('keydown', onEsc))

watch(() => props.show, (val) => {
    document.body.style.overflow = val ? 'hidden' : ''
})
</script>

<style scoped>
.modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 100;
    padding: 1rem;
}
.modal-panel {
    background: #fff;
    border-radius: 14px;
    width: 100%;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 60px rgba(0,0,0,0.15);
    animation: modal-in 0.2s ease;
}
.modal-panel--sm { max-width: 400px; }
.modal-panel--md { max-width: 520px; }
.modal-panel--lg { max-width: 700px; }

@keyframes modal-in {
    from { opacity: 0; transform: scale(0.95) translateY(10px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.1rem 1.25rem;
    border-bottom: 1px solid #e2e8f0;
}
.modal-title { font-size: 1rem; font-weight: 700; color: #1e293b; margin: 0; }
.modal-close {
    background: none; border: none; color: #94a3b8; cursor: pointer;
    padding: 0.25rem; border-radius: 6px; transition: all 0.15s;
}
.modal-close:hover { background: #f1f5f9; color: #475569; }
.modal-body { padding: 1.25rem; overflow-y: auto; flex: 1; }
.modal-footer {
    display: flex; align-items: center; justify-content: flex-end; gap: 0.5rem;
    padding: 0.85rem 1.25rem; border-top: 1px solid #e2e8f0;
}

.modal-enter-active, .modal-leave-active { transition: opacity 0.2s; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>
