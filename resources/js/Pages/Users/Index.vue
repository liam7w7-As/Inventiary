<template>
    <AppLayout>
        <template #title>Usuarios</template>

        <div class="users-page">
            <!-- Header -->
            <div class="users-header">
                <div>
                    <h2 class="users-header__title">Gestión de Usuarios</h2>
                    <p class="users-header__desc">Administra las cuentas del sistema</p>
                </div>
                <a href="/users/create" @click.prevent="$inertia.visit('/users/create')" class="users-header__btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                    Nuevo Usuario
                </a>
            </div>

            <!-- Filters -->
            <div class="users-filters">
                <div class="users-filters__field">
                    <input
                        v-model="filters.search"
                        type="text"
                        placeholder="Buscar por nombre o usuario..."
                        class="users-filters__input"
                        @input="applyFilters"
                    />
                </div>
                <div class="users-filters__field">
                    <select v-model="filters.role_id" class="users-filters__select" @change="applyFilters">
                        <option value="">Todos los roles</option>
                        <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.display_name }}</option>
                    </select>
                </div>
                <div class="users-filters__field">
                    <select v-model="filters.is_active" class="users-filters__select" @change="applyFilters">
                        <option value="">Todos los estados</option>
                        <option value="1">Activos</option>
                        <option value="0">Inactivos</option>
                    </select>
                </div>
                <button v-if="hasFilters" class="users-filters__clear" @click="clearFilters">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    Limpiar
                </button>
            </div>

            <!-- Table -->
            <AppTable :columns="columns" :rows="users.data">
                <template #cell(photo)="{ row }">
                    <div class="user-avatar-sm">
                        <img v-if="row.photo" :src="`/storage/${row.photo}`" :alt="row.name" />
                        <span v-else>{{ row.name?.charAt(0)?.toUpperCase() }}</span>
                    </div>
                </template>
                <template #cell(name)="{ row }">
                    <span class="user-name">{{ row.name }}</span>
                </template>
                <template #cell(username)="{ row }">
                    <span class="user-username">@{{ row.username }}</span>
                </template>
                <template #cell(role)="{ row }">
                    <AppBadge :variant="roleBadge(row.role?.name)">
                        {{ row.role?.display_name || row.role?.name }}
                    </AppBadge>
                </template>
                <template #cell(branch)="{ row }">
                    {{ row.branch?.name || 'Global' }}
                </template>
                <template #cell(is_active)="{ row }">
                    <AppBadge :variant="row.is_active ? 'success' : 'danger'">
                        {{ row.is_active ? 'Activo' : 'Inactivo' }}
                    </AppBadge>
                </template>
                <template #cell(actions)="{ row }">
                    <div class="user-actions">
                        <a :href="`/users/${row.id}/edit`" @click.prevent="$inertia.visit(`/users/${row.id}/edit`)" class="user-actions__btn user-actions__btn--edit" title="Editar">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                        </a>
                        <button class="user-actions__btn user-actions__btn--toggle" :title="row.is_active ? 'Desactivar' : 'Activar'" @click="toggleUser(row)">
                            <svg v-if="row.is_active" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                        <button class="user-actions__btn user-actions__btn--delete" title="Eliminar" @click="confirmDelete(row)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                        </button>
                    </div>
                </template>
            </AppTable>

            <!-- Pagination -->
            <div v-if="users.links && users.last_page > 1" class="users-pagination">
                <template v-for="link in users.links" :key="link.label">
                    <button
                        v-if="link.url"
                        class="users-pagination__btn"
                        :class="{ 'users-pagination__btn--active': link.active }"
                        @click="$inertia.visit(link.url)"
                        v-html="link.label"
                    ></button>
                    <span v-else class="users-pagination__disabled" v-html="link.label"></span>
                </template>
            </div>
        </div>

        <!-- Delete confirmation -->
        <ConfirmModal
            :show="deleteModal.show"
            title="Eliminar Usuario"
            :message="`¿Estás seguro de eliminar al usuario '${deleteModal.user?.name}'? Esta acción no se puede deshacer.`"
            @confirm="executeDelete"
            @cancel="deleteModal.show = false"
        />
    </AppLayout>
</template>

<script setup>
import { reactive, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AppTable from '@/Components/AppTable.vue'
import AppBadge from '@/Components/AppBadge.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'

const props = defineProps({
    users: Object,
    roles: Array,
    filters: Object,
})

const filters = reactive({
    search: props.filters?.search || '',
    role_id: props.filters?.role_id || '',
    is_active: props.filters?.is_active ?? '',
})

let debounceTimer = null

function applyFilters() {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(() => {
        router.get('/users', {
            search: filters.search || undefined,
            role_id: filters.role_id || undefined,
            is_active: filters.is_active !== '' ? filters.is_active : undefined,
        }, {
            preserveState: true,
            replace: true,
        })
    }, 300)
}

const hasFilters = computed(() => filters.search || filters.role_id || filters.is_active !== '')

function clearFilters() {
    filters.search = ''
    filters.role_id = ''
    filters.is_active = ''
    router.get('/users', {}, { preserveState: true, replace: true })
}

const columns = [
    { key: 'photo', label: '', width: '50px' },
    { key: 'name', label: 'Nombre' },
    { key: 'username', label: 'Usuario' },
    { key: 'role', label: 'Rol' },
    { key: 'branch', label: 'Sucursal' },
    { key: 'is_active', label: 'Estado' },
    { key: 'actions', label: 'Acciones', width: '120px' },
]

function roleBadge(name) {
    return { admin: 'info', encargado: 'warning', vendedor: 'gray' }[name] || 'gray'
}

function toggleUser(user) {
    router.patch(`/users/${user.id}/toggle-status`, {}, { preserveState: true })
}

const deleteModal = reactive({ show: false, user: null })

function confirmDelete(user) {
    deleteModal.user = user
    deleteModal.show = true
}

function executeDelete() {
    router.delete(`/users/${deleteModal.user.id}`, {
        preserveState: true,
        onSuccess: () => { deleteModal.show = false },
    })
}
</script>

<style scoped>
.users-page { }

.users-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.25rem;
}
.users-header__title {
    font-size: 1.15rem;
    font-weight: 800;
    color: #0f172a;
    margin: 0;
    letter-spacing: -0.01em;
}
.users-header__desc {
    font-size: 0.78rem;
    color: #64748b;
    margin: 0.15rem 0 0;
}
.users-header__btn {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.55rem 1rem;
    background: linear-gradient(135deg, #3b82f6, #6366f1);
    color: #fff;
    border-radius: 8px;
    font-size: 0.82rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.15s;
    white-space: nowrap;
}
.users-header__btn:hover {
    box-shadow: 0 4px 14px rgba(59,130,246,0.3);
    transform: translateY(-1px);
}

/* Filters */
.users-filters {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
}
.users-filters__field { flex: 1; min-width: 160px; max-width: 260px; }
.users-filters__input,
.users-filters__select {
    width: 100%;
    padding: 0.5rem 0.75rem;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.82rem;
    color: #334155;
    background: #fff;
    outline: none;
    transition: border-color 0.15s;
    font-family: inherit;
}
.users-filters__input:focus,
.users-filters__select:focus { border-color: #3b82f6; }
.users-filters__input::placeholder { color: #94a3b8; }
.users-filters__clear {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.45rem 0.75rem;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    background: #fff;
    color: #64748b;
    font-size: 0.78rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s;
    font-family: inherit;
}
.users-filters__clear:hover { border-color: #cbd5e1; background: #f8fafc; }

/* Avatar */
.user-avatar-sm {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 700;
    font-size: 0.72rem;
    overflow: hidden;
    flex-shrink: 0;
}
.user-avatar-sm img { width: 100%; height: 100%; object-fit: cover; }

.user-name { font-weight: 600; color: #1e293b; }
.user-username { color: #64748b; font-size: 0.8rem; }

/* Actions */
.user-actions { display: flex; gap: 0.3rem; }
.user-actions__btn {
    padding: 0.3rem;
    border-radius: 6px;
    border: none;
    background: none;
    cursor: pointer;
    transition: all 0.15s;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
}
.user-actions__btn--edit { color: #3b82f6; }
.user-actions__btn--edit:hover { background: rgba(59,130,246,0.1); }
.user-actions__btn--toggle { color: #f59e0b; }
.user-actions__btn--toggle:hover { background: rgba(245,158,11,0.1); }
.user-actions__btn--delete { color: #ef4444; }
.user-actions__btn--delete:hover { background: rgba(239,68,68,0.1); }

/* Pagination */
.users-pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.25rem;
    margin-top: 1rem;
}
.users-pagination__btn {
    padding: 0.35rem 0.7rem;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #475569;
    font-size: 0.78rem;
    cursor: pointer;
    transition: all 0.15s;
    font-family: inherit;
}
.users-pagination__btn:hover { background: #f1f5f9; }
.users-pagination__btn--active {
    background: #3b82f6;
    color: #fff;
    border-color: #3b82f6;
}
.users-pagination__disabled {
    padding: 0.35rem 0.7rem;
    color: #cbd5e1;
    font-size: 0.78rem;
}
</style>
