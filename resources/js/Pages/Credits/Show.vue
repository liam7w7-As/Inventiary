<template>
    <AppLayout>
        <template #title>Crédito #{{ credit.id }}</template>

        <div class="show-page">
            <div class="page-header">
                <a href="/credits" @click.prevent="$inertia.visit('/credits')" class="back-link">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                    Volver a créditos
                </a>
                
                <div class="header-actions">
                    <button v-if="isAdmin && credit.status !== 'paid'" class="btn-action btn-edit-date" @click="dateModal.show = true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/><path d="m9 16 2 2 4-4"/></svg>
                        Cambiar Vencimiento
                    </button>
                    <button v-if="credit.status !== 'paid'" class="btn-action btn-pay" @click="openPaymentModal">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
                        Registrar Pago
                    </button>
                </div>
            </div>

            <!-- Status Banner -->
            <div class="status-card" :class="`status-card--${credit.status}`">
                <div class="status-header">
                    <div style="display:flex; align-items:center; gap:0.75rem;">
                        <AppBadge :variant="statusVariant(credit.status)" style="font-size:0.9rem;">
                            {{ statusLabel(credit.status) }}
                        </AppBadge>
                        <span class="status-id">Crédito #{{ credit.id }}</span>
                    </div>
                </div>
                
                <div v-if="credit.status === 'overdue'" class="overdue-banner">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" x2="12" y1="9" y2="13"/><line x1="12" x2="12.01" y1="17" y2="17"/></svg>
                    <span>Este crédito se encuentra vencido desde el {{ new Date(credit.due_date).toLocaleDateString() }}.</span>
                </div>
            </div>

            <!-- Financial Summary -->
            <div class="financial-cards">
                <div class="fin-card">
                    <span class="fin-label">Total Crédito</span>
                    <span class="fin-value">Bs. {{ parseFloat(credit.total_amount).toFixed(2) }}</span>
                </div>
                <div class="fin-card fin-card--paid">
                    <span class="fin-label">Total Pagado</span>
                    <span class="fin-value">Bs. {{ parseFloat(credit.paid_amount).toFixed(2) }}</span>
                </div>
                <div class="fin-card fin-card--balance" :class="{'fin-card--zero': credit.balance <= 0}">
                    <span class="fin-label">Saldo Pendiente</span>
                    <span class="fin-value">Bs. {{ parseFloat(credit.balance).toFixed(2) }}</span>
                </div>
            </div>

            <!-- Progress Bar -->
            <div class="progress-section">
                <div class="progress-header">
                    <span class="progress-title">Progreso de pago</span>
                    <span class="progress-pct">{{ progressPct }}%</span>
                </div>
                <div class="progress-track">
                    <div class="progress-fill" :style="{width: progressPct + '%'}"></div>
                </div>
            </div>

            <div class="grid-layout">
                <!-- Info Grid -->
                <div class="info-section">
                    <h3 class="section-title">Información General</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Cliente</span>
                            <span class="info-value">{{ credit.client?.name || 'Cliente Eliminado' }}</span>
                            <span v-if="credit.client?.phone" class="info-sub">Tel: {{ credit.client.phone }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Sucursal</span>
                            <span class="info-value">{{ credit.branch?.name }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Venta Origen</span>
                            <span class="info-value">
                                <a :href="`/sales/${credit.sale_id}`" @click.prevent="$inertia.visit(`/sales/${credit.sale_id}`)" class="text-blue-600 hover:underline">
                                    {{ sale?.code || '#' + credit.sale_id }}
                                </a>
                            </span>
                            <span class="info-sub">Total Venta: Bs. {{ parseFloat(sale?.total || 0).toFixed(2) }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Fecha Creación</span>
                            <span class="info-value">{{ new Date(credit.created_at).toLocaleDateString() }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Vencimiento Actual</span>
                            <span class="info-value" :class="dueDateClass(credit.due_date, credit.status)">
                                {{ new Date(credit.due_date).toLocaleDateString() }}
                            </span>
                        </div>
                    </div>
                    
                    <div v-if="credit.notes" class="notes-box">
                        <strong>Observaciones iniciales:</strong> {{ credit.notes }}
                    </div>
                </div>

                <div class="installments-section" style="margin-bottom: 1.5rem;">
                    <h3 class="section-title">Plan de Cuotas</h3>
                    <div v-if="installments.length === 0" class="empty-payments">
                        Cargando cuotas...
                    </div>
                    <div v-else class="installments-grid">
                        <div v-for="inst in installments" :key="inst.id" class="installment-card" :class="'inst--' + inst.status">
                            <div class="inst-header">
                                <span class="inst-num">Cuota {{ inst.installment_number }}</span>
                                <span class="inst-status">
                                    {{ inst.status === 'paid' ? 'Pagada' : (inst.status === 'overdue' ? 'Vencida' : (inst.status === 'partial' ? 'Parcial' : 'Pendiente')) }}
                                </span>
                            </div>
                            <div class="inst-body">
                                <div class="inst-row">
                                    <span>Monto:</span>
                                    <strong>Bs. {{ parseFloat(inst.amount).toFixed(2) }}</strong>
                                </div>
                                <div class="inst-row">
                                    <span>Pagado:</span>
                                    <strong class="text-emerald-600">Bs. {{ parseFloat(inst.paid_amount).toFixed(2) }}</strong>
                                </div>
                                <div class="inst-row">
                                    <span>Vence:</span>
                                    <strong :class="dueDateClass(inst.due_date, inst.status)">{{ new Date(inst.due_date).toLocaleDateString() }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payments History -->
                <div class="payments-section">
                    <h3 class="section-title">Historial de Pagos</h3>
                    
                    <div v-if="payments.length === 0" class="empty-payments">
                        No se han registrado pagos aún.
                    </div>
                    
                    <div v-else class="payments-list">
                        <div v-for="payment in payments" :key="payment.id" class="payment-item">
                            <div class="payment-header">
                                <span class="payment-amount">Bs. {{ parseFloat(payment.amount).toFixed(2) }}</span>
                                <span class="payment-date">{{ new Date(payment.payment_date).toLocaleDateString() }}</span>
                            </div>
                            <div class="payment-body">
                                <span class="payment-user">Registrado por: {{ payment.user?.name }}</span>
                                <p v-if="payment.notes" class="payment-notes">{{ payment.notes }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Modal -->
        <AppModal :show="paymentModal.show" title="Registrar Pago" @close="paymentModal.show = false" maxWidth="md">
            <form @submit.prevent="submitPayment" id="paymentForm">
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Monto a pagar (Bs.)</label>
                    <div class="flex gap-2">
                        <input type="number" step="0.01" min="0.01" :max="credit.balance" v-model="paymentForm.amount" class="payment-input" required />
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

        <!-- Edit Date Modal -->
        <AppModal :show="dateModal.show" title="Cambiar Fecha de Vencimiento" @close="dateModal.show = false" maxWidth="sm">
            <form @submit.prevent="submitDate" id="dateForm">
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Nueva fecha de vencimiento</label>
                    <input type="date" v-model="dateForm.due_date" class="payment-input" :min="new Date().toISOString().split('T')[0]" required />
                    <div v-if="dateForm.errors.due_date" class="text-red-500 text-xs mt-1">{{ dateForm.errors.due_date }}</div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Motivo (opcional)</label>
                    <textarea v-model="dateForm.notes" class="payment-textarea" rows="2"></textarea>
                    <div v-if="dateForm.errors.notes" class="text-red-500 text-xs mt-1">{{ dateForm.errors.notes }}</div>
                </div>
            </form>
            <template #footer>
                <button type="button" class="btn-cancel" @click="dateModal.show = false">Cancelar</button>
                <button type="submit" form="dateForm" class="btn-submit btn-submit--blue" :disabled="dateForm.processing">Guardar Fecha</button>
            </template>
        </AppModal>

    </AppLayout>
</template>

<script setup>
import { reactive, computed, ref, onMounted } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AppBadge from '@/Components/AppBadge.vue'
import AppModal from '@/Components/AppModal.vue'

const props = defineProps({
    credit: Object,
    payments: Array,
    sale: Object
})

const installments = ref([])
onMounted(async () => {
    try {
        const resp = await fetch(`/credits/${props.credit.id}/installments`)
        installments.value = await resp.json()
    } catch(e) {}
})

const page = usePage()
const isAdmin = computed(() => page.props.auth.user.role === 'admin')

const progressPct = computed(() => {
    if (props.credit.total_amount <= 0) return 0
    const pct = (props.credit.paid_amount / props.credit.total_amount) * 100
    return Math.min(Math.round(pct), 100)
})

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
const paymentModal = reactive({ show: false })
const paymentForm = useForm({
    amount: '',
    payment_date: new Date().toISOString().split('T')[0],
    notes: ''
})

function openPaymentModal() {
    paymentForm.reset()
    paymentForm.clearErrors()
    paymentForm.amount = ''
    paymentForm.notes = ''
    paymentForm.payment_date = new Date().toISOString().split('T')[0]
    
    if (installments.value && installments.value.length > 0) {
        const nextInst = installments.value.find(i => i.status === 'pending' || i.status === 'partial' || i.status === 'overdue')
        if (nextInst) {
            const amountToPay = nextInst.amount - nextInst.paid_amount
            paymentForm.amount = parseFloat(amountToPay).toFixed(2)
            paymentForm.notes = `Pagado la cuota ${nextInst.installment_number}`
        }
    }
    
    paymentModal.show = true
}

function setFullPayment() {
    paymentForm.amount = parseFloat(props.credit.balance).toFixed(2)
}

function submitPayment() {
    paymentForm.post(`/credits/${props.credit.id}/payment`, {
        preserveScroll: true,
        onSuccess: () => { paymentModal.show = false }
    })
}

// Edit Date Modal
const dateModal = reactive({ show: false })
const dateForm = useForm({
    due_date: props.credit.due_date,
    notes: ''
})

function submitDate() {
    dateForm.patch(`/credits/${props.credit.id}/due-date`, {
        preserveScroll: true,
        onSuccess: () => { dateModal.show = false }
    })
}
</script>

<style scoped>
.show-page { max-width: 1000px; padding-bottom: 2rem; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem; }
.back-link { display: inline-flex; align-items: center; gap: 0.35rem; color: #64748b; font-size: 0.82rem; font-weight: 500; text-decoration: none; transition: color 0.15s; }
.back-link:hover { color: #3b82f6; }

.header-actions { display: flex; gap: 0.5rem; }
.btn-action { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.5rem 1rem; border-radius: 8px; font-size: 0.8rem; font-weight: 600; cursor: pointer; transition: all 0.15s; border: none; }
.btn-pay { background: #10b981; color: #fff; }
.btn-pay:hover { background: #059669; }
.btn-edit-date { background: #fff; border: 1px solid #cbd5e1; color: #334155; }
.btn-edit-date:hover { border-color: #3b82f6; color: #3b82f6; background: #eff6ff; }

/* Status Card */
.status-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.25rem; margin-bottom: 1.5rem; border-left: 4px solid #cbd5e1; }
.status-card--active { border-left-color: #3b82f6; }
.status-card--overdue { border-left-color: #ef4444; background: #fef2f2; }
.status-card--paid { border-left-color: #10b981; background: #f0fdf4; }
.status-header { display: flex; justify-content: space-between; align-items: center; }
.status-id { font-size: 1.1rem; font-weight: 800; color: #1e293b; }
.overdue-banner { display: flex; align-items: center; gap: 0.5rem; margin-top: 1rem; padding: 0.75rem; background: #fee2e2; color: #b91c1c; border-radius: 8px; font-size: 0.85rem; font-weight: 600; }

/* Financial Cards */
.financial-cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
.fin-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.25rem; display: flex; flex-direction: column; }
.fin-label { font-size: 0.75rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.25rem; }
.fin-value { font-size: 1.5rem; font-weight: 800; color: #1e293b; }
.fin-card--paid .fin-value { color: #059669; }
.fin-card--balance { border-color: #fca5a5; background: #fff1f2; }
.fin-card--balance .fin-value { color: #dc2626; }
.fin-card--zero { border-color: #bbf7d0; background: #f0fdf4; }
.fin-card--zero .fin-value { color: #059669; }

/* Progress Bar */
.progress-section { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.25rem; margin-bottom: 1.5rem; }
.progress-header { display: flex; justify-content: space-between; margin-bottom: 0.5rem; }
.progress-title { font-size: 0.85rem; font-weight: 600; color: #475569; }
.progress-pct { font-size: 0.85rem; font-weight: 800; color: #10b981; }
.progress-track { height: 8px; background: #f1f5f9; border-radius: 4px; overflow: hidden; }
.progress-fill { height: 100%; background: #10b981; transition: width 0.5s ease; }

/* Layout Grid */
.grid-layout { display: grid; grid-template-columns: 3fr 2fr; gap: 1.5rem; }
@media (max-width: 768px) { .grid-layout { grid-template-columns: 1fr; } .financial-cards { grid-template-columns: 1fr; } }

/* Info Section */
.info-section { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.5rem; }
.section-title { font-size: 0.95rem; font-weight: 700; color: #334155; margin: 0 0 1rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.5rem; }
.info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem; }
.info-item { display: flex; flex-direction: column; }
.info-label { font-size: 0.72rem; font-weight: 600; color: #64748b; text-transform: uppercase; margin-bottom: 0.2rem; }
.info-value { font-size: 0.9rem; color: #1e293b; font-weight: 600; }
.info-sub { font-size: 0.75rem; color: #94a3b8; margin-top: 0.1rem; }
.notes-box { background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 0.85rem 1rem; font-size: 0.85rem; color: #475569; }

/* Payments Section */
.payments-section { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.5rem; }
.empty-payments { font-size: 0.85rem; color: #94a3b8; text-align: center; padding: 2rem 0; }
.payments-list { display: flex; flex-direction: column; gap: 0.75rem; }
.payment-item { border: 1px solid #e2e8f0; border-radius: 8px; padding: 0.75rem 1rem; background: #f8fafc; }
.payment-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem; }
.payment-amount { font-size: 0.95rem; font-weight: 800; color: #059669; }
.payment-date { font-size: 0.75rem; color: #64748b; font-weight: 500; }
.payment-body { display: flex; flex-direction: column; gap: 0.25rem; }
.payment-user { font-size: 0.75rem; color: #475569; }
.payment-notes { font-size: 0.8rem; color: #334155; margin: 0; padding-top: 0.25rem; border-top: 1px dashed #cbd5e1; }

/* Installments */
.installments-section { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.5rem; }
.installments-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; }
.installment-card { border: 1px solid #e2e8f0; border-radius: 8px; padding: 0.75rem; background: #fff; border-left: 4px solid #cbd5e1; }
.inst--pending { border-left-color: #f59e0b; }
.inst--partial { border-left-color: #3b82f6; }
.inst--paid { border-left-color: #10b981; background: #f0fdf4; }
.inst--overdue { border-left-color: #ef4444; background: #fef2f2; }
.inst-header { display: flex; justify-content: space-between; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.4rem; margin-bottom: 0.4rem; }
.inst-num { font-size: 0.8rem; font-weight: 700; color: #334155; }
.inst-status { font-size: 0.7rem; font-weight: 600; text-transform: uppercase; color: #64748b; }
.inst-row { display: flex; justify-content: space-between; font-size: 0.8rem; color: #475569; padding: 0.2rem 0; }
.inst-row strong { color: #1e293b; }

/* Modals */
.payment-input, .payment-textarea { width: 100%; padding: 0.6rem 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; outline: none; }
.payment-input:focus, .payment-textarea:focus { border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59,130,246,0.1); }
.btn-full-pay { padding: 0 0.75rem; background: #eff6ff; color: #3b82f6; border: 1px solid #bfdbfe; border-radius: 6px; font-size: 0.8rem; font-weight: 600; cursor: pointer; white-space: nowrap; }
.btn-full-pay:hover { background: #dbeafe; }
.btn-cancel { padding: 0.6rem 1.2rem; background: #fff; border: 1px solid #cbd5e1; color: #475569; border-radius: 6px; font-size: 0.9rem; font-weight: 600; cursor: pointer; }
.btn-submit { padding: 0.6rem 1.2rem; background: #10b981; border: none; color: #fff; border-radius: 6px; font-size: 0.9rem; font-weight: 600; cursor: pointer; }
.btn-submit:hover:not(:disabled) { background: #059669; }
.btn-submit--blue { background: #3b82f6; }
.btn-submit--blue:hover:not(:disabled) { background: #2563eb; }
.btn-submit:disabled { opacity: 0.7; cursor: not-allowed; }

/* Utils */
.text-red-600 { color: #dc2626; }
.text-amber-600 { color: #d97706; }
.text-slate-500 { color: #64748b; }
.text-slate-700 { color: #334155; }
.text-blue-600 { color: #2563eb; }
.font-bold { font-weight: 700; }
.hover\:underline:hover { text-decoration: underline; }
</style>
