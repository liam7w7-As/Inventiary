<template>
    <AppLayout>
        <template #title>Configuración del Sistema</template>

        <form @submit.prevent="submit" class="settings-page" enctype="multipart/form-data">
            <!-- Section 1: System data -->
            <div class="settings-section">
                <div class="settings-section__header">
                    <h2 class="settings-section__title">Datos del Sistema</h2>
                    <p class="settings-section__desc">Información general del negocio</p>
                </div>
                <div class="settings-section__body">
                    <div class="settings-grid">
                        <AppInput label="Nombre del sistema" v-model="form.name" :error="form.errors.name" required />
                        <AppInput label="Alias / Nombre en interfaz" v-model="form.alias" :error="form.errors.alias" required />
                        <AppInput label="Actividad del negocio" v-model="form.activity" :error="form.errors.activity" />
                        <AppInput label="Moneda" v-model="form.currency" :error="form.errors.currency" placeholder="BOB" required />
                    </div>

                    <!-- Logo -->
                    <div class="settings-logo">
                        <label class="form-label">Logo del sistema</label>
                        <div class="settings-logo__wrap">
                            <div class="settings-logo__preview">
                                <img v-if="logoPreview" :src="logoPreview" alt="Logo" />
                                <div v-else class="settings-logo__placeholder">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                                </div>
                            </div>
                            <div class="settings-logo__actions">
                                <label class="settings-logo__btn">
                                    <input type="file" accept="image/*" @change="handleLogo" class="sr-only" />
                                    Cambiar logo
                                </label>
                                <span class="settings-logo__hint">PNG, JPG. Máx 2MB.</span>
                            </div>
                        </div>
                        <p v-if="form.errors.logo" class="form-error">{{ form.errors.logo }}</p>
                    </div>
                </div>
            </div>

            <!-- Section 2: Schedule -->
            <div class="settings-section">
                <div class="settings-section__header">
                    <h2 class="settings-section__title">Horarios de Acceso</h2>
                    <p class="settings-section__desc">Define el rango horario en que cada rol puede ingresar al sistema</p>
                </div>
                <div class="settings-section__body">
                    <div class="settings-schedule">
                        <div class="settings-schedule__group">
                            <span class="settings-schedule__label">Administrador</span>
                            <div class="settings-schedule__times">
                                <AppInput label="Hora inicio" type="time" v-model="form.admin_hour_start" :error="form.errors.admin_hour_start" required />
                                <AppInput label="Hora fin" type="time" v-model="form.admin_hour_end" :error="form.errors.admin_hour_end" required />
                            </div>
                        </div>
                        <div class="settings-schedule__group">
                            <span class="settings-schedule__label">Encargado</span>
                            <div class="settings-schedule__times">
                                <AppInput label="Hora inicio" type="time" v-model="form.encargado_hour_start" :error="form.errors.encargado_hour_start" required />
                                <AppInput label="Hora fin" type="time" v-model="form.encargado_hour_end" :error="form.errors.encargado_hour_end" required />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 3: Print -->
            <div class="settings-section">
                <div class="settings-section__header">
                    <h2 class="settings-section__title">Impresión</h2>
                    <p class="settings-section__desc">Formato de impresión predeterminado para tickets y reportes</p>
                </div>
                <div class="settings-section__body">
                    <div style="max-width: 280px;">
                        <AppSelect
                            label="Formato de impresión"
                            v-model="form.print_format"
                            :options="printOptions"
                            :error="form.errors.print_format"
                            required
                        />
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="settings-actions">
                <AppButton type="submit" :loading="form.processing">
                    Guardar cambios
                </AppButton>
            </div>
        </form>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AppInput from '@/Components/AppInput.vue'
import AppSelect from '@/Components/AppSelect.vue'
import AppButton from '@/Components/AppButton.vue'

const props = defineProps({ setting: Object })

const form = useForm({
    _method: 'PUT',
    name: props.setting.name || '',
    alias: props.setting.alias || '',
    activity: props.setting.activity || '',
    currency: props.setting.currency || 'BOB',
    admin_hour_start: (props.setting.admin_hour_start || '00:00').substring(0, 5),
    admin_hour_end: (props.setting.admin_hour_end || '23:59').substring(0, 5),
    encargado_hour_start: (props.setting.encargado_hour_start || '08:00').substring(0, 5),
    encargado_hour_end: (props.setting.encargado_hour_end || '20:00').substring(0, 5),
    print_format: props.setting.print_format || '58mm',
    logo: null,
})

const printOptions = [
    { value: '58mm', label: '58mm (Ticket pequeño)' },
    { value: '80mm', label: '80mm (Ticket estándar)' },
    { value: 'A4', label: 'A4 (Hoja completa)' },
]

const logoPreview = ref(
    props.setting.logo ? `/storage/${props.setting.logo}` : null
)

function handleLogo(e) {
    const file = e.target.files[0]
    if (file) {
        form.logo = file
        logoPreview.value = URL.createObjectURL(file)
    }
}

function submit() {
    form.post('/settings', {
        forceFormData: true,
        preserveScroll: true,
    })
}
</script>

<style scoped>
.settings-page { max-width: 780px; }

.settings-section {
    background: #fff;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    margin-bottom: 1.25rem;
    overflow: hidden;
}

.settings-section__header {
    padding: 1.25rem 1.5rem 0;
}
.settings-section__title {
    font-size: 1rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 0.2rem;
}
.settings-section__desc {
    font-size: 0.78rem;
    color: #64748b;
    margin: 0;
}
.settings-section__body {
    padding: 1.25rem 1.5rem 1.5rem;
}

.settings-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

/* Logo */
.settings-logo { margin-top: 1.25rem; }
.settings-logo__wrap {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-top: 0.5rem;
}
.settings-logo__preview {
    width: 72px;
    height: 72px;
    border-radius: 12px;
    border: 2px dashed #e2e8f0;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    background: #f8fafc;
}
.settings-logo__preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.settings-logo__placeholder { color: #cbd5e1; }
.settings-logo__actions { display: flex; flex-direction: column; gap: 0.3rem; }
.settings-logo__btn {
    display: inline-block;
    padding: 0.4rem 0.85rem;
    background: #f1f5f9;
    border-radius: 6px;
    font-size: 0.78rem;
    font-weight: 600;
    color: #475569;
    cursor: pointer;
    transition: all 0.15s;
}
.settings-logo__btn:hover { background: #e2e8f0; }
.settings-logo__hint { font-size: 0.7rem; color: #94a3b8; }
.sr-only { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0,0,0,0); border: 0; }

/* Schedule */
.settings-schedule { display: flex; flex-direction: column; gap: 1.25rem; }
.settings-schedule__group {
    border: 1px solid #f1f5f9;
    border-radius: 10px;
    padding: 1rem 1.25rem;
    background: #fafbfc;
}
.settings-schedule__label {
    display: block;
    font-size: 0.82rem;
    font-weight: 600;
    color: #334155;
    margin-bottom: 0.75rem;
}
.settings-schedule__times {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

/* Actions */
.settings-actions {
    display: flex;
    justify-content: flex-end;
    padding-top: 0.25rem;
}

.form-label { font-size: 0.8rem; font-weight: 600; color: #334155; display: block; margin-bottom: 0.3rem; }
.form-error { font-size: 0.73rem; color: #ef4444; margin: 0.25rem 0 0; font-weight: 500; }

@media (max-width: 640px) {
    .settings-grid { grid-template-columns: 1fr; }
    .settings-schedule__times { grid-template-columns: 1fr; }
}
</style>
