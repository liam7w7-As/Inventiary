<template>
    <AppLayout>
        <template #title>Nuevo Proveedor</template>

        <div class="form-page">
            <a href="/suppliers" @click.prevent="$inertia.visit('/suppliers')" class="back-link">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                Volver a proveedores
            </a>

            <form @submit.prevent="submit">
                <div class="form-section">
                    <div class="form-section__header">
                        <h2 class="form-section__title">Datos del Proveedor</h2>
                    </div>
                    <div class="form-section__body">
                        <div class="form-grid">
                            <AppInput label="Nombre o razón social" v-model="form.name" :error="form.errors.name" required />
                            <AppInput label="Persona de contacto" v-model="form.contact_name" :error="form.errors.contact_name" />
                            <AppInput label="Teléfono" v-model="form.phone" :error="form.errors.phone" />
                            <AppInput label="Correo electrónico" type="email" v-model="form.email" :error="form.errors.email" />
                        </div>
                        
                        <div style="margin-top: 1rem;">
                            <AppInput label="Dirección" v-model="form.address" :error="form.errors.address" />
                        </div>
                        
                        <div style="margin-top: 1rem;">
                            <AppTextarea label="Notas adicionales" v-model="form.notes" :error="form.errors.notes" :rows="3" />
                        </div>

                        <div class="form-toggles">
                            <label class="form-toggle">
                                <input type="checkbox" v-model="form.is_active" />
                                <span class="form-toggle__label">
                                    <strong>Activo</strong>
                                    <small>El proveedor estará disponible para registrar compras</small>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <AppButton variant="secondary" @click="$inertia.visit('/suppliers')">Cancelar</AppButton>
                    <AppButton type="submit" :loading="form.processing">Guardar Proveedor</AppButton>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AppInput from '@/Components/AppInput.vue'
import AppTextarea from '@/Components/AppTextarea.vue'
import AppButton from '@/Components/AppButton.vue'

const form = useForm({
    name: '',
    contact_name: '',
    phone: '',
    email: '',
    address: '',
    notes: '',
    is_active: true,
})

function submit() {
    form.post('/suppliers')
}
</script>

<style scoped>
.form-page { max-width: 680px; }

.back-link {
    display: inline-flex; align-items: center; gap: 0.35rem; color: #64748b;
    font-size: 0.82rem; font-weight: 500; text-decoration: none; margin-bottom: 1rem;
    transition: color 0.15s;
}
.back-link:hover { color: #3b82f6; }

.form-section {
    background: #fff; border-radius: 14px; border: 1px solid #e2e8f0; margin-bottom: 1.25rem; overflow: hidden;
}
.form-section__header { padding: 1.25rem 1.5rem 0; }
.form-section__title { font-size: 1rem; font-weight: 700; color: #1e293b; margin: 0; }
.form-section__body { padding: 1.25rem 1.5rem 1.5rem; }

.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }

.form-toggles { margin-top: 1.5rem; }
.form-toggle {
    display: inline-flex; align-items: flex-start; gap: 0.65rem; cursor: pointer;
    padding: 0.65rem 0.85rem; border-radius: 8px; border: 1px solid #f1f5f9; transition: background 0.15s; width: 100%;
}
.form-toggle:hover { background: #f8fafc; }
.form-toggle input[type="checkbox"] {
    width: 18px; height: 18px; border-radius: 4px; accent-color: #3b82f6; margin-top: 2px; flex-shrink: 0; cursor: pointer;
}
.form-toggle__label { display: flex; flex-direction: column; }
.form-toggle__label strong { font-size: 0.82rem; color: #1e293b; font-weight: 600; }
.form-toggle__label small { font-size: 0.72rem; color: #64748b; }

.form-actions { display: flex; justify-content: flex-end; gap: 0.5rem; padding-top: 0.25rem; }

@media (max-width: 640px) {
    .form-grid { grid-template-columns: 1fr; }
}
</style>
