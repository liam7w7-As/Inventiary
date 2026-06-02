<template>
    <AppLayout>
        <template #title>Preventas</template>

        <div class="page">
            <div class="page-header">
                <div>
                    <h2 class="page-title">Gestión de Preventas</h2>
                    <p class="page-desc">Solicita y aprueba pedidos antes de facturar</p>
                </div>
                <a href="/pre-sales/create" @click.prevent="$inertia.visit('/pre-sales/create')" class="btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                    Nueva Preventa
                </a>
            </div>

            <!-- Tabs -->
            <div class="tabs">
                <button class="tab" :class="{ 'tab--active': !filters.status }" @click="setStatus('')">Todas</button>
                <button class="tab" :class="{ 'tab--active': filters.status === 'pending' }" @click="setStatus('pending')">
                    Pendientes
                    <span v-if="pendingCount > 0" class="tab-badge">{{ pendingCount }}</span>
                </button>
                <button class="tab" :class="{ 'tab--active': filters.status === 'approved' }" @click="setStatus('approved')">Aprobadas</button>
                <button class="tab" :class="{ 'tab--active': filters.status === 'rejected' }" @click="setStatus('rejected')">Rechazadas</button>
                <button class="tab" :class="{ 'tab--active': filters.status === 'converted' }" @click="setStatus('converted')">Convertidas</button>
            </div>

            <!-- Filters -->
            <div class="page-filters">
                <div v-if="isAdmin" class="filter-field">
                    <select v-model="filters.branch_id" class="filter-select" @change="applyFilters">
                        <option value="">Todas las sucursales</option>
                        <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
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
            <AppTable :columns="columns" :rows="preSales.data">
                <template #cell(id)="{ row }">#{{ row.id }}</template>
                <template #cell(client)="{ row }">{{ row.client?.name || 'Sin cliente' }}</template>
                <template #cell(branch)="{ row }">{{ row.branch?.name }}</template>
                <template #cell(items_count)="{ row }">{{ row.items_count }} items</template>
                <template #cell(total)="{ row }">
                    <span class="font-semibold">Bs. {{ parseFloat(row.total_estimated || 0).toFixed(2) }}</span>
                </template>
                <template #cell(requester)="{ row }">{{ row.requester?.name }}</template>
                <template #cell(created_at)="{ row }">
                    <div class="date-cell">
                        <span class="date">{{ new Date(row.created_at).toLocaleDateString() }}</span>
                        <span class="time">{{ new Date(row.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}</span>
                    </div>
                </template>
                <template #cell(status)="{ row }">
                    <AppBadge :variant="statusVariant(row.status)">{{ statusLabel(row.status) }}</AppBadge>
                </template>
                <template #cell(actions)="{ row }">
                    <div class="actions-group">
                        <a :href="`/pre-sales/${row.id}`" @click.prevent="$inertia.visit(`/pre-sales/${row.id}`)" class="action-btn action-view" title="Ver">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </a>
                        <template v-if="isAdmin && row.status === 'pending'">
                            <button class="action-btn action-approve" title="Aprobar" @click="approvePreSale(row)">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                            </button>
                            <button class="action-btn action-reject" title="Rechazar" @click="openRejectModal(row)">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                            </button>
                        </template>
                        <button v-if="row.status === 'pending' && (isAdmin || row.requested_by === $page.props.auth.user.id)" class="action-btn action-delete" title="Eliminar" @click="confirmDelete(row)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                        </button>
                    </div>
                </template>
            </AppTable>

            <!-- Pagination -->
            <div v-if="preSales.links && preSales.last_page > 1" class="pagination">
                <template v-for="link in preSales.links" :key="link.label">
                    <button v-if="link.url" class="pagination__btn" :class="{ 'pagination__btn--active': link.active }" @click="$inertia.visit(link.url)" v-html="link.label"></button>
                    <span v-else class="pagination__disabled" v-html="link.label"></span>
                </template>
            </div>
        </div>

        <!-- Reject Modal -->
        <AppModal :show="rejectModal.show" title="Rechazar Preventa" @close="rejectModal.show = false">
            <form @submit.prevent="submitReject" id="rejectForm">
                <p class="modal-text">¿Estás seguro de rechazar la preventa <strong>#{{ rejectModal.preSale?.id }}</strong>?</p>
                <AppTextarea label="Motivo del rechazo (obligatorio)" v-model="rejectForm.notes" :error="rejectForm.errors.notes" :rows="3" required />
            </form>
            <template #footer>
                <AppButton variant="secondary" @click="rejectModal.show = false">Cancelar</AppButton>
                <AppButton type="submit" form="rejectForm" :loading="rejectForm.processing" variant="danger">Rechazar</AppButton>
            </template>
        </AppModal>

        <ConfirmModal
            :show="deleteModal.show"
            title="Eliminar Preventa"
            :message="`¿Eliminar la preventa #${deleteModal.preSale?.id}?`"
            @confirm="executeDelete"
            @cancel="deleteModal.show = false"
        />
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
import ConfirmModal from '@/Components/ConfirmModal.vue'

const props = defineProps({ preSales: Object, branches: Array, filters: Object, pendingCount: Number })
const page = usePage()
const isAdmin = computed(() => page.props.auth.user.role === 'admin')

const filters = reactive({
    status: props.filters?.status || '',
    branch_id: props.filters?.branch_id || '',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || '',
})

function applyFilters() {
    let params = { ...filters }; for (const k in params) { if (!params[k]) delete params[k] }
    router.get('/pre-sales', params, { preserveState: true, replace: true })
}
function setStatus(status) { filters.status = status; applyFilters() }
const hasFilters = computed(() => filters.branch_id || filters.date_from || filters.date_to)
function clearFilters() {
    filters.branch_id = ''; filters.date_from = ''; filters.date_to = ''
    applyFilters()
}

const columns = computed(() => {
    const cols = [
        { key: 'id', label: '#', width: '60px' },
        { key: 'client', label: 'Cliente' },
    ]
    if (isAdmin.value) cols.push({ key: 'branch', label: 'Sucursal' })
    cols.push({ key: 'items_count', label: 'Productos' })
    cols.push({ key: 'total', label: 'Total Estimado' })
    cols.push({ key: 'requester', label: 'Solicitado por' })
    cols.push({ key: 'created_at', label: 'Fecha' })
    cols.push({ key: 'status', label: 'Estado' })
    cols.push({ key: 'actions', label: 'Acciones', width: '130px' })
    return cols
})

function statusVariant(s) {
    return { pending: 'warning', approved: 'success', rejected: 'danger', converted: 'info' }[s] || 'gray'
}
function statusLabel(s) {
    return { pending: 'Pendiente', approved: 'Aprobada', rejected: 'Rechazada', converted: 'Convertida' }[s] || s
}

function approvePreSale(ps) {
    if (!confirm(`¿Aprobar la preventa #${ps.id}?`)) return
    router.patch(`/pre-sales/${ps.id}/approve`, {}, { preserveState: true })
}

const rejectModal = reactive({ show: false, preSale: null })
const rejectForm = useForm({ notes: '' })
function openRejectModal(ps) {
    rejectModal.preSale = ps; rejectForm.notes = ''; rejectForm.clearErrors(); rejectModal.show = true
}
function submitReject() {
    rejectForm.patch(`/pre-sales/${rejectModal.preSale.id}/reject`, {
        preserveScroll: true, onSuccess: () => { rejectModal.show = false }
    })
}

const deleteModal = reactive({ show: false, preSale: null })
function confirmDelete(ps) { deleteModal.preSale = ps; deleteModal.show = true }
function executeDelete() {
    router.delete(`/pre-sales/${deleteModal.preSale.id}`, { preserveState: true, onSuccess: () => { deleteModal.show = false } })
}
</script>

<style scoped>
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; }
.page-title { font-size: 1.15rem; font-weight: 800; color: #0f172a; margin: 0; }
.page-desc { font-size: 0.78rem; color: #64748b; margin: 0.15rem 0 0; }
.btn-primary { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.55rem 1rem; background: linear-gradient(135deg, #3b82f6, #6366f1); color: #fff; border-radius: 8px; border: none; font-size: 0.82rem; font-weight: 600; cursor: pointer; text-decoration: none; transition: all 0.15s; white-space: nowrap; }
.btn-primary:hover { box-shadow: 0 4px 14px rgba(59,130,246,0.3); transform: translateY(-1px); }

.tabs { display: flex; gap: 0.5rem; margin-bottom: 1.25rem; flex-wrap: wrap; }
.tab { padding: 0.5rem 1rem; border-radius: 8px; border: 1.5px solid #e2e8f0; background: #fff; color: #64748b; font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: all 0.15s; display: inline-flex; align-items: center; gap: 0.4rem; font-family: inherit; }
.tab:hover { border-color: #cbd5e1; color: #334155; }
.tab--active { background: #3b82f6; color: #fff; border-color: #3b82f6; }
.tab-badge { background: #ef4444; color: #fff; font-size: 0.7rem; font-weight: 700; padding: 0.1rem 0.4rem; border-radius: 10px; min-width: 18px; text-align: center; }
.tab--active .tab-badge { background: #fff; color: #3b82f6; }

.page-filters { display: flex; align-items: center; gap: 0.6rem; margin-bottom: 1rem; flex-wrap: wrap; }
.filter-field { flex: 1; min-width: 140px; }
.filter-input, .filter-select { width: 100%; padding: 0.5rem 0.75rem; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 0.82rem; color: #334155; background: #fff; outline: none; transition: border-color 0.15s; font-family: inherit; }
.filter-input:focus, .filter-select:focus { border-color: #3b82f6; }
.filter-date { display: flex; align-items: center; gap: 0.5rem; min-width: 250px; flex: 2; }
.filter-date span { font-size: 0.8rem; color: #64748b; }
.filter-clear { display: inline-flex; align-items: center; padding: 0.5rem 0.75rem; border: 1.5px solid #e2e8f0; border-radius: 8px; background: #fff; color: #64748b; font-size: 0.78rem; font-weight: 500; cursor: pointer; transition: all 0.15s; }
.filter-clear:hover { border-color: #cbd5e1; background: #f8fafc; color: #334155; }

.font-semibold { font-weight: 600; }
.date-cell { display: flex; flex-direction: column; gap: 0.1rem; }
.date-cell .date { font-weight: 500; color: #334155; }
.date-cell .time { font-size: 0.75rem; color: #94a3b8; }

.actions-group { display: flex; gap: 0.2rem; }
.action-btn { padding: 0.35rem; border-radius: 6px; border: none; background: none; cursor: pointer; transition: all 0.15s; display: flex; align-items: center; justify-content: center; text-decoration: none; }
.action-view { color: #64748b; } .action-view:hover { background: rgba(100,116,139,0.1); color: #334155; }
.action-approve { color: #059669; } .action-approve:hover { background: rgba(5,150,105,0.1); }
.action-reject { color: #f59e0b; } .action-reject:hover { background: rgba(245,158,11,0.1); }
.action-delete { color: #ef4444; } .action-delete:hover { background: rgba(239,68,68,0.1); }

.pagination { display: flex; align-items: center; justify-content: center; gap: 0.25rem; margin-top: 1rem; }
.pagination__btn { padding: 0.35rem 0.7rem; border-radius: 6px; border: 1px solid #e2e8f0; background: #fff; color: #475569; font-size: 0.78rem; cursor: pointer; transition: all 0.15s; font-family: inherit; }
.pagination__btn:hover { background: #f1f5f9; }
.pagination__btn--active { background: #3b82f6; color: #fff; border-color: #3b82f6; }
.pagination__disabled { padding: 0.35rem 0.7rem; color: #cbd5e1; font-size: 0.78rem; }

.modal-text { font-size: 0.85rem; color: #475569; margin: 0 0 1rem; }
</style>
