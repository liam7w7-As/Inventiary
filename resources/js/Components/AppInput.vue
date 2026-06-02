<template>
    <div class="form-group">
        <label v-if="label" :for="id" class="form-label">
            {{ label }}
            <span v-if="required" class="form-label__req">*</span>
        </label>
        <div class="form-input-wrap">
            <input
                :id="id"
                :type="type"
                :value="modelValue"
                :placeholder="placeholder"
                :disabled="disabled"
                :required="required"
                class="app-input"
                :class="{ 'app-input--error': error, 'app-input--disabled': disabled }"
                @input="$emit('update:modelValue', $event.target.value)"
            />
        </div>
        <p v-if="error" class="form-error">{{ error }}</p>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    label: String,
    modelValue: [String, Number],
    type: { type: String, default: 'text' },
    error: String,
    required: Boolean,
    disabled: Boolean,
    placeholder: String,
})

defineEmits(['update:modelValue'])

const id = computed(() => 'input-' + (props.label || '').toLowerCase().replace(/\s+/g, '-') + '-' + Math.random().toString(36).slice(2, 6))
</script>

<style scoped>
.form-group { display: flex; flex-direction: column; gap: 0.3rem; }
.form-label { font-size: 0.8rem; font-weight: 600; color: #334155; }
.form-label__req { color: #ef4444; margin-left: 1px; }
.app-input {
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
}
.app-input:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
}
.app-input--error { border-color: #ef4444; }
.app-input--error:focus { box-shadow: 0 0 0 3px rgba(239,68,68,0.1); }
.app-input--disabled { background: #f8fafc; color: #94a3b8; cursor: not-allowed; }
.app-input::placeholder { color: #94a3b8; }
.form-error { font-size: 0.73rem; color: #ef4444; margin: 0; font-weight: 500; }
</style>
