<template>
    <div class="form-group">
        <label v-if="label" :for="id" class="form-label">
            {{ label }}
            <span v-if="required" class="form-label__req">*</span>
        </label>
        <select
            :id="id"
            :value="modelValue"
            :disabled="disabled"
            :required="required"
            class="app-select"
            :class="{ 'app-select--error': error, 'app-select--disabled': disabled }"
            @change="$emit('update:modelValue', $event.target.value)"
        >
            <option value="" disabled>Seleccionar...</option>
            <option v-for="opt in options" :key="opt.value" :value="opt.value">
                {{ opt.label }}
            </option>
        </select>
        <p v-if="error" class="form-error">{{ error }}</p>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    label: String,
    modelValue: [String, Number],
    options: { type: Array, default: () => [] },
    error: String,
    required: Boolean,
    disabled: Boolean,
})

defineEmits(['update:modelValue'])

const id = computed(() => 'select-' + (props.label || '').toLowerCase().replace(/\s+/g, '-') + '-' + Math.random().toString(36).slice(2, 6))
</script>

<style scoped>
.form-group { display: flex; flex-direction: column; gap: 0.3rem; }
.form-label { font-size: 0.8rem; font-weight: 600; color: #334155; }
.form-label__req { color: #ef4444; margin-left: 1px; }
.app-select {
    width: 100%;
    padding: 0.55rem 0.75rem;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.85rem;
    color: #1e293b;
    background: #fff;
    outline: none;
    transition: all 0.15s;
    font-family: inherit;
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    padding-right: 2.25rem;
}
.app-select:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
}
.app-select--error { border-color: #ef4444; }
.app-select--disabled { background: #f8fafc; color: #94a3b8; cursor: not-allowed; }
.form-error { font-size: 0.73rem; color: #ef4444; margin: 0; font-weight: 500; }
</style>
