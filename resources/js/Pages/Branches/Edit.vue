<template>
    <AppLayout>
        <template #title>Editar Sucursal: {{ branch.name }}</template>

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
                    </div>
                </div>

                <div class="form-actions">
                    <AppButton variant="secondary" @click="$inertia.visit('/branches')">Cancelar</AppButton>
                    <AppButton type="submit" :loading="form.processing">Guardar Cambios</AppButton>
                </div>
            </form>

            <div class="form-section" style="margin-top: 2rem;">
                <div class="form-section__header">
                    <h2 class="form-section__title">Encargado Asignado</h2>
                    <p class="form-section__desc">Usuario que gestiona esta sucursal</p>
                </div>
                <div class="form-section__body">
                    <div v-if="manager" class="manager-card">
                        <div class="manager-avatar">
                            {{ manager.name.charAt(0).toUpperCase() }}
                        </div>
                        <div class="manager-info">
                            <span class="manager-name">{{ manager.name }}</span>
                            <span class="manager-username">@{{ manager.username }}</span>
                        </div>
                    </div>
                    <div v-else class="manager-empty">
                        <span class="manager-empty__icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" x2="19" y1="8" y2="14"/><line x1="22" x2="16" y1="11" y2="11"/></svg>
                        </span>
                        <p>Sin encargado asignado</p>
                    </div>

                    <div class="manager-actions">
                        <a :href="`/users?role_id=2`" @click.prevent="$inertia.visit(`/users?role_id=2`)" class="manager-btn">
                            Gestionar usuarios
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                        </a>
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
import AppButton from '@/Components/AppButton.vue'

const props = defineProps({
    branch: Object,
    manager: Object,
})

const form = useForm({
    _method: 'PUT',
    name: props.branch.name || '',
    address: props.branch.address || '',
    phone: props.branch.phone || '',
    email: props.branch.email || '',
    is_active: !!props.branch.is_active,
})

function submit() {
    form.post(`/branches/${props.branch.id}`)
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
.form-section__desc { font-size: 0.78rem; color: #64748b; margin: 0.2rem 0 0; }
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

.form-actions { display: flex; justify-content: flex-end; gap: 0.5rem; padding-top: 0.25rem; }

/* Manager info */
.manager-card {
    display: flex; align-items: center; gap: 1rem; padding: 1rem;
    background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px;
}
.manager-avatar {
    width: 42px; height: 42px; border-radius: 50%; background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; font-size: 1rem;
}
.manager-info { display: flex; flex-direction: column; }
.manager-name { font-weight: 600; color: #1e293b; font-size: 0.95rem; }
.manager-username { font-size: 0.82rem; color: #64748b; }

.manager-empty {
    display: flex; align-items: center; gap: 0.75rem; padding: 1rem;
    background: #fffbeb; border: 1px solid #fde68a; border-radius: 10px; color: #d97706;
}
.manager-empty__icon { display: flex; }
.manager-empty p { margin: 0; font-size: 0.85rem; font-weight: 500; }

.manager-actions { margin-top: 1rem; }
.manager-btn {
    display: inline-flex; align-items: center; gap: 0.4rem; color: #3b82f6;
    font-size: 0.82rem; font-weight: 600; text-decoration: none; transition: all 0.15s;
}
.manager-btn:hover { color: #2563eb; gap: 0.5rem; }
</style>
