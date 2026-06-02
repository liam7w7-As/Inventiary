<template>
    <teleport to="body">
        <transition-group name="toast" tag="div" class="flash-container">
            <div v-for="msg in messages" :key="msg.id" :class="['flash-toast', `flash-toast--${msg.type}`]">
                <svg v-if="msg.type === 'success'" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
                <span class="flash-text">{{ msg.text }}</span>
                <button class="flash-dismiss" @click="dismiss(msg.id)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>
        </transition-group>
    </teleport>
</template>

<script setup>
import { ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'

const page = usePage()
const messages = ref([])
let counter = 0

function addMessage(text, type) {
    const id = ++counter
    messages.value.push({ id, text, type })
    setTimeout(() => dismiss(id), 4000)
}

function dismiss(id) {
    messages.value = messages.value.filter(m => m.id !== id)
}

watch(() => page.props.flash?.success, (val) => {
    if (val) addMessage(val, 'success')
}, { immediate: true })

watch(() => page.props.flash?.error, (val) => {
    if (val) addMessage(val, 'error')
}, { immediate: true })
</script>

<style scoped>
.flash-container {
    position: fixed;
    top: 1rem;
    right: 1rem;
    z-index: 200;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    pointer-events: none;
}
.flash-toast {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.7rem 1rem;
    border-radius: 10px;
    font-size: 0.82rem;
    font-weight: 500;
    pointer-events: auto;
    box-shadow: 0 8px 24px rgba(0,0,0,0.1);
    min-width: 280px;
    max-width: 400px;
    animation: toast-in 0.3s ease;
}
.flash-toast--success {
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    color: #065f46;
}
.flash-toast--error {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #991b1b;
}
.flash-text { flex: 1; }
.flash-dismiss {
    background: none; border: none; color: inherit; cursor: pointer;
    opacity: 0.5; padding: 0.15rem; border-radius: 4px; transition: opacity 0.15s;
}
.flash-dismiss:hover { opacity: 1; }

@keyframes toast-in {
    from { opacity: 0; transform: translateX(20px); }
    to { opacity: 1; transform: translateX(0); }
}

.toast-enter-active { transition: all 0.3s ease; }
.toast-leave-active { transition: all 0.25s ease; }
.toast-enter-from { opacity: 0; transform: translateX(20px); }
.toast-leave-to { opacity: 0; transform: translateX(20px) scale(0.95); }
</style>
