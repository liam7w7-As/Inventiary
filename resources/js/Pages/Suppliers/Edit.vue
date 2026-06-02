<template>
    <AppLayout>
        <template #title>Editar Proveedor: {{ supplier.name }}</template>

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
                    <AppButton type="submit" :loading="form.processing">Guardar Cambios</AppButton>
                </div>
            </form>

            <div class="form-section" style="margin-top: 2rem;">
                <div class="form-section__body">
                    <div class="info-alert">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                        <div>
                            <strong>Productos de este proveedor</strong>
                            <p>Para ver los productos asociados a este proveedor, puedes ir a la sección de productos y filtrar por proveedor.</p>
                            <a :href="`/products?supplier_id=${supplier.id}`" @click.prevent="$inertia.visit(`/products?supplier_id=${supplier.id}`)" class="info-link">
                                Ver productos
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AppInput from '@/Components/AppInput.vue'
import AppTextarea from '@/Components/AppTextarea.vue'
import AppButton from '@/Components/AppButton.vue'

const props = defineProps({
    supplier: Object,
})

const form = useForm({
    _method: 'PUT',
    name: props.supplier.name || '',
    contact_name: props.supplier.contact_name || '',
    phone: props.supplier.phone || '',
    email: props.supplier.email || '',
    address: props.supplier.address || '',
    notes: props.supplier.notes || '',
    is_active: !!props.supplier.is_active,
})

function submit() {
    form.post(`/suppliers/${props.supplier.id}`, {
        preserveScroll: true,
    })
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

.info-alert {
    display: flex; align-items: flex-start; gap: 0.85rem; background: #f8fafc;
    border: 1px solid #e2e8f0; padding: 1rem; border-radius: 8px; color: #475569;
}
.info-alert svg { flex-shrink: 0; margin-top: 0.1rem; color: #64748b; }
.info-alert strong { display: block; font-size: 0.9rem; color: #1e293b; margin-bottom: 0.25rem; }
.info-alert p { font-size: 0.82rem; margin: 0 0 0.75rem; line-height: 1.5; }
.info-link {
    display: inline-flex; font-size: 0.82rem; font-weight: 600; color: #3b82f6; text-decoration: none;
}
.info-link:hover { text-decoration: underline; }

@media (max-width: 640px) {
    .form-grid { grid-template-columns: 1fr; }
}
</style>
