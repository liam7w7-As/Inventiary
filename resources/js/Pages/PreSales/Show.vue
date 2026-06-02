<template>
    <AppLayout>
        <template #title>Preventa #{{ preSale.id }}</template>

        <div class="show-page">
            <div class="page-header">
                <a href="/pre-sales" @click.prevent="$inertia.visit('/pre-sales')" class="back-link">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                    Volver a preventas
                </a>
            </div>

            <!-- Status Card -->
            <div class="status-card" :class="`status-card--${preSale.status}`">
                <div class="status-header">
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <AppBadge :variant="statusVariant(preSale.status)" style="font-size:0.9rem;">
                            {{ statusLabel(preSale.status) }}
                        </AppBadge>
                        <AppBadge :variant="preSale.payment_type === 'credit' ? 'warning' : 'success'" style="font-size:0.9rem;">
                            {{ preSale.payment_type === 'credit' ? 'A Crédito' : 'Efectivo' }}
                        </AppBadge>
                    </div>
                    <span class="status-id">#{{ preSale.id }}</span>
                </div>
                <div v-if="preSale.approved_by" class="status-meta">
                    <span>{{ preSale.status === 'approved' ? 'Aprobada' : 'Rechazada' }} por {{ preSale.approver?.name }}</span>
                    <span>{{ new Date(preSale.approved_at).toLocaleString() }}</span>
                </div>
            </div>

            <!-- Info -->
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Sucursal</span>
                    <span class="info-value">{{ preSale.branch?.name }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Cliente</span>
                    <span class="info-value">{{ preSale.client?.name || 'Sin cliente' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Solicitado por</span>
                    <span class="info-value">{{ preSale.requester?.name }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Fecha</span>
                    <span class="info-value">{{ new Date(preSale.created_at).toLocaleString() }}</span>
                </div>
            </div>

            <div v-if="preSale.payment_type === 'credit' && preSale.status !== 'approved'" class="notes-box" style="border-left: 4px solid #f59e0b;">
                <strong>Condiciones solicitadas:</strong> 
                <br />
                {{ preSale.credit_installments }} cuotas | Plazo: {{ preSale.credit_period_days }} días
            </div>

            <div v-if="preSale.payment_type === 'credit' && preSale.status === 'approved'" class="notes-box" style="border-left: 4px solid #10b981; background: #f0fdf4;">
                <strong>Condiciones aprobadas:</strong> 
                <br />
                {{ preSale.approved_installments }} cuotas de aprox. Bs. {{ (totalAmount / preSale.approved_installments).toFixed(2) }}
                cada {{ Math.round(preSale.approved_period_days / preSale.approved_installments) }} días. 
                Plazo total: {{ preSale.approved_period_days }} días.
                <div v-if="preSale.credit_notes" style="margin-top: 0.5rem;">
                    <em>Notas: {{ preSale.credit_notes }}</em>
                </div>
            </div>

            <div v-if="preSale.notes" class="notes-box">
                <strong>Notas:</strong> {{ preSale.notes }}
            </div>

            <!-- Items Table -->
            <div class="section-card">
                <h3 class="section-title">Detalle de Productos</h3>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Precio</th>
                            <th>Descuento</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in preSale.items" :key="item.id">
                            <td class="font-semibold">{{ item.product?.name }}</td>
                            <td>{{ parseFloat(item.quantity) }}</td>
                            <td>Bs. {{ parseFloat(item.sale_price).toFixed(2) }}</td>
                            <td>{{ parseFloat(item.discount) > 0 ? `${parseFloat(item.discount)}%` : '—' }}</td>
                            <td class="font-semibold">Bs. {{ parseFloat(item.subtotal).toFixed(2) }}</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" class="total-label">TOTAL</td>
                            <td class="total-value">Bs. {{ totalAmount.toFixed(2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Actions -->
            <div v-if="isAdmin && preSale.status === 'pending'" class="action-card">
                <template v-if="preSale.payment_type === 'credit'">
                    <h3 class="section-title">Aprobar con condiciones</h3>
                    <form @submit.prevent="submitApproveWithConditions" class="approve-form">
                        <div class="approve-grid">
                            <AppInput type="number" label="Cuotas aprobadas" v-model.number="approveForm.approved_installments" :error="approveForm.errors.approved_installments" min="1" max="36" required />
                            <AppInput type="number" label="Plazo aprobado (días)" v-model.number="approveForm.approved_period_days" :error="approveForm.errors.approved_period_days" min="7" required />
                        </div>
                        <AppTextarea label="Notas del crédito (opcional)" v-model="approveForm.credit_notes" :error="approveForm.errors.credit_notes" :rows="2" />
                        <div class="action-bar">
                            <AppButton type="submit" variant="success" :loading="approveForm.processing">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                Aprobar Crédito
                            </AppButton>
                            <AppButton type="button" variant="danger" @click="rejectModal.show = true">Rechazar</AppButton>
                        </div>
                    </form>
                </template>
                <template v-else>
                    <div class="action-bar">
                        <AppButton variant="success" @click="approve">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                            Aprobar Preventa
                        </AppButton>
                        <AppButton variant="danger" @click="rejectModal.show = true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                            Rechazar
                        </AppButton>
                    </div>
                </template>
            </div>

            <div v-if="preSale.status === 'approved'" class="action-bar" style="margin-top: 1.5rem;">
                <a :href="`/sales/create?presale_id=${preSale.id}`" @click.prevent="$inertia.visit(`/sales/create?presale_id=${preSale.id}`)" class="btn-convert">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
                    Convertir a Venta
                </a>
            </div>
        </div>

        <!-- Reject Modal -->
        <AppModal :show="rejectModal.show" title="Rechazar Preventa" @close="rejectModal.show = false">
            <form @submit.prevent="submitReject" id="rejectShowForm">
                <p style="font-size:0.85rem; color:#475569; margin:0 0 1rem;">Indica el motivo del rechazo:</p>
                <AppTextarea label="Motivo (obligatorio)" v-model="rejectForm.notes" :error="rejectForm.errors.notes" :rows="3" required />
            </form>
            <template #footer>
                <AppButton variant="secondary" @click="rejectModal.show = false">Cancelar</AppButton>
                <AppButton type="submit" form="rejectShowForm" :loading="rejectForm.processing" variant="danger">Rechazar</AppButton>
            </template>
        </AppModal>
    </AppLayout>
</template>

<script setup>
import { reactive, computed } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AppBadge from '@/Components/AppBadge.vue'
import AppButton from '@/Components/AppButton.vue'
import AppModal from '@/Components/AppModal.vue'
import AppTextarea from '@/Components/AppTextarea.vue'

const props = defineProps({ preSale: Object })
const page = usePage()
const isAdmin = computed(() => page.props.auth.user.role === 'admin')

const totalAmount = computed(() => (props.preSale.items || []).reduce((s, i) => s + parseFloat(i.subtotal), 0))

function statusVariant(s) {
    return { pending: 'warning', approved: 'success', rejected: 'danger', converted: 'info' }[s] || 'gray'
}
function statusLabel(s) {
    return { pending: 'Pendiente', approved: 'Aprobada', rejected: 'Rechazada', converted: 'Convertida' }[s] || s
}

function approve() {
    if (!confirm('¿Aprobar esta preventa?')) return
    router.patch(`/pre-sales/${props.preSale.id}/approve`)
}

const approveForm = useForm({
    approved_installments: props.preSale.credit_installments || 1,
    approved_period_days: props.preSale.credit_period_days || 30,
    credit_notes: '',
})

function submitApproveWithConditions() {
    approveForm.patch(`/pre-sales/${props.preSale.id}/approve`, {
        preserveScroll: true
    })
}

const rejectModal = reactive({ show: false })
const rejectForm = useForm({ notes: '' })
function submitReject() {
    rejectForm.patch(`/pre-sales/${props.preSale.id}/reject`, {
        preserveScroll: true, onSuccess: () => { rejectModal.show = false }
    })
}
</script>

<style scoped>
.show-page { max-width: 800px; padding-bottom: 2rem; }
.page-header { margin-bottom: 1.25rem; }
.back-link { display: inline-flex; align-items: center; gap: 0.35rem; color: #64748b; font-size: 0.82rem; font-weight: 500; text-decoration: none; transition: color 0.15s; }
.back-link:hover { color: #3b82f6; }

.status-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.25rem; margin-bottom: 1.5rem; border-left: 4px solid #cbd5e1; }
.status-card--pending { border-left-color: #f59e0b; }
.status-card--approved { border-left-color: #059669; }
.status-card--rejected { border-left-color: #ef4444; }
.status-card--converted { border-left-color: #3b82f6; }
.status-header { display: flex; justify-content: space-between; align-items: center; }
.status-id { font-size: 1.1rem; font-weight: 800; color: #1e293b; }
.status-meta { display: flex; justify-content: space-between; margin-top: 0.75rem; font-size: 0.82rem; color: #64748b; }

.info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem; }
.info-item { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 0.85rem 1rem; }
.info-label { display: block; font-size: 0.72rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.25rem; }
.info-value { font-size: 0.9rem; color: #1e293b; font-weight: 600; }

.notes-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 0.85rem 1rem; margin-bottom: 1.5rem; font-size: 0.85rem; color: #475569; }
.notes-box strong { color: #334155; }

.section-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; }
.section-title { font-size: 0.95rem; font-weight: 700; color: #334155; margin: 0 0 1rem; }

.items-table { width: 100%; border-collapse: collapse; }
.items-table th { font-size: 0.72rem; font-weight: 600; color: #64748b; text-transform: uppercase; text-align: left; padding: 0.65rem 0.85rem; border-bottom: 1px solid #e2e8f0; }
.items-table td { padding: 0.85rem; border-bottom: 1px solid #f1f5f9; font-size: 0.85rem; color: #334155; }
.items-table tfoot td { border-bottom: none; padding-top: 1rem; }
.total-label { text-align: right; font-weight: 800; font-size: 0.95rem; color: #334155; }
.total-value { font-weight: 800; font-size: 1.1rem; color: #059669; }
.font-semibold { font-weight: 600; }

.action-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.5rem; margin-top: 1.5rem; }
.approve-form { display: flex; flex-direction: column; gap: 1rem; }
.approve-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.action-bar { display: flex; gap: 0.75rem; }
.btn-convert { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.65rem 1.25rem; background: linear-gradient(135deg, #059669, #10b981); color: #fff; border-radius: 8px; font-size: 0.85rem; font-weight: 600; text-decoration: none; transition: all 0.15s; }
.btn-convert:hover { box-shadow: 0 4px 14px rgba(5,150,105,0.3); transform: translateY(-1px); }
</style>
