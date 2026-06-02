<template>
    <AppLayout>
        <template #title>Transferencia #{{ transfer.id }}</template>

        <div class="show-page">
            <div class="page-header">
                <a href="/transfers" @click.prevent="$inertia.visit('/transfers')" class="back-link">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                    Volver a transferencias
                </a>
                
                <div class="header-actions" v-if="isAdmin && transfer.status === 'pending'">
                    <button class="btn-action btn-approve" @click="confirmApprove">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                        Aprobar y Mover Stock
                    </button>
                    <button class="btn-action btn-reject" @click="openRejectModal">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                        Rechazar
                    </button>
                </div>
            </div>

            <!-- Status Banner -->
            <div class="status-card" :class="`status-card--${transfer.status}`">
                <div class="status-header">
                    <div style="display:flex; align-items:center; gap:0.75rem;">
                        <AppBadge :variant="statusVariant(transfer.status)" style="font-size:0.9rem;">
                            {{ statusLabel(transfer.status) }}
                        </AppBadge>
                        <span class="status-id">Trf #{{ transfer.id }}</span>
                    </div>
                </div>
                
                <div v-if="transfer.status === 'rejected'" class="alert-banner alert-banner--danger">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
                    <div class="alert-content">
                        <strong>Motivo de rechazo:</strong>
                        <p>{{ transfer.notes }}</p>
                    </div>
                </div>
                
                <div v-if="transfer.status === 'pending'" class="alert-banner alert-banner--warning">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
                    <span>Esta transferencia está esperando aprobación de un administrador para ejecutarse.</span>
                </div>
            </div>

            <div class="grid-layout">
                <!-- Info Grid -->
                <div class="info-section">
                    <h3 class="section-title">Detalle de la Transferencia</h3>
                    <div class="info-grid">
                        <div class="info-item info-item--full">
                            <span class="info-label">Producto</span>
                            <span class="info-value text-xl text-blue-600">{{ transfer.product?.name || 'Producto Eliminado' }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Cantidad</span>
                            <span class="info-value text-2xl text-emerald-600">{{ parseFloat(transfer.quantity) }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Fecha de Solicitud</span>
                            <span class="info-value">{{ new Date(transfer.created_at).toLocaleString() }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Desde (Origen)</span>
                            <span class="info-value">{{ transfer.from_branch?.name }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Hacia (Destino)</span>
                            <span class="info-value">{{ transfer.to_branch?.name }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Solicitado por</span>
                            <span class="info-value">{{ transfer.requester?.name }}</span>
                        </div>
                        <div class="info-item" v-if="transfer.approver">
                            <span class="info-label">Revisado por</span>
                            <span class="info-value">{{ transfer.approver?.name }}</span>
                        </div>
                        <div class="info-item" v-if="transfer.approved_at">
                            <span class="info-label">Fecha Revisión</span>
                            <span class="info-value">{{ new Date(transfer.approved_at).toLocaleString() }}</span>
                        </div>
                    </div>
                    
                    <div v-if="transfer.notes && transfer.status !== 'rejected'" class="notes-box">
                        <strong>Notas del solicitante:</strong> {{ transfer.notes }}
                    </div>
                </div>

                <!-- Movements Section -->
                <div class="movements-section" v-if="transfer.status === 'completed'">
                    <h3 class="section-title">Movimientos Generados</h3>
                    
                    <div class="movements-list">
                        <div v-for="movement in movements" :key="movement.id" class="movement-card">
                            <div class="movement-icon" :class="movement.type === 'transfer_in' ? 'icon-in' : 'icon-out'">
                                <svg v-if="movement.type === 'transfer_in'" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/></svg>
                            </div>
                            <div class="movement-info">
                                <h4>{{ movement.type === 'transfer_in' ? 'Ingreso' : 'Salida' }} (Sucursal {{ movement.branch?.name }})</h4>
                                <p>Cantidad: <strong>{{ parseFloat(movement.quantity) }}</strong></p>
                                <p class="movement-date">{{ new Date(movement.created_at).toLocaleString() }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Confirm Approve Modal -->
        <ConfirmModal 
            :show="approveModal.show"
            title="Aprobar Transferencia"
            :message="`¿Estás seguro de aprobar la transferencia de ${transfer.quantity} unidades de ${transfer.product?.name}? Se descontará el stock de ${transfer.from_branch?.name} y se sumará a ${transfer.to_branch?.name}.`"
            confirm-text="Sí, Aprobar y Mover Stock"
            confirm-variant="success"
            @close="approveModal.show = false"
            @confirm="submitApprove"
        />

        <!-- Reject Modal -->
        <AppModal :show="rejectModal.show" title="Rechazar Transferencia" @close="rejectModal.show = false" maxWidth="sm">
            <form @submit.prevent="submitReject" id="rejectForm">
                <p class="text-sm text-slate-600 mb-4">
                    Estás a punto de rechazar la transferencia solicitada por {{ transfer.requester?.name }}. Por favor indica el motivo.
                </p>
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Motivo del rechazo (obligatorio)</label>
                    <textarea v-model="rejectForm.notes" class="form-textarea" rows="3" required></textarea>
                    <div v-if="rejectForm.errors.notes" class="text-red-500 text-xs mt-1">{{ rejectForm.errors.notes }}</div>
                </div>
            </form>
            <template #footer>
                <button type="button" class="btn-cancel" @click="rejectModal.show = false">Cancelar</button>
                <button type="submit" form="rejectForm" class="btn-submit btn-submit--danger" :disabled="rejectForm.processing">Rechazar Transferencia</button>
            </template>
        </AppModal>

    </AppLayout>
</template>

<script setup>
import { reactive, computed } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AppBadge from '@/Components/AppBadge.vue'
import AppModal from '@/Components/AppModal.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'

const props = defineProps({
    transfer: Object,
    movements: Array
})

const page = usePage()
const isAdmin = computed(() => page.props.auth.user.role === 'admin')

function statusVariant(status) {
    if (status === 'pending') return 'warning'
    if (status === 'approved') return 'info'
    if (status === 'completed') return 'success'
    if (status === 'rejected') return 'danger'
    return 'secondary'
}

function statusLabel(status) {
    if (status === 'pending') return 'Pendiente'
    if (status === 'approved') return 'Aprobada'
    if (status === 'completed') return 'Completada'
    if (status === 'rejected') return 'Rechazada'
    return status
}

// Approve
const approveModal = reactive({ show: false })
function confirmApprove() {
    approveModal.show = true
}
function submitApprove() {
    router.patch(`/transfers/${props.transfer.id}/approve`, {}, {
        preserveScroll: true,
        onSuccess: () => { approveModal.show = false }
    })
}

// Reject
const rejectModal = reactive({ show: false })
const rejectForm = useForm({ notes: '' })

function openRejectModal() {
    rejectForm.reset()
    rejectForm.clearErrors()
    rejectModal.show = true
}

function submitReject() {
    rejectForm.patch(`/transfers/${props.transfer.id}/reject`, {
        preserveScroll: true,
        onSuccess: () => { rejectModal.show = false }
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
.btn-approve { background: #10b981; color: #fff; }
.btn-approve:hover { background: #059669; }
.btn-reject { background: #fff; border: 1px solid #fca5a5; color: #ef4444; }
.btn-reject:hover { background: #fef2f2; }

/* Status Card */
.status-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.25rem; margin-bottom: 1.5rem; border-left: 4px solid #cbd5e1; }
.status-card--pending { border-left-color: #f59e0b; }
.status-card--completed { border-left-color: #10b981; background: #f0fdf4; }
.status-card--rejected { border-left-color: #ef4444; background: #fef2f2; }
.status-header { display: flex; justify-content: space-between; align-items: center; }
.status-id { font-size: 1.1rem; font-weight: 800; color: #1e293b; }

.alert-banner { display: flex; gap: 0.75rem; align-items: flex-start; margin-top: 1rem; padding: 1rem; border-radius: 8px; font-size: 0.85rem; }
.alert-banner--danger { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
.alert-banner--danger svg { color: #ef4444; flex-shrink: 0; }
.alert-banner--warning { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; align-items: center; }
.alert-banner--warning svg { color: #f59e0b; flex-shrink: 0; }
.alert-content p { margin: 0.25rem 0 0 0; }

/* Layout Grid */
.grid-layout { display: grid; grid-template-columns: 3fr 2fr; gap: 1.5rem; }
@media (max-width: 768px) { .grid-layout { grid-template-columns: 1fr; } }

/* Info Section */
.info-section { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.5rem; }
.section-title { font-size: 0.95rem; font-weight: 700; color: #334155; margin: 0 0 1rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.5rem; }
.info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; margin-bottom: 1.5rem; }
.info-item { display: flex; flex-direction: column; }
.info-item--full { grid-column: 1 / -1; }
.info-label { font-size: 0.72rem; font-weight: 600; color: #64748b; text-transform: uppercase; margin-bottom: 0.2rem; }
.info-value { font-size: 0.95rem; color: #1e293b; font-weight: 600; }
.text-xl { font-size: 1.1rem; font-weight: 800; }
.text-2xl { font-size: 1.5rem; font-weight: 800; }
.text-emerald-600 { color: #059669; }
.text-blue-600 { color: #2563eb; }

.notes-box { background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 0.85rem 1rem; font-size: 0.85rem; color: #475569; }

/* Movements Section */
.movements-section { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.5rem; }
.movements-list { display: flex; flex-direction: column; gap: 1rem; }
.movement-card { display: flex; gap: 1rem; padding: 1rem; border: 1px solid #e2e8f0; border-radius: 8px; background: #f8fafc; align-items: center; }
.movement-icon { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.icon-in { background: #dcfce7; color: #16a34a; }
.icon-out { background: #fee2e2; color: #ef4444; }
.movement-info h4 { margin: 0 0 0.25rem 0; font-size: 0.85rem; color: #334155; font-weight: 700; }
.movement-info p { margin: 0; font-size: 0.85rem; color: #475569; }
.movement-date { font-size: 0.75rem !important; color: #94a3b8 !important; margin-top: 0.25rem !important; }

/* Modal */
.form-textarea { width: 100%; padding: 0.6rem 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; outline: none; }
.form-textarea:focus { border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59,130,246,0.1); }
.btn-cancel { padding: 0.6rem 1.2rem; background: #fff; border: 1px solid #cbd5e1; color: #475569; border-radius: 6px; font-size: 0.9rem; font-weight: 600; cursor: pointer; }
.btn-submit { padding: 0.6rem 1.2rem; border: none; color: #fff; border-radius: 6px; font-size: 0.9rem; font-weight: 600; cursor: pointer; }
.btn-submit:disabled { opacity: 0.7; cursor: not-allowed; }
.btn-submit--danger { background: #ef4444; }
.btn-submit--danger:hover:not(:disabled) { background: #dc2626; }
</style>
