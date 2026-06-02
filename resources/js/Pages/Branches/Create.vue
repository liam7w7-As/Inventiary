<template>
    <AppLayout>
        <template #title>Nueva Sucursal</template>

        <div class="form-page">
            <a href="/branches" @click.prevent="$inertia.visit('/branches')" class="back-link">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                Volver a sucursales
            </a>

            <form @submit.prevent="submit">
                <div class="form-section">
                    <div class="form-section__header">
                        <h2 class="form-section__title">Datos de la Sucursal</h2>
                    </div>
                    <div class="form-section__body">
                        <div class="form-grid">
                            <AppInput label="Nombre de la sucursal" v-model="form.name" :error="form.errors.name" required />
                            <AppInput label="Teléfono" v-model="form.phone" :error="form.errors.phone" />
                            <AppInput label="Correo electrónico" type="email" v-model="form.email" :error="form.errors.email" />
                            <AppInput label="Dirección" v-model="form.address" :error="form.errors.address" />
                        </div>

                        <div class="form-toggles">
                            <label class="form-toggle">
                                <input type="checkbox" v-model="form.is_active" />
                                <span class="form-toggle__label">
                                    <strong>Activa</strong>
                                    <small>La sucursal operará normalmente</small>
                                </span>
                            </label>
                        </div>

                        <div class="info-alert">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                            <p>Para asignar un encargado a esta sucursal, crea o edita un usuario con rol <strong>Encargado</strong> y selecciona esta sucursal.</p>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <AppButton variant="secondary" @click="$inertia.visit('/branches')">Cancelar</AppButton>
                    <AppButton type="submit" :loading="form.processing">Guardar Sucursal</AppButton>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AppInput from '@/Components/AppInput.vue'
import AppButton from '@/Components/AppButton.vue'

const form = useForm({
    name: '',
    address: '',
    phone: '',
    email: '',
    is_active: true,
})

function submit() {
    form.post('/branches')
}
</script>

<style scoped>
.form-page { max-width: 600px; }

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

.form-grid { display: grid; grid-template-columns: 1fr; gap: 1rem; }

.form-toggles { margin-top: 1.5rem; }
.form-toggle {
    display: inline-flex; align-items: flex-start; gap: 0.65rem; cursor: pointer;
    padding: 0.65rem 0.85rem; border-radius: 8px; border: 1px solid #f1f5f9; transition: background 0.15s;
}
.form-toggle:hover { background: #f8fafc; }
.form-toggle input[type="checkbox"] {
    width: 18px; height: 18px; border-radius: 4px; accent-color: #3b82f6; margin-top: 2px; flex-shrink: 0; cursor: pointer;
}
.form-toggle__label { display: flex; flex-direction: column; }
.form-toggle__label strong { font-size: 0.82rem; color: #1e293b; font-weight: 600; }
.form-toggle__label small { font-size: 0.72rem; color: #64748b; }

.info-alert {
    display: flex; align-items: flex-start; gap: 0.75rem; background: #f0f9ff;
    border: 1px solid #bae6fd; padding: 1rem; border-radius: 8px; margin-top: 1.5rem; color: #0369a1;
}
.info-alert svg { flex-shrink: 0; margin-top: 0.1rem; }
.info-alert p { font-size: 0.82rem; margin: 0; line-height: 1.5; }

.form-actions { display: flex; justify-content: flex-end; gap: 0.5rem; padding-top: 0.25rem; }
</style>
