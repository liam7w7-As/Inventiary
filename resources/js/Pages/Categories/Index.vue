<template>
    <AppLayout>
        <template #title>Categorías</template>

        <div class="categories-page">
            <!-- Header -->
            <div class="page-header">
                <div>
                    <h2 class="page-title">Gestión de Categorías</h2>
                    <p class="page-desc">Organiza tus productos en categorías</p>
                </div>
                <button @click="openCreateModal" class="btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                    Nueva Categoría
                </button>
            </div>

            <!-- Filters -->
            <div class="page-filters">
                <div class="filter-field">
                    <input
                        v-model="filters.search"
                        type="text"
                        placeholder="Buscar por nombre..."
                        class="filter-input"
                        @input="applyFilters"
                    />
                </div>
                <div class="filter-field" style="max-width: 200px;">
                    <select v-model="filters.is_active" class="filter-select" @change="applyFilters">
                        <option value="">Todos los estados</option>
                        <option value="1">Activas</option>
                        <option value="0">Inactivas</option>
                    </select>
                </div>
                <button v-if="hasFilters" class="filter-clear" @click="clearFilters">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    Limpiar
                </button>
            </div>

            <!-- Table -->
            <AppTable :columns="columns" :rows="categories.data">
                <template #cell(name)="{ row }">
                    <span class="font-semibold text-slate-800">{{ row.name }}</span>
                </template>
                <template #cell(description)="{ row }">
                    <span class="text-slate-500" :title="row.description">
                        {{ row.description ? (row.description.length > 60 ? row.description.substring(0, 60) + '...' : row.description) : '—' }}
                    </span>
                </template>
                <template #cell(products_count)="{ row }">
                    <AppBadge variant="info">{{ row.products_count }}</AppBadge>
                </template>
                <template #cell(is_active)="{ row }">
                    <AppBadge :variant="row.is_active ? 'success' : 'danger'">
                        {{ row.is_active ? 'Activa' : 'Inactiva' }}
                    </AppBadge>
                </template>
                <template #cell(actions)="{ row }">
                    <div class="actions-group">
                        <button class="action-btn action-edit" title="Editar" @click="openEditModal(row)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                        </button>
                        <button class="action-btn action-toggle" :title="row.is_active ? 'Desactivar' : 'Activar'" @click="toggleCategory(row)">
                            <svg v-if="row.is_active" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                        <button class="action-btn action-delete" title="Eliminar" @click="confirmDelete(row)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                        </button>
                    </div>
                </template>
            </AppTable>

            <!-- Pagination -->
            <div v-if="categories.links && categories.last_page > 1" class="pagination">
                <template v-for="link in categories.links" :key="link.label">
                    <button
                        v-if="link.url"
                        class="pagination__btn"
                        :class="{ 'pagination__btn--active': link.active }"
                        @click="$inertia.visit(link.url)"
                        v-html="link.label"
                    ></button>
                    <span v-else class="pagination__disabled" v-html="link.label"></span>
                </template>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <AppModal :show="modal.show" :title="modal.isEdit ? 'Editar Categoría' : 'Nueva Categoría'" @close="modal.show = false">
            <form @submit.prevent="submitModal" id="categoryForm">
                <div class="form-grid">
                    <AppInput label="Nombre de la categoría" v-model="form.name" :error="form.errors.name" required />
                    <AppTextarea label="Descripción" v-model="form.description" :error="form.errors.description" :rows="3" />
                </div>
                
                <div class="form-toggles">
                    <label class="form-toggle">
                        <input type="checkbox" v-model="form.is_active" />
                        <span class="form-toggle__label">
                            <strong>Activa</strong>
                            <small>Esta categoría estará visible al crear productos</small>
                        </span>
                    </label>
                </div>
            </form>
            <template #footer>
                <AppButton variant="secondary" @click="modal.show = false">Cancelar</AppButton>
                <AppButton type="submit" form="categoryForm" :loading="form.processing">
                    {{ modal.isEdit ? 'Actualizar' : 'Crear' }} Categoría
                </AppButton>
            </template>
        </AppModal>

        <!-- Delete confirmation -->
        <ConfirmModal
            :show="deleteModal.show"
            title="Eliminar Categoría"
            :message="`¿Estás seguro de eliminar la categoría '${deleteModal.category?.name}'? Asegúrate de que no tenga productos asociados.`"
            @confirm="executeDelete"
            @cancel="deleteModal.show = false"
        />
    </AppLayout>
</template>

<script setup>
import { reactive, computed } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AppTable from '@/Components/AppTable.vue'
import AppBadge from '@/Components/AppBadge.vue'
import AppModal from '@/Components/AppModal.vue'
import AppInput from '@/Components/AppInput.vue'
import AppTextarea from '@/Components/AppTextarea.vue'
import AppButton from '@/Components/AppButton.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'

const props = defineProps({
    categories: Object,
    filters: Object,
})

const filters = reactive({
    search: props.filters?.search || '',
    is_active: props.filters?.is_active ?? '',
})

let debounceTimer = null

function applyFilters() {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(() => {
        router.get('/categories', {
            search: filters.search || undefined,
            is_active: filters.is_active !== '' ? filters.is_active : undefined,
        }, {
            preserveState: true,
            replace: true,
        })
    }, 300)
}

const hasFilters = computed(() => filters.search || filters.is_active !== '')

function clearFilters() {
    filters.search = ''
    filters.is_active = ''
    router.get('/categories', {}, { preserveState: true, replace: true })
}

const columns = [
    { key: 'name', label: 'Nombre', width: '25%' },
    { key: 'description', label: 'Descripción' },
    { key: 'products_count', label: 'Productos asociados', width: '150px' },
    { key: 'is_active', label: 'Estado', width: '100px' },
    { key: 'actions', label: 'Acciones', width: '120px' },
]

// Modal Logic
const modal = reactive({
    show: false,
    isEdit: false,
    id: null,
})

const form = useForm({
    name: '',
    description: '',
    is_active: true,
})

function openCreateModal() {
    modal.isEdit = false
    modal.id = null
    form.reset()
    form.clearErrors()
    modal.show = true
}

function openEditModal(category) {
    modal.isEdit = true
    modal.id = category.id
    form.name = category.name
    form.description = category.description || ''
    form.is_active = !!category.is_active
    form.clearErrors()
    modal.show = true
}

function submitModal() {
    if (modal.isEdit) {
        form.put(`/categories/${modal.id}`, {
            preserveScroll: true,
            onSuccess: () => { modal.show = false },
        })
    } else {
        form.post('/categories', {
            preserveScroll: true,
            onSuccess: () => { modal.show = false },
        })
    }
}

function toggleCategory(category) {
    router.patch(`/categories/${category.id}/toggle-status`, {}, { preserveState: true })
}

const deleteModal = reactive({ show: false, category: null })

function confirmDelete(category) {
    deleteModal.category = category
    deleteModal.show = true
}

function executeDelete() {
    router.delete(`/categories/${deleteModal.category.id}`, {
        preserveState: true,
        onSuccess: () => { deleteModal.show = false },
    })
}
</script>

<style scoped>
.page-header {
    display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem;
}
.page-title { font-size: 1.15rem; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -0.01em; }
.page-desc { font-size: 0.78rem; color: #64748b; margin: 0.15rem 0 0; }
.btn-primary {
    display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.55rem 1rem;
    background: linear-gradient(135deg, #3b82f6, #6366f1); color: #fff;
    border-radius: 8px; border: none; font-size: 0.82rem; font-weight: 600; text-decoration: none;
    transition: all 0.15s; white-space: nowrap; cursor: pointer;
}
.btn-primary:hover { box-shadow: 0 4px 14px rgba(59,130,246,0.3); transform: translateY(-1px); }

/* Filters */
.page-filters { display: flex; align-items: center; gap: 0.6rem; margin-bottom: 1rem; flex-wrap: wrap; }
.filter-field { flex: 1; min-width: 160px; }
.filter-input, .filter-select {
    width: 100%; padding: 0.5rem 0.75rem; border: 1.5px solid #e2e8f0; border-radius: 8px;
    font-size: 0.82rem; color: #334155; background: #fff; outline: none;
    transition: border-color 0.15s; font-family: inherit;
}
.filter-input:focus, .filter-select:focus { border-color: #3b82f6; }
.filter-input::placeholder { color: #94a3b8; }
.filter-clear {
    display: inline-flex; align-items: center; gap: 0.3rem; padding: 0.45rem 0.75rem;
    border: 1.5px solid #e2e8f0; border-radius: 8px; background: #fff; color: #64748b;
    font-size: 0.78rem; font-weight: 500; cursor: pointer; transition: all 0.15s; font-family: inherit;
}
.filter-clear:hover { border-color: #cbd5e1; background: #f8fafc; }

.font-semibold { font-weight: 600; }
.text-slate-800 { color: #1e293b; }
.text-slate-500 { color: #64748b; font-size: 0.8rem; }

/* Actions */
.actions-group { display: flex; gap: 0.3rem; }
.action-btn {
    padding: 0.3rem; border-radius: 6px; border: none; background: none; cursor: pointer;
    transition: all 0.15s; display: flex; align-items: center; justify-content: center;
    text-decoration: none;
}
.action-edit { color: #3b82f6; } .action-edit:hover { background: rgba(59,130,246,0.1); }
.action-toggle { color: #f59e0b; } .action-toggle:hover { background: rgba(245,158,11,0.1); }
.action-delete { color: #ef4444; } .action-delete:hover { background: rgba(239,68,68,0.1); }

/* Pagination */
.pagination { display: flex; align-items: center; justify-content: center; gap: 0.25rem; margin-top: 1rem; }
.pagination__btn {
    padding: 0.35rem 0.7rem; border-radius: 6px; border: 1px solid #e2e8f0; background: #fff;
    color: #475569; font-size: 0.78rem; cursor: pointer; transition: all 0.15s; font-family: inherit;
}
.pagination__btn:hover { background: #f1f5f9; }
.pagination__btn--active { background: #3b82f6; color: #fff; border-color: #3b82f6; }
.pagination__disabled { padding: 0.35rem 0.7rem; color: #cbd5e1; font-size: 0.78rem; }

/* Modal form */
.form-grid { display: flex; flex-direction: column; gap: 1rem; }
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
</style>
