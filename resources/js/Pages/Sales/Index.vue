<template>
    <AppLayout>
        <template #title>Ventas</template>

        <div class="page">
            <div class="page-header">
                <div>
                    <h2 class="page-title">Historial de Ventas</h2>
                    <p class="page-desc">Visualiza y administra las ventas registradas</p>
                </div>
                <a href="/sales/create" @click.prevent="$inertia.visit('/sales/create')" class="btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                    Nueva Venta
                </a>
            </div>

            <!-- Summary Cards -->
            <div class="summary-cards">
                <div class="summary-card">
                    <div class="summary-icon bg-emerald-50 text-emerald-500">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" x2="12" y1="2" y2="22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    </div>
                    <div class="summary-info">
                        <span class="summary-label">Total Ventas (Activas)</span>
                        <span class="summary-value text-emerald-600">Bs. {{ parseFloat(totals.total || 0).toFixed(2) }}</span>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="summary-icon bg-blue-50 text-blue-500">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
                    </div>
                    <div class="summary-info">
                        <span class="summary-label">Efectivo</span>
                        <span class="summary-value">Bs. {{ parseFloat(totals.cash || 0).toFixed(2) }}</span>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="summary-icon bg-amber-50 text-amber-500">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    </div>
                    <div class="summary-info">
                        <span class="summary-label">Crédito</span>
                        <span class="summary-value">Bs. {{ parseFloat(totals.credit || 0).toFixed(2) }}</span>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="summary-icon bg-red-50 text-red-500">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" x2="9" y1="9" y2="15"/><line x1="9" x2="15" y1="9" y2="15"/></svg>
                    </div>
                    <div class="summary-info">
                        <span class="summary-label">Anuladas</span>
                        <span class="summary-value">Bs. {{ parseFloat(totals.cancelled || 0).toFixed(2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="page-filters">
                <div class="filter-field">
                    <input v-model="filters.search" type="text" placeholder="Buscar código..." class="filter-input" @input="applyFilters" />
                </div>
                <div v-if="isAdmin" class="filter-field">
                    <select v-model="filters.branch_id" class="filter-select" @change="applyFilters">
                        <option value="">Todas las sucursales</option>
                        <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>
                </div>
                <div class="filter-field">
                    <select v-model="filters.payment_type" class="filter-select" @change="applyFilters">
                        <option value="">Todos los pagos</option>
                        <option value="cash">Efectivo</option>
                        <option value="credit">Crédito</option>
                        <option value="transfer">Transferencia</option>
                        <option value="other">Otro</option>
                    </select>
                </div>
                <div class="filter-field">
                    <select v-model="filters.status" class="filter-select" @change="applyFilters">
                        <option value="">Todos los estados</option>
                        <option value="active">Activas</option>
                        <option value="cancelled">Anuladas</option>
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
            <AppTable :columns="columns" :rows="sales.data">
                <template #cell(code)="{ row }">
                    <span class="font-semibold text-slate-800">{{ row.code }}</span>
                </template>
                <template #cell(client)="{ row }">{{ row.client?.name || 'Consumidor Final' }}</template>
                <template #cell(branch)="{ row }">{{ row.branch?.name }}</template>
                <template #cell(user)="{ row }">{{ row.user?.name }}</template>
                <template #cell(items)="{ row }">{{ row.items_count }} prod.</template>
                <template #cell(total)="{ row }">
                    <span class="font-bold text-slate-800" :class="{'line-through text-slate-400': row.status === 'cancelled'}">
                        Bs. {{ parseFloat(row.total).toFixed(2) }}
                    </span>
                </template>
                <template #cell(payment_type)="{ row }">
                    <AppBadge :variant="paymentVariant(row.payment_type)">{{ paymentLabel(row.payment_type) }}</AppBadge>
                </template>
                <template #cell(status)="{ row }">
                    <AppBadge :variant="row.status === 'active' ? 'success' : 'danger'">
                        {{ row.status === 'active' ? 'Activa' : 'Anulada' }}
                    </AppBadge>
                </template>
                <template #cell(created_at)="{ row }">
                    <div class="date-cell">
                        <span class="date">{{ new Date(row.created_at).toLocaleDateString() }}</span>
                        <span class="time">{{ new Date(row.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}</span>
                    </div>
                </template>
                <template #cell(actions)="{ row }">
                    <div class="actions-group">
                        <a :href="`/sales/${row.id}`" @click.prevent="$inertia.visit(`/sales/${row.id}`)" class="action-btn action-view" title="Ver Venta">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </a>
                        <button class="action-btn action-print" title="Imprimir" @click="openPrintModal(row)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
                        </button>
                        <button v-if="isAdmin && row.status === 'active'" class="action-btn action-delete" title="Anular" @click="openCancelModal(row)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                        </button>
                    </div>
                </template>
            </AppTable>

            <!-- Pagination -->
            <div v-if="sales.links && sales.last_page > 1" class="pagination">
                <template v-for="link in sales.links" :key="link.label">
                    <button v-if="link.url" class="pagination__btn" :class="{ 'pagination__btn--active': link.active }" @click="$inertia.visit(link.url)" v-html="link.label"></button>
                    <span v-else class="pagination__disabled" v-html="link.label"></span>
                </template>
            </div>
        </div>

        <!-- Print Modal -->
        <AppModal :show="printModal.show" title="Imprimir Nota" @close="printModal.show = false">
            <div class="print-options">
                <button class="print-btn" @click="print('58mm')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="print-icon"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 17V7"/></svg>
                    <span>Ticket 58mm</span>
                </button>
                <button class="print-btn" @click="print('80mm')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="print-icon"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 17V7"/></svg>
                    <span>Ticket 80mm</span>
                </button>
                <button class="print-btn print-btn--a4" @click="print('A4')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="print-icon"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
                    <span>Hoja A4</span>
                </button>
            </div>
            <template #footer>
                <AppButton variant="secondary" @click="printModal.show = false">Cancelar</AppButton>
            </template>
        </AppModal>

        <!-- Cancel Modal -->
        <AppModal :show="cancelModal.show" title="Anular Venta" @close="cancelModal.show = false">
            <form @submit.prevent="submitCancel" id="cancelForm">
                <p class="modal-text">¿Estás seguro de anular la venta <strong>{{ cancelModal.sale?.code }}</strong>? Se devolverá el stock y se cancelarán los créditos asociados.</p>
                <AppTextarea label="Motivo de anulación (obligatorio)" v-model="cancelForm.cancel_reason" :error="cancelForm.errors.cancel_reason" :rows="3" required />
            </form>
            <template #footer>
                <AppButton variant="secondary" @click="cancelModal.show = false">Cerrar</AppButton>
                <AppButton type="submit" form="cancelForm" :loading="cancelForm.processing" variant="danger">Anular Venta</AppButton>
            </template>
        </AppModal>

    </AppLayout>
</template>

<script setup>
import { reactive, computed } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AppTable from '@/Components/AppTable.vue'
import AppBadge from '@/Components/AppBadge.vue'
import AppModal from '@/Components/AppModal.vue'
import AppTextarea from '@/Components/AppTextarea.vue'
import AppButton from '@/Components/AppButton.vue'

const props = defineProps({ sales: Object, branches: Array, filters: Object, totals: Object })
const page = usePage()
const isAdmin = computed(() => page.props.auth.user.role === 'admin')

const filters = reactive({
    search: props.filters?.search || '', branch_id: props.filters?.branch_id || '',
    payment_type: props.filters?.payment_type || '', status: props.filters?.status || '',
    date_from: props.filters?.date_from || '', date_to: props.filters?.date_to || '',
})

let debounceTimer = null
function applyFilters() {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(() => {
        let params = { ...filters }; for (const k in params) { if (!params[k]) delete params[k] }
        router.get('/sales', params, { preserveState: true, replace: true })
    }, 300)
}
const hasFilters = computed(() => Object.values(filters).some(v => v !== ''))
function clearFilters() {
    Object.keys(filters).forEach(k => filters[k] = '')
    applyFilters()
}

const columns = computed(() => {
    const cols = [
        { key: 'code', label: 'Código' },
        { key: 'client', label: 'Cliente' },
    ]
    if (isAdmin.value) cols.push({ key: 'branch', label: 'Sucursal' })
    cols.push({ key: 'user', label: 'Encargado' })
    cols.push({ key: 'items', label: 'Items' })
    cols.push({ key: 'total', label: 'Total' })
    cols.push({ key: 'payment_type', label: 'Pago' })
    cols.push({ key: 'status', label: 'Estado' })
    cols.push({ key: 'created_at', label: 'Fecha' })
    cols.push({ key: 'actions', label: 'Acciones', width: '120px' })
    return cols
})

function paymentLabel(type) {
    return { cash: 'Efectivo', credit: 'Crédito', transfer: 'Transferencia', other: 'Otro' }[type] || type
}
function paymentVariant(type) {
    return { cash: 'success', credit: 'warning', transfer: 'info', other: 'gray' }[type] || 'gray'
}

// Print Modal
const printModal = reactive({ show: false, sale: null })
function openPrintModal(sale) { printModal.sale = sale; printModal.show = true }
function print(format) {
    window.open(`/sales/${printModal.sale.id}/print?format=${format}`, '_blank')
    printModal.show = false
}

// Cancel Modal
const cancelModal = reactive({ show: false, sale: null })
const cancelForm = useForm({ cancel_reason: '' })
function openCancelModal(sale) {
    cancelModal.sale = sale; cancelForm.cancel_reason = ''; cancelForm.clearErrors(); cancelModal.show = true
}
function submitCancel() {
    cancelForm.patch(`/sales/${cancelModal.sale.id}/cancel`, {
        preserveScroll: true, onSuccess: () => { cancelModal.show = false }
    })
}
</script>

<style scoped>
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; }
.page-title { font-size: 1.15rem; font-weight: 800; color: #0f172a; margin: 0; }
.page-desc { font-size: 0.78rem; color: #64748b; margin: 0.15rem 0 0; }
.btn-primary { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.55rem 1rem; background: linear-gradient(135deg, #3b82f6, #6366f1); color: #fff; border-radius: 8px; font-size: 0.82rem; font-weight: 600; cursor: pointer; text-decoration: none; transition: all 0.15s; white-space: nowrap; }
.btn-primary:hover { box-shadow: 0 4px 14px rgba(59,130,246,0.3); transform: translateY(-1px); }

.summary-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem; }
.summary-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.25rem; display: flex; align-items: center; gap: 1rem; }
.summary-icon { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
.summary-info { display: flex; flex-direction: column; }
.summary-label { font-size: 0.75rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.2rem; }
.summary-value { font-size: 1.25rem; font-weight: 800; color: #1e293b; line-height: 1; }

.page-filters { display: flex; align-items: center; gap: 0.6rem; margin-bottom: 1rem; flex-wrap: wrap; }
.filter-field { flex: 1; min-width: 130px; }
.filter-input, .filter-select { width: 100%; padding: 0.5rem 0.75rem; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 0.82rem; color: #334155; background: #fff; outline: none; transition: border-color 0.15s; font-family: inherit; }
.filter-input:focus, .filter-select:focus { border-color: #3b82f6; }
.filter-date { display: flex; align-items: center; gap: 0.5rem; min-width: 250px; flex: 2; }
.filter-date span { font-size: 0.8rem; color: #64748b; }
.filter-clear { display: inline-flex; align-items: center; padding: 0.5rem 0.75rem; border: 1.5px solid #e2e8f0; border-radius: 8px; background: #fff; color: #64748b; font-size: 0.78rem; font-weight: 500; cursor: pointer; transition: all 0.15s; }
.filter-clear:hover { border-color: #cbd5e1; background: #f8fafc; color: #334155; }

.font-semibold { font-weight: 600; }
.font-bold { font-weight: 700; }
.text-slate-800 { color: #1e293b; }
.text-slate-400 { color: #94a3b8; }
.line-through { text-decoration: line-through; }
.text-emerald-600 { color: #059669; }

.date-cell { display: flex; flex-direction: column; gap: 0.1rem; }
.date-cell .date { font-weight: 500; color: #334155; }
.date-cell .time { font-size: 0.75rem; color: #94a3b8; }

.actions-group { display: flex; gap: 0.2rem; }
.action-btn { padding: 0.35rem; border-radius: 6px; border: none; background: none; cursor: pointer; transition: all 0.15s; display: flex; align-items: center; justify-content: center; text-decoration: none; }
.action-view { color: #64748b; } .action-view:hover { background: rgba(100,116,139,0.1); color: #334155; }
.action-print { color: #3b82f6; } .action-print:hover { background: rgba(59,130,246,0.1); }
.action-delete { color: #ef4444; } .action-delete:hover { background: rgba(239,68,68,0.1); }

.pagination { display: flex; align-items: center; justify-content: center; gap: 0.25rem; margin-top: 1rem; }
.pagination__btn { padding: 0.35rem 0.7rem; border-radius: 6px; border: 1px solid #e2e8f0; background: #fff; color: #475569; font-size: 0.78rem; cursor: pointer; transition: all 0.15s; font-family: inherit; }
.pagination__btn:hover { background: #f1f5f9; }
.pagination__btn--active { background: #3b82f6; color: #fff; border-color: #3b82f6; }
.pagination__disabled { padding: 0.35rem 0.7rem; color: #cbd5e1; font-size: 0.78rem; }

.print-options { display: flex; gap: 1rem; justify-content: center; padding: 1rem 0; }
.print-btn { display: flex; flex-direction: column; align-items: center; gap: 0.75rem; padding: 1.5rem; background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 12px; cursor: pointer; transition: all 0.15s; flex: 1; }
.print-btn:hover { border-color: #3b82f6; background: #eff6ff; transform: translateY(-2px); }
.print-icon { color: #64748b; }
.print-btn:hover .print-icon { color: #3b82f6; }
.print-btn span { font-size: 0.85rem; font-weight: 600; color: #334155; }

.modal-text { font-size: 0.85rem; color: #475569; margin: 0 0 1rem; }
</style>
