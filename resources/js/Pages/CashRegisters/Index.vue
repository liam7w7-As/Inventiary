<template>
    <AppLayout>
        <template #title>Apertura de Caja</template>

        <div class="page">
            <div class="page-header">
                <div>
                    <h2 class="page-title">Control de Cajas</h2>
                    <p class="page-desc">Gestiona las aperturas y cierres de caja</p>
                </div>
                <a v-if="isAdmin" href="/cash-registers/create" @click.prevent="$inertia.visit('/cash-registers/create')" class="btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                    Abrir Caja
                </a>
            </div>

            <!-- Summary -->
            <div class="summary-cards">
                <div class="summary-card">
                    <div class="summary-icon bg-emerald-50 text-emerald-500">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" x2="8" y1="21" y2="17"/><line x1="16" x2="16" y1="21" y2="17"/><line x1="12" x2="12" y1="21" y2="17"/></svg>
                    </div>
                    <div class="summary-info">
                        <span class="summary-label">Cajas Abiertas Hoy</span>
                        <span class="summary-value">{{ summary.openToday }}</span>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="summary-icon bg-blue-50 text-blue-500">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" x2="12" y1="2" y2="22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    </div>
                    <div class="summary-info">
                        <span class="summary-label">Total Inicial Hoy</span>
                        <span class="summary-value">Bs. {{ parseFloat(summary.totalInitialToday || 0).toFixed(2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="page-filters">
                <div v-if="isAdmin" class="filter-field">
                    <select v-model="filters.user_id" class="filter-select" @change="applyFilters">
                        <option value="">Todos los encargados</option>
                        <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                    </select>
                </div>
                <div v-if="isAdmin" class="filter-field">
                    <select v-model="filters.branch_id" class="filter-select" @change="applyFilters">
                        <option value="">Todas las sucursales</option>
                        <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>
                </div>
                <div class="filter-field">
                    <select v-model="filters.status" class="filter-select" @change="applyFilters">
                        <option value="">Todos los estados</option>
                        <option value="open">Abiertas</option>
                        <option value="closed">Cerradas</option>
                    </select>
                </div>
                <div class="filter-field filter-date">
                    <input type="date" v-model="filters.date_from" class="filter-input" @change="applyFilters" />
                    <span>a</span>
                    <input type="date" v-model="filters.date_to" class="filter-input" @change="applyFilters" />
                </div>
                <button v-if="hasFilters" class="filter-clear" @click="clearFilters">Limpiar</button>
            </div>

            <!-- Table -->
            <AppTable :columns="columns" :rows="cashRegisters.data">
                <template #cell(user)="{ row }">
                    <span class="font-semibold text-slate-800">{{ row.user?.name }}</span>
                </template>
                <template #cell(branch)="{ row }">{{ row.branch?.name }}</template>
                <template #cell(initial_balance)="{ row }">
                    <span class="font-semibold">Bs. {{ parseFloat(row.initial_balance).toFixed(2) }}</span>
                </template>
                <template #cell(sale_limit)="{ row }">
                    <span v-if="row.sale_limit">Bs. {{ parseFloat(row.sale_limit).toFixed(2) }}</span>
                    <span v-else class="text-slate-400">Sin límite</span>
                </template>
                <template #cell(opened_at)="{ row }">
                    <div class="date-cell">
                        <span class="date">{{ new Date(row.opened_at).toLocaleDateString() }}</span>
                        <span class="time">{{ new Date(row.opened_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}</span>
                    </div>
                </template>
                <template #cell(closed_at)="{ row }">
                    <template v-if="row.closed_at">
                        <div class="date-cell">
                            <span class="date">{{ new Date(row.closed_at).toLocaleDateString() }}</span>
                            <span class="time">{{ new Date(row.closed_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}</span>
                        </div>
                    </template>
                    <AppBadge v-else variant="success">Abierta</AppBadge>
                </template>
                <template #cell(status)="{ row }">
                    <AppBadge :variant="row.status === 'open' ? 'success' : 'gray'">
                        {{ row.status === 'open' ? 'Abierta' : 'Cerrada' }}
                    </AppBadge>
                </template>
                <template #cell(actions)="{ row }">
                    <a :href="`/cash-registers/${row.id}`" @click.prevent="$inertia.visit(`/cash-registers/${row.id}`)" class="action-btn action-view" title="Ver detalle">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                    </a>
                </template>
            </AppTable>

            <!-- Pagination -->
            <div v-if="cashRegisters.links && cashRegisters.last_page > 1" class="pagination">
                <template v-for="link in cashRegisters.links" :key="link.label">
                    <button v-if="link.url" class="pagination__btn" :class="{ 'pagination__btn--active': link.active }" @click="$inertia.visit(link.url)" v-html="link.label"></button>
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
    cashRegisters: Object, users: Array, branches: Array, filters: Object, summary: Object,
})
const page = usePage()
const isAdmin = computed(() => page.props.auth.user.role === 'admin')

const filters = reactive({
    user_id: props.filters?.user_id || '', branch_id: props.filters?.branch_id || '',
    status: props.filters?.status || '', date_from: props.filters?.date_from || '', date_to: props.filters?.date_to || '',
})

function applyFilters() {
    let params = { ...filters }; for (const k in params) { if (!params[k]) delete params[k] }
    router.get('/cash-registers', params, { preserveState: true, replace: true })
}
const hasFilters = computed(() => Object.values(filters).some(v => v !== ''))
function clearFilters() {
    Object.keys(filters).forEach(k => filters[k] = '')
    router.get('/cash-registers', {}, { preserveState: true, replace: true })
}

const columns = computed(() => {
    const cols = [{ key: 'user', label: 'Encargado' }]
    if (isAdmin.value) cols.push({ key: 'branch', label: 'Sucursal' })
    cols.push({ key: 'initial_balance', label: 'Saldo Inicial' })
    cols.push({ key: 'sale_limit', label: 'Límite Venta' })
    cols.push({ key: 'opened_at', label: 'Apertura' })
    cols.push({ key: 'closed_at', label: 'Cierre' })
    cols.push({ key: 'status', label: 'Estado' })
    cols.push({ key: 'actions', label: '', width: '60px' })
    return cols
})
</script>

<style scoped>
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; }
.page-title { font-size: 1.15rem; font-weight: 800; color: #0f172a; margin: 0; }
.page-desc { font-size: 0.78rem; color: #64748b; margin: 0.15rem 0 0; }
.btn-primary { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.55rem 1rem; background: linear-gradient(135deg, #3b82f6, #6366f1); color: #fff; border-radius: 8px; border: none; font-size: 0.82rem; font-weight: 600; cursor: pointer; text-decoration: none; transition: all 0.15s; white-space: nowrap; }
.btn-primary:hover { box-shadow: 0 4px 14px rgba(59,130,246,0.3); transform: translateY(-1px); }

.summary-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem; }
.summary-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.25rem; display: flex; align-items: center; gap: 1rem; }
.summary-icon { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
.summary-info { display: flex; flex-direction: column; }
.summary-label { font-size: 0.75rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.2rem; }
.summary-value { font-size: 1.4rem; font-weight: 800; color: #1e293b; line-height: 1; }

.page-filters { display: flex; align-items: center; gap: 0.6rem; margin-bottom: 1rem; flex-wrap: wrap; }
.filter-field { flex: 1; min-width: 140px; }
.filter-input, .filter-select { width: 100%; padding: 0.5rem 0.75rem; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 0.82rem; color: #334155; background: #fff; outline: none; transition: border-color 0.15s; font-family: inherit; }
.filter-input:focus, .filter-select:focus { border-color: #3b82f6; }
.filter-date { display: flex; align-items: center; gap: 0.5rem; min-width: 250px; flex: 2; }
.filter-date span { font-size: 0.8rem; color: #64748b; }
.filter-clear { display: inline-flex; align-items: center; padding: 0.5rem 0.75rem; border: 1.5px solid #e2e8f0; border-radius: 8px; background: #fff; color: #64748b; font-size: 0.78rem; font-weight: 500; cursor: pointer; transition: all 0.15s; }
.filter-clear:hover { border-color: #cbd5e1; background: #f8fafc; color: #334155; }

.font-semibold { font-weight: 600; }
.text-slate-800 { color: #1e293b; }
.text-slate-400 { color: #94a3b8; }
.date-cell { display: flex; flex-direction: column; gap: 0.1rem; }
.date-cell .date { font-weight: 500; color: #334155; }
.date-cell .time { font-size: 0.75rem; color: #94a3b8; }

.action-btn { padding: 0.35rem; border-radius: 6px; border: none; background: none; cursor: pointer; transition: all 0.15s; display: flex; align-items: center; justify-content: center; text-decoration: none; }
.action-view { color: #64748b; } .action-view:hover { background: rgba(100,116,139,0.1); color: #334155; }

.pagination { display: flex; align-items: center; justify-content: center; gap: 0.25rem; margin-top: 1rem; }
.pagination__btn { padding: 0.35rem 0.7rem; border-radius: 6px; border: 1px solid #e2e8f0; background: #fff; color: #475569; font-size: 0.78rem; cursor: pointer; transition: all 0.15s; font-family: inherit; }
.pagination__btn:hover { background: #f1f5f9; }
.pagination__btn--active { background: #3b82f6; color: #fff; border-color: #3b82f6; }
.pagination__disabled { padding: 0.35rem 0.7rem; color: #cbd5e1; font-size: 0.78rem; }
</style>
