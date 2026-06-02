<template>
    <AppLayout>
        <template #title>Créditos y Cobranza</template>

        <div class="credits-page">
            <!-- Summary Cards -->
            <div class="summary-cards">
                <div class="summary-card summary-card--active">
                    <div class="summary-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    </div>
                    <div class="summary-info">
                        <h3>Saldo Total Activo</h3>
                        <p class="amount">Bs. {{ parseFloat(summary.total_active || 0).toFixed(2) }}</p>
                        <p class="subtitle">{{ summary.count_active }} créditos activos</p>
                    </div>
                </div>

                <div class="summary-card summary-card--overdue">
                    <div class="summary-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <div class="summary-info">
                        <h3>Saldo Vencido</h3>
                        <p class="amount">Bs. {{ parseFloat(summary.total_overdue || 0).toFixed(2) }}</p>
                        <p class="subtitle">{{ summary.count_overdue }} créditos vencidos</p>
                    </div>
                </div>

                <div class="summary-card summary-card--paid">
                    <div class="summary-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                    <div class="summary-info">
                        <h3>Total Cobrado (Histórico)</h3>
                        <p class="amount">Bs. {{ parseFloat(summary.total_paid || 0).toFixed(2) }}</p>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="tabs-container">
                <button class="tab-btn" :class="{'tab-btn--active': !filters.status && !filters.is_overdue}" @click="setTab('')">Todos</button>
                <button class="tab-btn" :class="{'tab-btn--active': filters.status === 'active' && !filters.is_overdue}" @click="setTab('active')">Activos</button>
                <button class="tab-btn tab-btn--danger" :class="{'tab-btn--active': filters.is_overdue === 'true'}" @click="setTab('overdue')">
                    Vencidos
                    <span v-if="summary.count_overdue > 0" class="tab-badge">{{ summary.count_overdue }}</span>
                </button>
                <button class="tab-btn" :class="{'tab-btn--active': filters.status === 'paid'}" @click="setTab('paid')">Pagados</button>
            </div>

            <!-- Filters -->
            <div class="filters-bar">
                <div class="filter-field" v-if="isAdmin">
                    <select v-model="filtersForm.branch_id" class="filter-select" @change="applyFilters">
                        <option value="">Todas las sucursales</option>
                        <option v-for="branch in branches" :key="branch.id" :value="branch.id">{{ branch.name }}</option>
                    </select>
                </div>
                <div class="filter-field">
                    <select v-model="filtersForm.client_id" class="filter-select" @change="applyFilters">
                        <option value="">Todos los clientes</option>
                        <option v-for="client in clients" :key="client.id" :value="client.id">{{ client.name }}</option>
                    </select>
                </div>
                <div class="filter-field filter-date">
                    <span class="text-xs text-slate-500 mr-2">Vence:</span>
                    <input type="date" v-model="filtersForm.date_from" class="filter-input" placeholder="Desde" @change="applyFilters" />
                    <span class="mx-1">a</span>
                    <input type="date" v-model="filtersForm.date_to" class="filter-input" placeholder="Hasta" @change="applyFilters" />
                </div>
                <button v-if="hasFilters" class="filter-clear" @click="clearFilters">Limpiar</button>
            </div>

            <!-- Table -->
            <AppTable :headers="tableHeaders">
                <tr v-for="credit in credits.data" :key="credit.id">
                    <td>
                        <div class="font-semibold">{{ credit.client?.name || 'Cliente Eliminado' }}</div>
                    </td>
                    <td v-if="isAdmin">{{ credit.branch?.name }}</td>
                    <td>
                        <a :href="`/sales/${credit.sale_id}`" @click.prevent="$inertia.visit(`/sales/${credit.sale_id}`)" class="text-blue-600 hover:underline">
                            {{ credit.sale?.code || '#' + credit.sale_id }}
                        </a>
                    </td>
                    <td class="text-right">Bs. {{ parseFloat(credit.total_amount).toFixed(2) }}</td>
                    <td class="text-right text-emerald-600">Bs. {{ parseFloat(credit.paid_amount).toFixed(2) }}</td>
                    <td class="text-right font-bold" :class="{'text-red-600': credit.balance > 0}">Bs. {{ parseFloat(credit.balance).toFixed(2) }}</td>
                    <td>
                        <div :class="dueDateClass(credit.due_date, credit.status)">
                            {{ new Date(credit.due_date).toLocaleDateString() }}
                        </div>
                    </td>
                    <td>
                        <AppBadge :variant="statusVariant(credit.status)">
                            {{ statusLabel(credit.status) }}
                        </AppBadge>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn-action btn-view" @click="$inertia.visit(`/credits/${credit.id}`)" title="Ver Detalle">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                            <button v-if="credit.status !== 'paid'" class="btn-action btn-pay" @click="openPaymentModal(credit)" title="Registrar Pago">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
                            </button>
                        </div>
                    </td>
                </tr>
                <tr v-if="credits.data.length === 0">
                    <td :colspan="isAdmin ? 9 : 8" class="text-center py-8 text-slate-500">
                        No se encontraron créditos.
                    </td>
                </tr>
            </AppTable>

            <!-- Pagination -->
            <div v-if="credits.links && credits.links.length > 3" class="pagination">
                <button v-for="(link, k) in credits.links" :key="k"
                        class="page-link"
                        :class="{'page-link--active': link.active, 'opacity-50 cursor-not-allowed': !link.url}"
                        :disabled="!link.url"
                        v-html="link.label"
                        @click="link.url && $inertia.visit(link.url)">
                </button>
            </div>
        </div>

        <!-- Payment Modal -->
        <AppModal :show="paymentModal.show" title="Registrar Pago" @close="paymentModal.show = false" maxWidth="md">
            <form @submit.prevent="submitPayment" id="paymentForm">
                <div class="payment-info">
                    <p><strong>Cliente:</strong> {{ paymentModal.credit?.client?.name }}</p>
                    <p><strong>Saldo Pendiente:</strong> <span class="text-red-600 font-bold">Bs. {{ parseFloat(paymentModal.credit?.balance || 0).toFixed(2) }}</span></p>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Monto a pagar (Bs.)</label>
                    <div class="flex gap-2">
                        <input type="number" step="0.01" min="0.01" :max="paymentModal.credit?.balance" v-model="paymentForm.amount" class="payment-input" required />
                        <button type="button" class="btn-full-pay" @click="setFullPayment">Pago total</button>
                    </div>
                    <div v-if="paymentForm.errors.amount" class="text-red-500 text-xs mt-1">{{ paymentForm.errors.amount }}</div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Fecha de pago</label>
                    <input type="date" v-model="paymentForm.payment_date" class="payment-input" required />
                    <div v-if="paymentForm.errors.payment_date" class="text-red-500 text-xs mt-1">{{ paymentForm.errors.payment_date }}</div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Notas (opcional)</label>
                    <textarea v-model="paymentForm.notes" class="payment-textarea" rows="2"></textarea>
                    <div v-if="paymentForm.errors.notes" class="text-red-500 text-xs mt-1">{{ paymentForm.errors.notes }}</div>
                </div>
            </form>
            <template #footer>
                <button type="button" class="btn-cancel" @click="paymentModal.show = false">Cancelar</button>
                <button type="submit" form="paymentForm" class="btn-submit" :disabled="paymentForm.processing">Registrar Pago</button>
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

const props = defineProps({
    credits: Object,
    branches: Array,
    clients: Array,
    filters: Object,
    summary: Object,
})

const page = usePage()
const isAdmin = computed(() => page.props.auth.user.role === 'admin')

const tableHeaders = computed(() => {
    const h = ['Cliente']
    if (isAdmin.value) h.push('Sucursal')
    h.push('Venta', 'Total Créd.', 'Pagado', 'Saldo Pend.', 'Vencimiento', 'Estado', 'Acciones')
    return h
})

const filtersForm = reactive({
    branch_id: props.filters.branch_id || '',
    client_id: props.filters.client_id || '',
    status: props.filters.status || '',
    is_overdue: props.filters.is_overdue || '',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
})

const hasFilters = computed(() => {
    return filtersForm.branch_id || filtersForm.client_id || filtersForm.date_from || filtersForm.date_to || filtersForm.status || filtersForm.is_overdue;
})

function applyFilters() {
    router.get('/credits', filtersForm, { preserveState: true, preserveScroll: true })
}

function clearFilters() {
    filtersForm.branch_id = ''
    filtersForm.client_id = ''
    filtersForm.status = ''
    filtersForm.is_overdue = ''
    filtersForm.date_from = ''
    filtersForm.date_to = ''
    applyFilters()
}

function setTab(tab) {
    filtersForm.status = ''
    filtersForm.is_overdue = ''
    if (tab === 'active') filtersForm.status = 'active'
    else if (tab === 'paid') filtersForm.status = 'paid'
    else if (tab === 'overdue') filtersForm.is_overdue = 'true'
    applyFilters()
}

function statusVariant(status) {
    if (status === 'active') return 'info'
    if (status === 'overdue') return 'danger'
    if (status === 'paid') return 'success'
    return 'secondary'
}

function statusLabel(status) {
    if (status === 'active') return 'Activo'
    if (status === 'overdue') return 'Vencido'
    if (status === 'paid') return 'Pagado'
    return status
}

function dueDateClass(dateStr, status) {
    if (status === 'paid') return 'text-slate-500'
    const due = new Date(dateStr)
    const today = new Date()
    today.setHours(0,0,0,0)
    const diffTime = due - today
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
    
    if (diffDays < 0 || status === 'overdue') return 'text-red-600 font-bold'
    if (diffDays <= 3) return 'text-amber-600 font-bold'
    return 'text-slate-700'
}

// Payment Modal
const paymentModal = reactive({ show: false, credit: null })
const paymentForm = useForm({
    amount: '',
    payment_date: new Date().toISOString().split('T')[0],
    notes: ''
})

function openPaymentModal(credit) {
    paymentModal.credit = credit
    paymentForm.reset()
    paymentForm.clearErrors()
    paymentForm.amount = ''
    paymentForm.payment_date = new Date().toISOString().split('T')[0]
    paymentModal.show = true
}

function setFullPayment() {
    if (paymentModal.credit) {
        paymentForm.amount = parseFloat(paymentModal.credit.balance).toFixed(2)
    }
}

function submitPayment() {
    paymentForm.post(`/credits/${paymentModal.credit.id}/payment`, {
        preserveScroll: true,
        onSuccess: () => { paymentModal.show = false }
    })
}
</script>

<style scoped>
.credits-page { padding-bottom: 2rem; }

/* Summary Cards */
.summary-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem; }
.summary-card { background: #fff; border-radius: 12px; padding: 1.5rem; display: flex; align-items: center; gap: 1rem; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
.summary-card--active { border-bottom: 4px solid #3b82f6; }
.summary-card--overdue { border-bottom: 4px solid #ef4444; }
.summary-card--paid { border-bottom: 4px solid #10b981; }
.summary-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; justify-content: center; align-items: center; }
.summary-card--active .summary-icon { background: #eff6ff; color: #3b82f6; }
.summary-card--overdue .summary-icon { background: #fef2f2; color: #ef4444; }
.summary-card--paid .summary-icon { background: #f0fdf4; color: #10b981; }
.summary-info h3 { font-size: 0.8rem; font-weight: 600; color: #64748b; margin: 0 0 0.25rem 0; text-transform: uppercase; letter-spacing: 0.05em; }
.summary-info .amount { font-size: 1.4rem; font-weight: 800; color: #1e293b; margin: 0; }
.summary-info .subtitle { font-size: 0.8rem; color: #94a3b8; margin: 0.25rem 0 0 0; }

/* Tabs */
.tabs-container { display: flex; gap: 0.5rem; margin-bottom: 1rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem; overflow-x: auto; }
.tab-btn { padding: 0.5rem 1rem; border: none; background: transparent; color: #64748b; font-size: 0.9rem; font-weight: 600; cursor: pointer; border-radius: 8px; transition: all 0.15s; display: flex; align-items: center; gap: 0.5rem; white-space: nowrap; }
.tab-btn:hover { background: #f1f5f9; color: #334155; }
.tab-btn--active { background: #fff; color: #3b82f6; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
.tab-btn--danger.tab-btn--active { color: #ef4444; }
.tab-badge { background: #ef4444; color: #fff; font-size: 0.7rem; padding: 0.1rem 0.4rem; border-radius: 10px; }

/* Filters */
.filters-bar { display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; margin-bottom: 1rem; background: #fff; padding: 1rem; border-radius: 8px; border: 1px solid #e2e8f0; }
.filter-field { display: flex; align-items: center; }
.filter-select, .filter-input { padding: 0.45rem 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.85rem; color: #334155; outline: none; }
.filter-select:focus, .filter-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59,130,246,0.1); }
.filter-date { display: flex; align-items: center; }
.filter-clear { background: transparent; border: none; color: #ef4444; font-size: 0.85rem; font-weight: 500; cursor: pointer; padding: 0.45rem 0.75rem; transition: color 0.15s; }
.filter-clear:hover { text-decoration: underline; }

/* Actions */
.action-buttons { display: flex; gap: 0.4rem; }
.btn-action { width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; border: none; border-radius: 6px; cursor: pointer; transition: all 0.15s; }
.btn-view { background: #eff6ff; color: #3b82f6; }
.btn-view:hover { background: #dbeafe; }
.btn-pay { background: #f0fdf4; color: #10b981; }
.btn-pay:hover { background: #dcfce7; }

/* Text colors */
.text-emerald-600 { color: #059669; }
.text-red-600 { color: #dc2626; }
.text-amber-600 { color: #d97706; }
.text-slate-500 { color: #64748b; }
.text-slate-700 { color: #334155; }
.text-blue-600 { color: #2563eb; }
.font-bold { font-weight: 700; }
.font-semibold { font-weight: 600; }
.hover\:underline:hover { text-decoration: underline; }

/* Modal */
.payment-info { background: #f8fafc; border: 1px solid #e2e8f0; padding: 1rem; border-radius: 8px; margin-bottom: 1.25rem; font-size: 0.9rem; color: #334155; }
.payment-info p { margin: 0 0 0.25rem 0; }
.payment-input, .payment-textarea { width: 100%; padding: 0.6rem 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; outline: none; }
.payment-input:focus, .payment-textarea:focus { border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59,130,246,0.1); }
.btn-full-pay { padding: 0 0.75rem; background: #eff6ff; color: #3b82f6; border: 1px solid #bfdbfe; border-radius: 6px; font-size: 0.8rem; font-weight: 600; cursor: pointer; white-space: nowrap; }
.btn-full-pay:hover { background: #dbeafe; }
.btn-cancel { padding: 0.6rem 1.2rem; background: #fff; border: 1px solid #cbd5e1; color: #475569; border-radius: 6px; font-size: 0.9rem; font-weight: 600; cursor: pointer; }
.btn-submit { padding: 0.6rem 1.2rem; background: #10b981; border: none; color: #fff; border-radius: 6px; font-size: 0.9rem; font-weight: 600; cursor: pointer; }
.btn-submit:hover:not(:disabled) { background: #059669; }
.btn-submit:disabled { opacity: 0.7; cursor: not-allowed; }

/* Pagination */
.pagination { display: flex; flex-wrap: wrap; gap: 0.25rem; margin-top: 1.5rem; justify-content: flex-end; }
.page-link { padding: 0.4rem 0.75rem; border: 1px solid #e2e8f0; background: #fff; color: #475569; font-size: 0.85rem; border-radius: 6px; cursor: pointer; transition: all 0.15s; }
.page-link:hover:not(:disabled) { background: #f8fafc; border-color: #cbd5e1; }
.page-link--active { background: #3b82f6; color: #fff; border-color: #3b82f6; }
.page-link--active:hover:not(:disabled) { background: #2563eb; }
.cursor-not-allowed { cursor: not-allowed; }
.opacity-50 { opacity: 0.5; }
</style>
