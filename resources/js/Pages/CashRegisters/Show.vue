<template>
    <AppLayout>
        <template #title>Detalle de Caja</template>

        <div class="show-page">
            <a href="/cash-registers" @click.prevent="$inertia.visit('/cash-registers')" class="back-link">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                Volver a Cajas
            </a>

            <!-- Summary cards -->
            <div class="summary-grid">
                <div class="info-card">
                    <h4 class="card-label">Encargado</h4>
                    <p class="card-value">{{ cashRegister.user?.name }}</p>
                    <p class="card-sub">{{ cashRegister.branch?.name }}</p>
                </div>
                <div class="info-card">
                    <h4 class="card-label">Saldo Inicial</h4>
                    <p class="card-value text-blue-600">Bs. {{ parseFloat(cashRegister.initial_balance).toFixed(2) }}</p>
                    <p class="card-sub" v-if="cashRegister.sale_limit">Límite: Bs. {{ parseFloat(cashRegister.sale_limit).toFixed(2) }}</p>
                    <p class="card-sub" v-else>Sin límite de venta</p>
                </div>
                <div class="info-card">
                    <h4 class="card-label">Total Ventas</h4>
                    <p class="card-value text-emerald-600">Bs. {{ parseFloat(totals.totalSales || 0).toFixed(2) }}</p>
                    <p class="card-sub">{{ totals.totalCount }} ventas completadas</p>
                </div>
                <div class="info-card">
                    <h4 class="card-label">Estado</h4>
                    <AppBadge :variant="cashRegister.status === 'open' ? 'success' : 'gray'" style="font-size:0.9rem;">
                        {{ cashRegister.status === 'open' ? 'Abierta' : 'Cerrada' }}
                    </AppBadge>
                    <p class="card-sub" style="margin-top: 0.3rem;">
                        Abierta: {{ new Date(cashRegister.opened_at).toLocaleString() }}
                    </p>
                    <p v-if="cashRegister.closed_at" class="card-sub">
                        Cerrada: {{ new Date(cashRegister.closed_at).toLocaleString() }}
                    </p>
                </div>
            </div>

            <!-- Notes -->
            <div v-if="cashRegister.notes" class="notes-box">
                <strong>Notas:</strong> {{ cashRegister.notes }}
            </div>

            <!-- Sales -->
            <div class="section-card">
                <h3 class="section-title">Ventas de la Jornada</h3>
                <AppTable :columns="salesColumns" :rows="sales">
                    <template #cell(code)="{ row }">
                        <span class="font-semibold text-slate-800">{{ row.code || `#${row.id}` }}</span>
                    </template>
                    <template #cell(client)="{ row }">{{ row.client?.name || 'Sin cliente' }}</template>
                    <template #cell(total)="{ row }">
                        <span class="font-semibold">Bs. {{ parseFloat(row.total).toFixed(2) }}</span>
                    </template>
                    <template #cell(payment_type)="{ row }">
                        <AppBadge :variant="row.payment_type === 'cash' ? 'success' : (row.payment_type === 'credit' ? 'warning' : 'info')">
                            {{ paymentLabels[row.payment_type] || row.payment_type }}
                        </AppBadge>
                    </template>
                    <template #cell(status)="{ row }">
                        <AppBadge :variant="row.status === 'completed' ? 'success' : (row.status === 'cancelled' ? 'danger' : 'gray')">
                            {{ statusLabels[row.status] || row.status }}
                        </AppBadge>
                    </template>
                    <template #cell(created_at)="{ row }">
                        {{ new Date(row.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}
                    </template>
                </AppTable>
                <p v-if="!sales.length" class="empty-state">No hay ventas registradas en esta caja.</p>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import AppTable from '@/Components/AppTable.vue'
import AppBadge from '@/Components/AppBadge.vue'

defineProps({ cashRegister: Object, sales: Array, totals: Object })

const paymentLabels = { cash: 'Efectivo', transfer: 'Transferencia', credit: 'Crédito' }
const statusLabels = { completed: 'Completada', cancelled: 'Cancelada', pending: 'Pendiente' }

const salesColumns = [
    { key: 'code', label: 'Código' },
    { key: 'client', label: 'Cliente' },
    { key: 'total', label: 'Total' },
    { key: 'payment_type', label: 'Pago' },
    { key: 'status', label: 'Estado' },
    { key: 'created_at', label: 'Hora' },
]
</script>

<style scoped>
.show-page { max-width: 900px; padding-bottom: 2rem; }
.back-link { display: inline-flex; align-items: center; gap: 0.35rem; color: #64748b; font-size: 0.82rem; font-weight: 500; text-decoration: none; margin-bottom: 1.25rem; transition: color 0.15s; }
.back-link:hover { color: #3b82f6; }

.summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
.info-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.25rem; }
.card-label { font-size: 0.75rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin: 0 0 0.5rem; }
.card-value { font-size: 1.15rem; font-weight: 800; color: #1e293b; margin: 0; }
.card-sub { font-size: 0.78rem; color: #94a3b8; margin: 0.2rem 0 0; }
.text-blue-600 { color: #2563eb; }
.text-emerald-600 { color: #059669; }

.notes-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 0.85rem 1rem; margin-bottom: 1.5rem; font-size: 0.85rem; color: #475569; }
.notes-box strong { color: #334155; }

.section-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.5rem; }
.section-title { font-size: 0.95rem; font-weight: 700; color: #334155; margin: 0 0 1rem; }

.font-semibold { font-weight: 600; }
.text-slate-800 { color: #1e293b; }
.empty-state { text-align: center; color: #94a3b8; font-size: 0.85rem; padding: 2rem 0; font-style: italic; }
</style>
