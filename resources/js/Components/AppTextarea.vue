<template>
    <div class="form-group">
        <label v-if="label" :for="id" class="form-label">
            {{ label }}
            <span v-if="required" class="form-label__req">*</span>
        </label>
        <textarea
            :id="id"
            :value="modelValue"
            :placeholder="placeholder"
            :disabled="disabled"
            :required="required"
            :rows="rows"
            class="app-textarea"
            :class="{ 'app-textarea--error': error, 'app-textarea--disabled': disabled }"
            @input="$emit('update:modelValue', $event.target.value)"
        ></textarea>
        <p v-if="error" class="form-error">{{ error }}</p>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    label: String,
    modelValue: String,
    error: String,
    required: Boolean,
    disabled: Boolean,
    placeholder: String,
    rows: { type: Number, default: 3 },
})

defineEmits(['update:modelValue'])

const id = computed(() => 'textarea-' + Math.random().toString(36).slice(2, 6))
</script>

<style scoped>
.form-group { display: flex; flex-direction: column; gap: 0.3rem; }
.form-label { font-size: 0.8rem; font-weight: 600; color: #334155; }
.form-label__req { color: #ef4444; margin-left: 1px; }
.app-textarea {
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
    resize: vertical;
    min-height: 60px;
}
.app-textarea:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
}
.app-textarea--error { border-color: #ef4444; }
.app-textarea--disabled { background: #f8fafc; color: #94a3b8; cursor: not-allowed; }
.app-textarea::placeholder { color: #94a3b8; }
.form-error { font-size: 0.73rem; color: #ef4444; margin: 0; font-weight: 500; }
</style>
