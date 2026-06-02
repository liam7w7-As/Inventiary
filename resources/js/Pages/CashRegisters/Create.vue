<template>
    <AppLayout>
        <template #title>Abrir Caja</template>

        <div class="form-page">
            <a href="/cash-registers" @click.prevent="$inertia.visit('/cash-registers')" class="back-link">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                Volver a Cajas
            </a>

            <div class="form-section">
                <div class="form-section__header">
                    <h2 class="form-section__title">Nueva Apertura de Caja</h2>
                </div>
                <div class="form-section__body">
                    <form @submit.prevent="submit">
                        <div class="form-grid">
                            <AppSelect label="Sucursal" v-model="form.branch_id" :options="branchOptions" :error="form.errors.branch_id" required @change="onBranchChange" />
                            <div>
                                <AppSelect label="Encargado" v-model="form.user_id" :options="encargadoOptions" :error="form.errors.user_id" required :disabled="!form.branch_id || loadingUsers" />
                                <p v-if="loadingUsers" class="field-hint">Cargando encargados...</p>
                                <p v-if="selectedUserHasOpen" class="field-error-hint">
                                    ⚠ Este encargado ya tiene una caja abierta
                                </p>
                            </div>
                        </div>
                        <div class="form-grid" style="margin-top: 1rem;">
                            <AppInput label="Saldo inicial (Bs.)" type="number" step="0.01" v-model="form.initial_balance" :error="form.errors.initial_balance" required />
                            <AppInput label="Límite de venta (Bs.)" type="number" step="0.01" v-model="form.sale_limit" :error="form.errors.sale_limit" placeholder="Sin límite (opcional)" />
                        </div>
                        <div style="margin-top: 1rem;">
                            <AppTextarea label="Notas" v-model="form.notes" :error="form.errors.notes" :rows="2" />
                        </div>

                        <div class="form-actions">
                            <AppButton variant="secondary" @click="$inertia.visit('/cash-registers')">Cancelar</AppButton>
                            <AppButton type="submit" :loading="form.processing" :disabled="selectedUserHasOpen">Abrir Caja</AppButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AppInput from '@/Components/AppInput.vue'
import AppSelect from '@/Components/AppSelect.vue'
import AppTextarea from '@/Components/AppTextarea.vue'
import AppButton from '@/Components/AppButton.vue'

const props = defineProps({ branches: Array, users: Array })

const branchOptions = computed(() => props.branches.map(b => ({ value: b.id, label: b.name })))

const branchUsers = ref([])
const loadingUsers = ref(false)

const encargadoOptions = computed(() => branchUsers.value.map(u => ({
    value: u.id,
    label: u.name + (u.has_open_register ? ' (Caja abierta)' : ''),
})))

const selectedUserHasOpen = computed(() => {
    if (!form.user_id) return false
    const u = branchUsers.value.find(u => u.id == form.user_id)
    return u?.has_open_register
})

const form = useForm({
    branch_id: '',
    user_id: '',
    initial_balance: '',
    sale_limit: '',
    notes: '',
})

async function onBranchChange() {
    form.user_id = ''
    branchUsers.value = []
    if (!form.branch_id) return

    loadingUsers.value = true
    try {
        const resp = await fetch(`/cash-registers/users-by-branch/${form.branch_id}`)
        branchUsers.value = await resp.json()
    } catch (e) {
        branchUsers.value = []
    } finally {
        loadingUsers.value = false
    }
}

function submit() {
    form.post('/cash-registers')
}
</script>

<style scoped>
.form-page { max-width: 700px; padding-bottom: 2rem; }
.back-link { display: inline-flex; align-items: center; gap: 0.35rem; color: #64748b; font-size: 0.82rem; font-weight: 500; text-decoration: none; margin-bottom: 1rem; transition: color 0.15s; }
.back-link:hover { color: #3b82f6; }
.form-section { background: #fff; border-radius: 14px; border: 1px solid #e2e8f0; overflow: hidden; }
.form-section__header { padding: 1.25rem 1.5rem 0.5rem; border-bottom: 1px solid #f1f5f9; }
.form-section__title { font-size: 0.95rem; font-weight: 700; color: #334155; margin: 0; }
.form-section__body { padding: 1.25rem 1.5rem 1.5rem; }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.form-actions { display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1.5rem; }
.field-hint { font-size: 0.75rem; color: #94a3b8; margin-top: 0.25rem; }
.field-error-hint { font-size: 0.78rem; color: #ef4444; margin-top: 0.25rem; font-weight: 500; }
@media (max-width: 640px) { .form-grid { grid-template-columns: 1fr; } }
</style>
