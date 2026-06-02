<template>
    <AppLayout>
        <template #title>Nuevo Usuario</template>

        <div class="user-form-page">
            <!-- Back link -->
            <a href="/users" @click.prevent="$inertia.visit('/users')" class="user-form__back">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                Volver a usuarios
            </a>

            <form @submit.prevent="submit" enctype="multipart/form-data">
                <!-- Section 1: User data -->
                <div class="form-section">
                    <div class="form-section__header">
                        <h2 class="form-section__title">Datos del Usuario</h2>
                    </div>
                    <div class="form-section__body">
                        <div class="form-grid">
                            <AppInput label="Nombre completo" v-model="form.name" :error="form.errors.name" required />
                            <AppInput label="Usuario (username)" v-model="form.username" :error="form.errors.username" required />
                            <AppInput label="Email" type="email" v-model="form.email" :error="form.errors.email" placeholder="Opcional" />
                            <div></div>
                            <AppInput label="Contraseña" type="password" v-model="form.password" :error="form.errors.password" required />
                            <AppInput label="Confirmar contraseña" type="password" v-model="form.password_confirmation" :error="form.errors.password_confirmation" required />
                        </div>

                        <!-- Photo -->
                        <div class="form-photo">
                            <label class="form-label">Foto de perfil</label>
                            <div class="form-photo__wrap">
                                <div class="form-photo__preview">
                                    <img v-if="photoPreview" :src="photoPreview" alt="Foto" />
                                    <div v-else class="form-photo__placeholder">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                    </div>
                                </div>
                                <label class="form-photo__btn">
                                    <input type="file" accept="image/*" @change="handlePhoto" class="sr-only" />
                                    Seleccionar foto
                                </label>
                            </div>
                            <p v-if="form.errors.photo" class="form-error">{{ form.errors.photo }}</p>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Permissions -->
                <div class="form-section">
                    <div class="form-section__header">
                        <h2 class="form-section__title">Acceso y Permisos</h2>
                    </div>
                    <div class="form-section__body">
                        <div class="form-grid">
                            <AppSelect label="Rol" v-model="form.role_id" :options="roleOptions" :error="form.errors.role_id" required />
                            <AppSelect v-if="showBranch" label="Sucursal" v-model="form.branch_id" :options="branchOptions" :error="form.errors.branch_id" :required="isEncargado" />
                        </div>

                        <div class="form-toggles">
                            <label class="form-toggle">
                                <input type="checkbox" v-model="form.is_active" />
                                <span class="form-toggle__label">
                                    <strong>Activo</strong>
                                    <small>El usuario puede iniciar sesión</small>
                                </span>
                            </label>
                            <label class="form-toggle">
                                <input type="checkbox" v-model="form.bypass_schedule" />
                                <span class="form-toggle__label">
                                    <strong>Ignorar horario</strong>
                                    <small>Permite acceder fuera del horario configurado</small>
                                </span>
                            </label>
                            <label class="form-toggle">
                                <input type="checkbox" v-model="form.can_view_profits" />
                                <span class="form-toggle__label">
                                    <strong>Ver ganancias</strong>
                                    <small>Permite ver precios de compra y márgenes de ganancia</small>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="form-actions">
                    <AppButton variant="secondary" @click="$inertia.visit('/users')">Cancelar</AppButton>
                    <AppButton type="submit" :loading="form.processing">Crear Usuario</AppButton>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AppInput from '@/Components/AppInput.vue'
import AppSelect from '@/Components/AppSelect.vue'
import AppButton from '@/Components/AppButton.vue'

const props = defineProps({
    roles: Array,
    branches: Array,
})

const form = useForm({
    name: '',
    username: '',
    email: '',
    password: '',
    password_confirmation: '',
    role_id: '',
    branch_id: '',
    bypass_schedule: false,
    is_active: true,
    can_view_profits: false,
    photo: null,
})

const roleOptions = computed(() =>
    props.roles.map(r => ({ value: r.id, label: r.display_name }))
)

const branchOptions = computed(() =>
    props.branches.map(b => ({ value: b.id, label: b.name }))
)

const selectedRole = computed(() =>
    props.roles.find(r => r.id == form.role_id)
)

const isEncargado = computed(() => selectedRole.value?.name === 'encargado')
const showBranch = computed(() => selectedRole.value?.name !== 'admin')

watch(() => form.role_id, () => {
    if (selectedRole.value?.name === 'admin') {
        form.branch_id = ''
    }
})

const photoPreview = ref(null)

function handlePhoto(e) {
    const file = e.target.files[0]
    if (file) {
        form.photo = file
        photoPreview.value = URL.createObjectURL(file)
    }
}

function submit() {
    form.post('/users', { forceFormData: true })
}
</script>

<style scoped>
.user-form-page { max-width: 780px; }

.user-form__back {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    color: #64748b;
    font-size: 0.82rem;
    font-weight: 500;
    text-decoration: none;
    margin-bottom: 1rem;
    transition: color 0.15s;
}
.user-form__back:hover { color: #3b82f6; }

.form-section {
    background: #fff;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    margin-bottom: 1.25rem;
    overflow: hidden;
}
.form-section__header { padding: 1.25rem 1.5rem 0; }
.form-section__title { font-size: 1rem; font-weight: 700; color: #1e293b; margin: 0; }
.form-section__body { padding: 1.25rem 1.5rem 1.5rem; }

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

/* Photo */
.form-photo { margin-top: 1.25rem; }
.form-label { font-size: 0.8rem; font-weight: 600; color: #334155; display: block; margin-bottom: 0.4rem; }
.form-photo__wrap { display: flex; align-items: center; gap: 1rem; margin-top: 0.25rem; }
.form-photo__preview {
    width: 56px; height: 56px; border-radius: 50%;
    border: 2px dashed #e2e8f0; overflow: hidden;
    display: flex; align-items: center; justify-content: center;
    background: #f8fafc; flex-shrink: 0;
}
.form-photo__preview img { width: 100%; height: 100%; object-fit: cover; }
.form-photo__placeholder { color: #cbd5e1; }
.form-photo__btn {
    padding: 0.4rem 0.85rem; background: #f1f5f9; border-radius: 6px;
    font-size: 0.78rem; font-weight: 600; color: #475569;
    cursor: pointer; transition: all 0.15s;
}
.form-photo__btn:hover { background: #e2e8f0; }
.sr-only { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0,0,0,0); border: 0; }
.form-error { font-size: 0.73rem; color: #ef4444; margin: 0.25rem 0 0; font-weight: 500; }

/* Toggles */
.form-toggles { margin-top: 1.5rem; display: flex; flex-direction: column; gap: 0.75rem; }
.form-toggle {
    display: flex; align-items: flex-start; gap: 0.65rem; cursor: pointer;
    padding: 0.65rem 0.85rem; border-radius: 8px; border: 1px solid #f1f5f9;
    transition: background 0.15s;
}
.form-toggle:hover { background: #f8fafc; }
.form-toggle input[type="checkbox"] {
    width: 18px; height: 18px; border-radius: 4px; accent-color: #3b82f6;
    margin-top: 2px; flex-shrink: 0; cursor: pointer;
}
.form-toggle__label { display: flex; flex-direction: column; }
.form-toggle__label strong { font-size: 0.82rem; color: #1e293b; font-weight: 600; }
.form-toggle__label small { font-size: 0.72rem; color: #64748b; }

/* Actions */
.form-actions {
    display: flex; justify-content: flex-end; gap: 0.5rem; padding-top: 0.25rem;
}

@media (max-width: 640px) {
    .form-grid { grid-template-columns: 1fr; }
}
</style>
