<template>
    <AppLayout>
        <template #title>Clientes</template>

        <div class="clients-page">
            <div class="page-header">
                <div>
                    <h2 class="page-title">Gestión de Clientes</h2>
                    <p class="page-desc">Administra tu cartera de clientes</p>
                </div>
                <button @click="openCreateModal" class="btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                    Nuevo Cliente
                </button>
            </div>

            <!-- Filters -->
            <div class="page-filters">
                <div class="filter-field">
                    <input v-model="filters.search" type="text" placeholder="Buscar por nombre o teléfono..." class="filter-input" @input="applyFilters" />
                </div>
                <div v-if="isAdmin" class="filter-field" style="max-width: 220px;">
                    <select v-model="filters.branch_id" class="filter-select" @change="applyFilters">
                        <option value="">Todas las sucursales</option>
                        <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>
                </div>
                <button v-if="hasFilters" class="filter-clear" @click="clearFilters">Limpiar</button>
            </div>

            <!-- Table -->
            <AppTable :columns="columns" :rows="clients.data">
                <template #cell(name)="{ row }">
                    <span class="font-semibold text-slate-800">{{ row.name }}</span>
                </template>
                <template #cell(phone)="{ row }">{{ row.phone || '—' }}</template>
                <template #cell(email)="{ row }">{{ row.email || '—' }}</template>
                <template #cell(branch)="{ row }">{{ row.branch?.name }}</template>
                <template #cell(sales_count)="{ row }">
                    <AppBadge variant="info">{{ row.sales_count }}</AppBadge>
                </template>
                <template #cell(notes)="{ row }">
                    <span class="notes-cell" :title="row.notes">{{ row.notes ? (row.notes.length > 40 ? row.notes.substring(0, 40) + '...' : row.notes) : '—' }}</span>
                </template>
                <template #cell(actions)="{ row }">
                    <div class="actions-group">
                        <button class="action-btn action-edit" title="Editar" @click="openEditModal(row)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                        </button>
                        <button class="action-btn action-delete" title="Eliminar" @click="confirmDelete(row)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                        </button>
                    </div>
                </template>
            </AppTable>

            <!-- Pagination -->
            <div v-if="clients.links && clients.last_page > 1" class="pagination">
                <template v-for="link in clients.links" :key="link.label">
                    <button v-if="link.url" class="pagination__btn" :class="{ 'pagination__btn--active': link.active }" @click="$inertia.visit(link.url)" v-html="link.label"></button>
                    <span v-else class="pagination__disabled" v-html="link.label"></span>
                </template>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <AppModal :show="modal.show" :title="modal.isEdit ? 'Editar Cliente' : 'Nuevo Cliente'" @close="modal.show = false">
            <form @submit.prevent="submitModal" id="clientForm">
                <div class="form-stack">
                    <AppInput label="Nombre del cliente" v-model="form.name" :error="form.errors.name" required />
                    <AppInput label="Teléfono" v-model="form.phone" :error="form.errors.phone" />
                    <AppInput label="Correo electrónico" type="email" v-model="form.email" :error="form.errors.email" />
                    <AppSelect v-if="isAdmin" label="Sucursal" v-model="form.branch_id" :options="branchOptions" :error="form.errors.branch_id" required />
                    <AppTextarea label="Notas" v-model="form.notes" :error="form.errors.notes" :rows="2" />
                </div>
            </form>
            <template #footer>
                <AppButton variant="secondary" @click="modal.show = false">Cancelar</AppButton>
                <AppButton type="submit" form="clientForm" :loading="form.processing">
                    {{ modal.isEdit ? 'Actualizar' : 'Crear' }} Cliente
                </AppButton>
            </template>
        </AppModal>

        <ConfirmModal
            :show="deleteModal.show"
            title="Eliminar Cliente"
            :message="`¿Estás seguro de eliminar al cliente '${deleteModal.client?.name}'? No debe tener ventas ni créditos.`"
            @confirm="executeDelete"
            @cancel="deleteModal.show = false"
        />
    </AppLayout>
</template>

<script setup>
import { reactive, computed } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AppTable from '@/Components/AppTable.vue'
import AppBadge from '@/Components/AppBadge.vue'
import AppModal from '@/Components/AppModal.vue'
import AppInput from '@/Components/AppInput.vue'
import AppSelect from '@/Components/AppSelect.vue'
import AppTextarea from '@/Components/AppTextarea.vue'
import AppButton from '@/Components/AppButton.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'

const props = defineProps({
    clients: Object,
    branches: Array,
    filters: Object,
})

const page = usePage()
const isAdmin = computed(() => page.props.auth.user.role === 'admin')

const branchOptions = computed(() => props.branches.map(b => ({ value: b.id, label: b.name })))

const filters = reactive({
    search: props.filters?.search || '',
    branch_id: props.filters?.branch_id || '',
})

let debounceTimer = null
function applyFilters() {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(() => {
        let params = { ...filters }
        for (const key in params) { if (!params[key]) delete params[key] }
        router.get('/clients', params, { preserveState: true, replace: true })
    }, 300)
}

const hasFilters = computed(() => filters.search || filters.branch_id)
function clearFilters() {
    filters.search = ''
    filters.branch_id = ''
    router.get('/clients', {}, { preserveState: true, replace: true })
}

const columns = computed(() => {
    const cols = [
        { key: 'name', label: 'Nombre' },
        { key: 'phone', label: 'Teléfono' },
        { key: 'email', label: 'Correo' },
    ]
    if (isAdmin.value) cols.push({ key: 'branch', label: 'Sucursal' })
    cols.push({ key: 'sales_count', label: 'Ventas' })
    cols.push({ key: 'notes', label: 'Notas' })
    cols.push({ key: 'actions', label: 'Acciones', width: '100px' })
    return cols
})

// Modal
const modal = reactive({ show: false, isEdit: false, id: null })
const form = useForm({
    name: '',
    phone: '',
    email: '',
    branch_id: '',
    notes: '',
})

function openCreateModal() {
    modal.isEdit = false
    modal.id = null
    form.reset()
    form.clearErrors()
    // Default branch for encargado
    if (!isAdmin.value) form.branch_id = page.props.auth.user.branch_id
    modal.show = true
}

function openEditModal(client) {
    modal.isEdit = true
    modal.id = client.id
    form.name = client.name
    form.phone = client.phone || ''
    form.email = client.email || ''
    form.branch_id = client.branch_id || ''
    form.notes = client.notes || ''
    form.clearErrors()
    modal.show = true
}

function submitModal() {
    if (modal.isEdit) {
        form.put(`/clients/${modal.id}`, { preserveScroll: true, onSuccess: () => { modal.show = false } })
    } else {
        form.post('/clients', { preserveScroll: true, onSuccess: () => { modal.show = false } })
    }
}

const deleteModal = reactive({ show: false, client: null })
function confirmDelete(client) { deleteModal.client = client; deleteModal.show = true }
function executeDelete() {
    router.delete(`/clients/${deleteModal.client.id}`, { preserveState: true, onSuccess: () => { deleteModal.show = false } })
}
</script>

<style scoped>
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; }
.page-title { font-size: 1.15rem; font-weight: 800; color: #0f172a; margin: 0; }
.page-desc { font-size: 0.78rem; color: #64748b; margin: 0.15rem 0 0; }
.btn-primary { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.55rem 1rem; background: linear-gradient(135deg, #3b82f6, #6366f1); color: #fff; border-radius: 8px; border: none; font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: all 0.15s; white-space: nowrap; }
.btn-primary:hover { box-shadow: 0 4px 14px rgba(59,130,246,0.3); transform: translateY(-1px); }

.page-filters { display: flex; align-items: center; gap: 0.6rem; margin-bottom: 1rem; flex-wrap: wrap; }
.filter-field { flex: 1; min-width: 160px; }
.filter-input, .filter-select { width: 100%; padding: 0.5rem 0.75rem; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 0.82rem; color: #334155; background: #fff; outline: none; transition: border-color 0.15s; font-family: inherit; }
.filter-input:focus, .filter-select:focus { border-color: #3b82f6; }
.filter-input::placeholder { color: #94a3b8; }
.filter-clear { display: inline-flex; align-items: center; padding: 0.5rem 0.75rem; border: 1.5px solid #e2e8f0; border-radius: 8px; background: #fff; color: #64748b; font-size: 0.78rem; font-weight: 500; cursor: pointer; transition: all 0.15s; }
.filter-clear:hover { border-color: #cbd5e1; background: #f8fafc; color: #334155; }

.font-semibold { font-weight: 600; }
.text-slate-800 { color: #1e293b; }
.notes-cell { display: block; max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: 0.8rem; color: #64748b; }

.actions-group { display: flex; gap: 0.3rem; }
.action-btn { padding: 0.3rem; border-radius: 6px; border: none; background: none; cursor: pointer; transition: all 0.15s; display: flex; align-items: center; justify-content: center; }
.action-edit { color: #3b82f6; } .action-edit:hover { background: rgba(59,130,246,0.1); }
.action-delete { color: #ef4444; } .action-delete:hover { background: rgba(239,68,68,0.1); }

.pagination { display: flex; align-items: center; justify-content: center; gap: 0.25rem; margin-top: 1rem; }
.pagination__btn { padding: 0.35rem 0.7rem; border-radius: 6px; border: 1px solid #e2e8f0; background: #fff; color: #475569; font-size: 0.78rem; cursor: pointer; transition: all 0.15s; font-family: inherit; }
.pagination__btn:hover { background: #f1f5f9; }
.pagination__btn--active { background: #3b82f6; color: #fff; border-color: #3b82f6; }
.pagination__disabled { padding: 0.35rem 0.7rem; color: #cbd5e1; font-size: 0.78rem; }

.form-stack { display: flex; flex-direction: column; gap: 1rem; }
</style>
