<template>
    <AppLayout>
        <template #title>Venta {{ sale.code }}</template>

        <div class="show-page">
            <div class="page-header">
                <a href="/sales" @click.prevent="$inertia.visit('/sales')" class="back-link">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                    Volver a ventas
                </a>
                
                <div class="header-actions">
                    <button class="btn-print" @click="printDirectly()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
                        Imprimir Nota
                    </button>
                    <button v-if="isAdmin && sale.status === 'active'" class="btn-cancel" @click="cancelModal.show = true">
                        Anular Venta
                    </button>
                </div>
            </div>

            <!-- Status Banner -->
            <div class="status-card" :class="`status-card--${sale.status}`">
                <div class="status-header">
                    <div style="display:flex; align-items:center; gap:0.75rem;">
                        <AppBadge :variant="sale.status === 'active' ? 'success' : 'danger'" style="font-size:0.9rem;">
                            {{ sale.status === 'active' ? 'Venta Activa' : 'Venta Anulada' }}
                        </AppBadge>
                        <span class="status-id">{{ sale.code }}</span>
                    </div>
                    <div class="status-date">{{ new Date(sale.created_at).toLocaleString() }}</div>
                </div>
                
                <div v-if="sale.status === 'cancelled'" class="cancel-info">
                    <p><strong>Motivo de anulación:</strong> {{ sale.cancel_reason }}</p>
                    <p>Anulada por {{ sale.canceller?.name }} el {{ new Date(sale.cancelled_at).toLocaleString() }}</p>
                </div>
            </div>

            <!-- Info Grid -->
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Encargado / Caja</span>
                    <span class="info-value">{{ sale.user?.name }}</span>
                    <span class="info-sub">Caja #{{ sale.cashRegister?.id }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Sucursal</span>
                    <span class="info-value">{{ sale.branch?.name }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Cliente</span>
                    <span class="info-value">{{ sale.client?.name || 'Consumidor Final' }}</span>
                    <span v-if="sale.client?.phone" class="info-sub">Tel: {{ sale.client.phone }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Tipo de Pago</span>
                    <span class="info-value">
                        <span class="pay-dot" :class="`pay-dot--${sale.payment_type}`"></span>
                        {{ paymentLabel(sale.payment_type) }}
                    </span>
                    <span v-if="sale.pre_sale_id" class="info-sub text-blue-600">Origen: Preventa #{{ sale.pre_sale_id }}</span>
                </div>
            </div>

            <!-- Credit Card -->
            <div v-if="sale.payment_type === 'credit' && credit" class="credit-card" :class="{'credit-card--paid': credit.status === 'paid'}">
                <div class="credit-header">
                    <h3 class="credit-title">Detalle de Crédito</h3>
                    <AppBadge :variant="credit.status === 'active' ? 'warning' : 'success'">
                        {{ credit.status === 'active' ? 'Activo' : 'Pagado' }}
                    </AppBadge>
                </div>
                <div class="credit-body">
                    <div class="credit-stat">
                        <span class="stat-label">Total</span>
                        <span class="stat-value">Bs. {{ parseFloat(credit.total_amount).toFixed(2) }}</span>
                    </div>
                    <div class="credit-stat">
                        <span class="stat-label">Pagado</span>
                        <span class="stat-value text-emerald-600">Bs. {{ parseFloat(credit.paid_amount).toFixed(2) }}</span>
                    </div>
                    <div class="credit-stat">
                        <span class="stat-label">Saldo Pendiente</span>
                        <span class="stat-value text-red-600 font-bold">Bs. {{ parseFloat(credit.balance).toFixed(2) }}</span>
                    </div>
                    <div class="credit-stat">
                        <span class="stat-label">Vencimiento</span>
                        <span class="stat-value">{{ new Date(credit.due_date).toLocaleDateString() }}</span>
                    </div>
                </div>
                <a :href="`/credits/${credit.id}`" @click.prevent="$inertia.visit(`/credits/${credit.id}`)" class="btn-view-credit">
                    Ver Crédito Completo →
                </a>
            </div>

            <div v-if="sale.notes" class="notes-box">
                <strong>Observaciones:</strong> {{ sale.notes }}
            </div>

            <!-- Items Table -->
            <div class="section-card">
                <h3 class="section-title">Detalle de Productos</h3>
                <div class="table-responsive">
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Producto</th>
                                <th class="text-center">Cant.</th>
                                <th class="text-right">P. Unitario</th>
                                <th class="text-right">Desc.</th>
                                <th class="text-right">Subtotal</th>
                                <th v-if="canViewProfits" class="text-right bg-blue-50">P. Compra</th>
                                <th v-if="canViewProfits" class="text-right bg-blue-50">Ganancia</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, index) in items" :key="item.id" :class="{'opacity-50': sale.status === 'cancelled'}">
                                <td class="text-slate-400">{{ index + 1 }}</td>
                                <td class="font-semibold">{{ item.product?.name }}</td>
                                <td class="text-center">{{ formatQty(item) }}</td>
                                <td class="text-right">Bs. {{ parseFloat(item.sale_price).toFixed(2) }}</td>
                                <td class="text-right">{{ parseFloat(item.discount) > 0 ? `${parseFloat(item.discount)}%` : '—' }}</td>
                                <td class="text-right font-bold">Bs. {{ parseFloat(item.subtotal).toFixed(2) }}</td>
                                <td v-if="canViewProfits" class="text-right bg-blue-50 text-slate-500">Bs. {{ parseFloat(item.purchase_price).toFixed(2) }}</td>
                                <td v-if="canViewProfits" class="text-right bg-blue-50 text-emerald-600 font-bold">Bs. {{ profit(item).toFixed(2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="totals-area">
                    <div class="totals-block">
                        <div class="total-row">
                            <span>Subtotal:</span>
                            <span>Bs. {{ parseFloat(sale.subtotal).toFixed(2) }}</span>
                        </div>
                        <div v-if="parseFloat(sale.discount) > 0" class="total-row">
                            <span>Descuento Total:</span>
                            <span class="text-red-600">-Bs. {{ parseFloat(sale.discount).toFixed(2) }}</span>
                        </div>
                        <div class="total-row total-row--grand">
                            <span>TOTAL:</span>
                            <span>Bs. {{ parseFloat(sale.total).toFixed(2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>



        <!-- Cancel Modal -->
        <AppModal :show="cancelModal.show" title="Anular Venta" @close="cancelModal.show = false">
            <form @submit.prevent="submitCancel" id="cancelShowForm">
                <p class="modal-text">¿Estás seguro de anular la venta <strong>{{ sale.code }}</strong>? Se devolverá el stock y se cancelarán los créditos asociados.</p>
                <AppTextarea label="Motivo de anulación (obligatorio)" v-model="cancelForm.cancel_reason" :error="cancelForm.errors.cancel_reason" :rows="3" required />
            </form>
            <template #footer>
                <AppButton variant="secondary" @click="cancelModal.show = false">Cerrar</AppButton>
                <AppButton type="submit" form="cancelShowForm" :loading="cancelForm.processing" variant="danger">Anular Venta</AppButton>
            </template>
        </AppModal>

    </AppLayout>
</template>

<script setup>
import { reactive, computed, watch, onMounted } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AppBadge from '@/Components/AppBadge.vue'
import AppButton from '@/Components/AppButton.vue'
import AppModal from '@/Components/AppModal.vue'
import AppTextarea from '@/Components/AppTextarea.vue'

const props = defineProps({ sale: Object, items: Array, credit: Object })
const page = usePage()
const isAdmin = computed(() => page.props.auth.user.role === 'admin')
const canViewProfits = computed(() => isAdmin.value || page.props.auth.user.can_view_profits)

// Auto-print logic
onMounted(() => {
    if (page.props.flash?.print_url) {
        window.open(page.props.flash.print_url, '_blank')
    }
})

function paymentLabel(type) {
    return { cash: 'Efectivo', credit: 'Crédito', transfer: 'Transferencia', other: 'Otro' }[type] || type
}

function profit(item) {
    // Profit = subtotal - (purchase_price * quantity)
    // Note: subtotal already includes the discount
    const cost = parseFloat(item.purchase_price) * parseFloat(item.quantity)
    return parseFloat(item.subtotal) - cost
}

function formatQty(item) {
    const qty = parseFloat(item.quantity)
    const upb = parseFloat(item.product?.units_per_box || 0)
    const isBoxable = item.product?.product_type === 'caja' || upb > 0
    
    if (isBoxable && upb > 0 && qty >= upb) {
        const boxes = Math.floor(qty / upb)
        const remainder = qty % upb
        if (remainder === 0) {
            return `${boxes} Caja(s) (${qty} u.)`
        } else {
            return `${boxes} Caja(s), ${remainder} u.`
        }
    }
    return `${qty}`
}

// Direct Print Function
function printDirectly() {
    const format = page.props.system?.print_format || '80mm'
    window.open(`/sales/${props.sale.id}/print?format=${format}`, '_blank')
}

// Cancel Modal
const cancelModal = reactive({ show: false })
const cancelForm = useForm({ cancel_reason: '' })
function submitCancel() {
    cancelForm.patch(`/sales/${props.sale.id}/cancel`, {
        preserveScroll: true, onSuccess: () => { cancelModal.show = false }
    })
}
</script>

<style scoped>
.show-page { max-width: 1000px; padding-bottom: 2rem; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem; }
.back-link { display: inline-flex; align-items: center; gap: 0.35rem; color: #64748b; font-size: 0.82rem; font-weight: 500; text-decoration: none; transition: color 0.15s; }
.back-link:hover { color: #3b82f6; }

.header-actions { display: flex; gap: 0.5rem; }
.btn-print { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.5rem 1rem; background: #fff; border: 1px solid #cbd5e1; color: #334155; border-radius: 8px; font-size: 0.8rem; font-weight: 600; cursor: pointer; transition: all 0.15s; }
.btn-print:hover { border-color: #3b82f6; color: #3b82f6; background: #eff6ff; }
.btn-cancel { padding: 0.5rem 1rem; background: #fff; border: 1px solid #fca5a5; color: #ef4444; border-radius: 8px; font-size: 0.8rem; font-weight: 600; cursor: pointer; transition: all 0.15s; }
.btn-cancel:hover { background: #fef2f2; border-color: #ef4444; }

.status-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.25rem; margin-bottom: 1.5rem; border-left: 4px solid #cbd5e1; }
.status-card--active { border-left-color: #10b981; }
.status-card--cancelled { border-left-color: #ef4444; background: #fff1f2; }
.status-header { display: flex; justify-content: space-between; align-items: center; }
.status-id { font-size: 1.1rem; font-weight: 800; color: #1e293b; }
.status-date { font-size: 0.85rem; color: #64748b; }
.cancel-info { margin-top: 1rem; padding-top: 1rem; border-top: 1px dashed #fca5a5; font-size: 0.85rem; color: #b91c1c; }
.cancel-info p { margin: 0 0 0.25rem; }

.info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
.info-item { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem; display: flex; flex-direction: column; }
.info-label { font-size: 0.72rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.35rem; }
.info-value { font-size: 0.95rem; color: #1e293b; font-weight: 700; display: flex; align-items: center; gap: 0.3rem; }
.info-sub { font-size: 0.75rem; color: #94a3b8; margin-top: 0.2rem; }
.text-blue-600 { color: #2563eb; }

.pay-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
.pay-dot--cash { background: #10b981; }
.pay-dot--credit { background: #f59e0b; }
.pay-dot--transfer { background: #3b82f6; }
.pay-dot--other { background: #94a3b8; }

.credit-card { background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; padding: 1.25rem; margin-bottom: 1.5rem; border-left: 4px solid #f59e0b; }
.credit-card--paid { background: #f0fdf4; border-color: #bbf7d0; border-left-color: #10b981; }
.credit-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
.credit-title { font-size: 0.95rem; font-weight: 700; color: #92400e; margin: 0; }
.credit-card--paid .credit-title { color: #166534; }
.credit-body { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1rem; }
.credit-stat { display: flex; flex-direction: column; }
.stat-label { font-size: 0.72rem; color: #92400e; font-weight: 600; text-transform: uppercase; }
.credit-card--paid .stat-label { color: #166534; }
.stat-value { font-size: 1.1rem; font-weight: 800; color: #78350f; }
.credit-card--paid .stat-value { color: #14532d; }
.text-emerald-600 { color: #059669 !important; }
.text-red-600 { color: #dc2626 !important; }
.font-bold { font-weight: 700; }
.btn-view-credit { display: inline-block; font-size: 0.8rem; font-weight: 600; color: #d97706; text-decoration: none; }
.btn-view-credit:hover { text-decoration: underline; }

.notes-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 0.85rem 1rem; margin-bottom: 1.5rem; font-size: 0.85rem; color: #475569; }

.section-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.5rem; overflow: hidden; }
.section-title { font-size: 0.95rem; font-weight: 700; color: #334155; margin: 0 0 1rem; }

.table-responsive { overflow-x: auto; }
.items-table { width: 100%; border-collapse: collapse; min-width: 600px; }
.items-table th { font-size: 0.72rem; font-weight: 600; color: #64748b; text-transform: uppercase; text-align: left; padding: 0.65rem 0.85rem; border-bottom: 1px solid #e2e8f0; }
.items-table td { padding: 0.85rem; border-bottom: 1px solid #f1f5f9; font-size: 0.85rem; color: #334155; }
.text-right { text-align: right; }
.text-center { text-align: center; }
.bg-blue-50 { background-color: #eff6ff; }
.text-slate-400 { color: #94a3b8; }
.text-slate-500 { color: #64748b; }
.font-semibold { font-weight: 600; }
.opacity-50 { opacity: 0.5; }

.totals-area { display: flex; justify-content: flex-end; margin-top: 1.5rem; padding-top: 1rem; border-top: 2px solid #f1f5f9; }
.totals-block { width: 300px; }
.total-row { display: flex; justify-content: space-between; padding: 0.35rem 0; font-size: 0.9rem; color: #475569; font-weight: 600; }
.total-row--grand { border-top: 1px dashed #cbd5e1; padding-top: 0.75rem; margin-top: 0.5rem; font-size: 1.25rem; font-weight: 800; color: #1e293b; }

.print-options { display: flex; gap: 1rem; justify-content: center; padding: 1rem 0; }
.print-btn { display: flex; flex-direction: column; align-items: center; gap: 0.75rem; padding: 1.5rem; background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 12px; cursor: pointer; transition: all 0.15s; flex: 1; }
.print-btn:hover { border-color: #3b82f6; background: #eff6ff; transform: translateY(-2px); }
.print-icon { color: #64748b; }
.print-btn:hover .print-icon { color: #3b82f6; }
.print-btn span { font-size: 0.85rem; font-weight: 600; color: #334155; }

.modal-text { font-size: 0.85rem; color: #475569; margin: 0 0 1rem; }

@media (max-width: 640px) { .credit-body { grid-template-columns: 1fr 1fr; } }
</style>
