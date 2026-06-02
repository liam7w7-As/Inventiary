<template>
    <AppLayout>
        <template #title>Transferencias entre Sucursales</template>

        <div class="transfers-page">
            <div class="page-header">
                <div class="tabs-container">
                    <button class="tab-btn" :class="{'tab-btn--active': !filtersForm.status}" @click="setTab('')">Todas</button>
                    <button class="tab-btn tab-btn--warning" :class="{'tab-btn--active': filtersForm.status === 'pending'}" @click="setTab('pending')">
                        Pendientes
                        <span v-if="pendingCount > 0" class="tab-badge">{{ pendingCount }}</span>
                    </button>
                    <button class="tab-btn" :class="{'tab-btn--active': filtersForm.status === 'completed'}" @click="setTab('completed')">Completadas</button>
                    <button class="tab-btn" :class="{'tab-btn--active': filtersForm.status === 'rejected'}" @click="setTab('rejected')">Rechazadas</button>
                </div>
                <div class="header-actions">
                    <AppButton @click="$inertia.visit('/transfers/create')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-1"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                        Nueva Transferencia
                    </AppButton>
                </div>
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
                    <select v-model="filtersForm.product_id" class="filter-select" @change="applyFilters">
                        <option value="">Todos los productos</option>
                        <option v-for="product in products" :key="product.id" :value="product.id">{{ product.name }}</option>
                    </select>
                </div>
                <div class="filter-field filter-date">
                    <input type="date" v-model="filtersForm.date_from" class="filter-input" placeholder="Desde" @change="applyFilters" />
                    <span class="mx-1 text-slate-500">a</span>
                    <input type="date" v-model="filtersForm.date_to" class="filter-input" placeholder="Hasta" @change="applyFilters" />
                </div>
                <button v-if="hasFilters" class="filter-clear" @click="clearFilters">Limpiar</button>
            </div>

            <!-- Table -->
            <AppTable :headers="tableHeaders">
                <tr v-for="transfer in transfers.data" :key="transfer.id">
                    <td class="font-semibold">{{ transfer.product?.name || 'Producto Eliminado' }}</td>
                    <td>
                        <span class="branch-pill bg-slate-100">{{ transfer.from_branch?.name }}</span>
                    </td>
                    <td>
                        <span class="branch-pill bg-blue-50 text-blue-700">{{ transfer.to_branch?.name }}</span>
                    </td>
                    <td class="text-center font-bold">{{ parseFloat(transfer.quantity) }}</td>
                    <td>{{ transfer.requester?.name }}</td>
                    <td>{{ new Date(transfer.created_at).toLocaleDateString() }}</td>
                    <td>
                        <AppBadge :variant="statusVariant(transfer.status)">
                            {{ statusLabel(transfer.status) }}
                        </AppBadge>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn-action btn-view" @click="$inertia.visit(`/transfers/${transfer.id}`)" title="Ver Detalle">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                            <button v-if="isAdmin && transfer.status === 'pending'" class="btn-action btn-approve" @click="confirmApprove(transfer)" title="Aprobar">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            </button>
                            <button v-if="isAdmin && transfer.status === 'pending'" class="btn-action btn-reject" @click="openRejectModal(transfer)" title="Rechazar">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                            </button>
                        </div>
                    </td>
                </tr>
                <tr v-if="transfers.data.length === 0">
                    <td colspan="8" class="text-center py-8 text-slate-500">
                        No se encontraron transferencias.
                    </td>
                </tr>
            </AppTable>

            <!-- Pagination -->
            <div v-if="transfers.links && transfers.links.length > 3" class="pagination">
                <button v-for="(link, k) in transfers.links" :key="k"
                        class="page-link"
                        :class="{'page-link--active': link.active, 'opacity-50 cursor-not-allowed': !link.url}"
                        :disabled="!link.url"
                        v-html="link.label"
                        @click="link.url && $inertia.visit(link.url)">
                </button>
            </div>
        </div>

        <!-- Confirm Approve Modal -->
        <ConfirmModal 
            :show="approveModal.show"
            title="Aprobar Transferencia"
            :message="`¿Estás seguro de aprobar la transferencia de ${approveModal.transfer?.quantity} unidades de ${approveModal.transfer?.product?.name}? Se descontará el stock de ${approveModal.transfer?.from_branch?.name} y se sumará a ${approveModal.transfer?.to_branch?.name}.`"
            confirm-text="Sí, Aprobar y Mover Stock"
            confirm-variant="success"
            @close="approveModal.show = false"
            @confirm="submitApprove"
        />

        <!-- Reject Modal -->
        <AppModal :show="rejectModal.show" title="Rechazar Transferencia" @close="rejectModal.show = false" maxWidth="sm">
            <form @submit.prevent="submitReject" id="rejectForm">
                <p class="text-sm text-slate-600 mb-4">
                    Estás a punto de rechazar la transferencia solicitada por {{ rejectModal.transfer?.requester?.name }}. Por favor indica el motivo.
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
import AppTable from '@/Components/AppTable.vue'
import AppBadge from '@/Components/AppBadge.vue'
import AppButton from '@/Components/AppButton.vue'
import AppModal from '@/Components/AppModal.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'

const props = defineProps({
    transfers: Object,
    branches: Array,
    products: Array,
    filters: Object,
    pendingCount: Number
})

const page = usePage()
const isAdmin = computed(() => page.props.auth.user.role === 'admin')

const tableHeaders = ['Producto', 'Desde', 'Hacia', 'Cantidad', 'Solicitado por', 'Fecha', 'Estado', 'Acciones']

const filtersForm = reactive({
    branch_id: props.filters.branch_id || '',
    product_id: props.filters.product_id || '',
    status: props.filters.status || '',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
})

const hasFilters = computed(() => {
    return filtersForm.branch_id || filtersForm.product_id || filtersForm.date_from || filtersForm.date_to || filtersForm.status;
})

function applyFilters() {
    router.get('/transfers', filtersForm, { preserveState: true, preserveScroll: true })
}

function clearFilters() {
    filtersForm.branch_id = ''
    filtersForm.product_id = ''
    filtersForm.status = ''
    filtersForm.date_from = ''
    filtersForm.date_to = ''
    applyFilters()
}

function setTab(tab) {
    filtersForm.status = tab
    applyFilters()
}

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
const approveModal = reactive({ show: false, transfer: null })
function confirmApprove(transfer) {
    approveModal.transfer = transfer
    approveModal.show = true
}
function submitApprove() {
    router.patch(`/transfers/${approveModal.transfer.id}/approve`, {}, {
        preserveScroll: true,
        onSuccess: () => { approveModal.show = false }
    })
}

// Reject
const rejectModal = reactive({ show: false, transfer: null })
const rejectForm = useForm({ notes: '' })

function openRejectModal(transfer) {
    rejectModal.transfer = transfer
    rejectForm.reset()
    rejectForm.clearErrors()
    rejectModal.show = true
}

function submitReject() {
    rejectForm.patch(`/transfers/${rejectModal.transfer.id}/reject`, {
        preserveScroll: true,
        onSuccess: () => { rejectModal.show = false }
    })
}
</script>

<style scoped>
.transfers-page { padding-bottom: 2rem; }

.page-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem; }

/* Tabs */
.tabs-container { display: flex; gap: 0.5rem; overflow-x: auto; }
.tab-btn { padding: 0.5rem 1rem; border: none; background: transparent; color: #64748b; font-size: 0.9rem; font-weight: 600; cursor: pointer; border-radius: 8px; transition: all 0.15s; display: flex; align-items: center; gap: 0.5rem; white-space: nowrap; margin-bottom: -0.5rem; padding-bottom: 0.75rem; border-bottom: 3px solid transparent; border-bottom-left-radius: 0; border-bottom-right-radius: 0; }
.tab-btn:hover { color: #334155; }
.tab-btn--active { color: #3b82f6; border-bottom-color: #3b82f6; }
.tab-btn--warning.tab-btn--active { color: #d97706; border-bottom-color: #d97706; }
.tab-badge { background: #f59e0b; color: #fff; font-size: 0.7rem; padding: 0.1rem 0.4rem; border-radius: 10px; }

/* Filters */
.filters-bar { display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; margin-bottom: 1rem; background: #fff; padding: 1rem; border-radius: 8px; border: 1px solid #e2e8f0; }
.filter-field { display: flex; align-items: center; }
.filter-select, .filter-input { padding: 0.45rem 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.85rem; color: #334155; outline: none; }
.filter-select:focus, .filter-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59,130,246,0.1); }
.filter-date { display: flex; align-items: center; }
.filter-clear { background: transparent; border: none; color: #ef4444; font-size: 0.85rem; font-weight: 500; cursor: pointer; padding: 0.45rem 0.75rem; transition: color 0.15s; }
.filter-clear:hover { text-decoration: underline; }

/* Table Elements */
.branch-pill { display: inline-block; padding: 0.2rem 0.6rem; border-radius: 6px; font-size: 0.8rem; font-weight: 600; color: #475569; }
.bg-blue-50 { background-color: #eff6ff; }
.text-blue-700 { color: #1d4ed8; }
.bg-slate-100 { background-color: #f1f5f9; }

/* Actions */
.action-buttons { display: flex; gap: 0.4rem; }
.btn-action { width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; border: none; border-radius: 6px; cursor: pointer; transition: all 0.15s; }
.btn-view { background: #eff6ff; color: #3b82f6; }
.btn-view:hover { background: #dbeafe; }
.btn-approve { background: #f0fdf4; color: #10b981; }
.btn-approve:hover { background: #dcfce7; }
.btn-reject { background: #fef2f2; color: #ef4444; }
.btn-reject:hover { background: #fee2e2; }

/* Modal */
.form-textarea { width: 100%; padding: 0.6rem 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; outline: none; }
.form-textarea:focus { border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59,130,246,0.1); }
.btn-cancel { padding: 0.6rem 1.2rem; background: #fff; border: 1px solid #cbd5e1; color: #475569; border-radius: 6px; font-size: 0.9rem; font-weight: 600; cursor: pointer; }
.btn-submit { padding: 0.6rem 1.2rem; border: none; color: #fff; border-radius: 6px; font-size: 0.9rem; font-weight: 600; cursor: pointer; }
.btn-submit:disabled { opacity: 0.7; cursor: not-allowed; }
.btn-submit--danger { background: #ef4444; }
.btn-submit--danger:hover:not(:disabled) { background: #dc2626; }

/* Pagination */
.pagination { display: flex; flex-wrap: wrap; gap: 0.25rem; margin-top: 1.5rem; justify-content: flex-end; }
.page-link { padding: 0.4rem 0.75rem; border: 1px solid #e2e8f0; background: #fff; color: #475569; font-size: 0.85rem; border-radius: 6px; cursor: pointer; transition: all 0.15s; }
.page-link:hover:not(:disabled) { background: #f8fafc; border-color: #cbd5e1; }
.page-link--active { background: #3b82f6; color: #fff; border-color: #3b82f6; }
.page-link--active:hover:not(:disabled) { background: #2563eb; }
.cursor-not-allowed { cursor: not-allowed; }
.opacity-50 { opacity: 0.5; }
</style>
