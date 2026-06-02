<template>
    <AppLayout>
        <template #title>Historial de Movimientos</template>

        <div class="inventory-page">
            <div class="page-header">
                <div>
                    <h2 class="page-title">Control de Inventario</h2>
                    <p class="page-desc">Revisa el historial de todos los movimientos de stock</p>
                </div>
            </div>

            <!-- Tabs -->
            <div class="tabs">
                <a href="/inventory" @click.prevent="$inertia.visit('/inventory')" class="tab">Stock Actual</a>
                <a href="/inventory/movements" class="tab tab--active">Historial de movimientos</a>
            </div>

            <!-- Filters -->
            <div class="page-filters">
                <div class="filter-field">
                    <select v-model="filters.product_id" class="filter-select" @change="applyFilters">
                        <option value="">Todos los productos</option>
                        <option v-for="product in products" :key="product.id" :value="product.id">{{ product.name }}</option>
                    </select>
                </div>
                <div v-if="$page.props.auth.user.role === 'admin'" class="filter-field">
                    <select v-model="filters.branch_id" class="filter-select" @change="applyFilters">
                        <option value="">Todas las sucursales</option>
                        <option v-for="branch in branches" :key="branch.id" :value="branch.id">{{ branch.name }}</option>
                    </select>
                </div>
                <div class="filter-field">
                    <select v-model="filters.movement_type" class="filter-select" @change="applyFilters">
                        <option value="">Todos los tipos</option>
                        <option value="in">Ingreso</option>
                        <option value="out">Salida</option>
                        <option value="adjustment">Ajuste</option>
                        <option value="transfer_in">Transferencia Entrante</option>
                        <option value="transfer_out">Transferencia Saliente</option>
                    </select>
                </div>
                <div class="filter-field filter-date">
                    <input type="date" v-model="filters.date_from" class="filter-input" placeholder="Desde" @change="applyFilters" />
                    <span>a</span>
                    <input type="date" v-model="filters.date_to" class="filter-input" placeholder="Hasta" @change="applyFilters" />
                </div>
                <button v-if="hasFilters" class="filter-clear" @click="clearFilters">
                    Limpiar
                </button>
            </div>

            <!-- Table -->
            <AppTable :columns="columns" :rows="movements.data">
                <template #cell(created_at)="{ row }">
                    <div class="date-cell">
                        <span class="date">{{ new Date(row.created_at).toLocaleDateString() }}</span>
                        <span class="time">{{ new Date(row.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}</span>
                    </div>
                </template>
                <template #cell(product)="{ row }">
                    <span class="font-semibold text-slate-800">{{ row.product?.name }}</span>
                </template>
                <template #cell(branch)="{ row }">
                    {{ row.branch?.name }}
                </template>
                <template #cell(type)="{ row }">
                    <AppBadge :variant="getMovVariant(row.movement_type)">
                        {{ getMovLabel(row.movement_type) }}
                    </AppBadge>
                </template>
                <template #cell(quantity)="{ row }">
                    <span class="font-bold" :class="`text-${getMovVariant(row.movement_type)}`">
                        {{ ['in', 'transfer_in'].includes(row.movement_type) ? '+' : (['out', 'transfer_out'].includes(row.movement_type) ? '-' : '') }}{{ parseFloat(row.quantity) }}
                    </span>
                </template>
                <template #cell(stock_before)="{ row }">
                    {{ parseFloat(row.stock_before) }}
                </template>
                <template #cell(stock_after)="{ row }">
                    <strong>{{ parseFloat(row.stock_after) }}</strong>
                </template>
                <template #cell(user)="{ row }">
                    <span class="text-sm text-slate-500">{{ row.user?.name }}</span>
                </template>
                <template #cell(notes)="{ row }">
                    <span class="notes-cell" :title="row.notes">{{ row.notes || '—' }}</span>
                </template>
            </AppTable>

            <!-- Pagination -->
            <div v-if="movements.links && movements.last_page > 1" class="pagination">
                <template v-for="link in movements.links" :key="link.label">
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
    </AppLayout>
</template>

<script setup>
import { reactive, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AppTable from '@/Components/AppTable.vue'
import AppBadge from '@/Components/AppBadge.vue'

const props = defineProps({
    movements: Object,
    products: Array,
    branches: Array,
    filters: Object,
})

const page = usePage()
const isAdmin = computed(() => page.props.auth.user.role === 'admin')

const filters = reactive({
    product_id: props.filters?.product_id || '',
    branch_id: props.filters?.branch_id || '',
    movement_type: props.filters?.movement_type || '',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || '',
})

function applyFilters() {
    let params = { ...filters }
    for (const key in params) {
        if (!params[key]) delete params[key]
    }
    router.get('/inventory/movements', params, { preserveState: true, replace: true })
}

const hasFilters = computed(() => Object.values(filters).some(v => v !== ''))

function clearFilters() {
    filters.product_id = ''
    filters.branch_id = ''
    filters.movement_type = ''
    filters.date_from = ''
    filters.date_to = ''
    router.get('/inventory/movements', {}, { preserveState: true, replace: true })
}

const columns = computed(() => {
    const cols = [
        { key: 'created_at', label: 'Fecha' },
        { key: 'product', label: 'Producto' },
    ]
    if (isAdmin.value) cols.push({ key: 'branch', label: 'Sucursal' })
    cols.push({ key: 'type', label: 'Tipo' })
    cols.push({ key: 'quantity', label: 'Cant.' })
    cols.push({ key: 'stock_before', label: 'Antes' })
    cols.push({ key: 'stock_after', label: 'Después' })
    cols.push({ key: 'user', label: 'Usuario' })
    cols.push({ key: 'notes', label: 'Notas', width: '20%' })
    return cols
})

function getMovVariant(type) {
    switch (type) {
        case 'in': return 'success'
        case 'out': return 'danger'
        case 'adjustment': return 'warning'
        case 'transfer_in': return 'info'
        case 'transfer_out': return 'gray'
        default: return 'gray'
    }
}

function getMovLabel(type) {
    switch (type) {
        case 'in': return 'Ingreso'
        case 'out': return 'Salida'
        case 'adjustment': return 'Ajuste'
        case 'transfer_in': return 'Transf. Entrante'
        case 'transfer_out': return 'Transf. Saliente'
        default: return type
    }
}
</script>

<style scoped>
.page-header { margin-bottom: 1.25rem; }
.page-title { font-size: 1.15rem; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -0.01em; }
.page-desc { font-size: 0.78rem; color: #64748b; margin: 0.15rem 0 0; }

/* Tabs */
.tabs { display: flex; gap: 1.5rem; border-bottom: 1px solid #e2e8f0; margin-bottom: 1.5rem; }
.tab {
    padding: 0.75rem 0.5rem; font-size: 0.9rem; font-weight: 600; color: #64748b;
    text-decoration: none; border-bottom: 2px solid transparent; transition: all 0.15s;
}
.tab:hover { color: #334155; }
.tab--active { color: #3b82f6; border-bottom-color: #3b82f6; }

/* Filters */
.page-filters { display: flex; align-items: center; gap: 0.6rem; margin-bottom: 1rem; flex-wrap: wrap; }
.filter-field { flex: 1; min-width: 140px; }
.filter-input, .filter-select {
    width: 100%; padding: 0.5rem 0.75rem; border: 1.5px solid #e2e8f0; border-radius: 8px;
    font-size: 0.82rem; color: #334155; background: #fff; outline: none;
    transition: border-color 0.15s; font-family: inherit;
}
.filter-input:focus, .filter-select:focus { border-color: #3b82f6; }
.filter-date { display: flex; align-items: center; gap: 0.5rem; min-width: 250px; flex: 2; }
.filter-date span { font-size: 0.8rem; color: #64748b; }
.filter-clear {
    display: inline-flex; align-items: center; justify-content: center; padding: 0.5rem 0.75rem;
    border: 1.5px solid #e2e8f0; border-radius: 8px; background: #fff; color: #64748b;
    font-size: 0.78rem; font-weight: 500; cursor: pointer; transition: all 0.15s;
}
.filter-clear:hover { border-color: #cbd5e1; background: #f8fafc; color: #334155; }

.font-semibold { font-weight: 600; }
.font-bold { font-weight: 800; }
.text-slate-800 { color: #1e293b; }
.text-slate-500 { color: #64748b; }
.text-sm { font-size: 0.8rem; }
.text-success { color: #059669; }
.text-danger { color: #ef4444; }
.text-warning { color: #d97706; }
.text-info { color: #2563eb; }
.text-gray { color: #64748b; }

.date-cell { display: flex; flex-direction: column; gap: 0.1rem; }
.date-cell .date { font-weight: 500; color: #334155; }
.date-cell .time { font-size: 0.75rem; color: #94a3b8; }

.notes-cell {
    display: block; max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    font-size: 0.8rem; color: #64748b;
}

/* Pagination */
.pagination { display: flex; align-items: center; justify-content: center; gap: 0.25rem; margin-top: 1rem; }
.pagination__btn {
    padding: 0.35rem 0.7rem; border-radius: 6px; border: 1px solid #e2e8f0; background: #fff;
    color: #475569; font-size: 0.78rem; cursor: pointer; transition: all 0.15s; font-family: inherit;
}
.pagination__btn:hover { background: #f1f5f9; }
.pagination__btn--active { background: #3b82f6; color: #fff; border-color: #3b82f6; }
.pagination__disabled { padding: 0.35rem 0.7rem; color: #cbd5e1; font-size: 0.78rem; }
</style>
