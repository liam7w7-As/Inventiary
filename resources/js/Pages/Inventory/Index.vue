<template>
    <AppLayout>
        <template #title>Inventario</template>

        <div class="inventory-page">
            <div class="page-header">
                <div>
                    <h2 class="page-title">Control de Inventario</h2>
                    <p class="page-desc">Gestiona el stock actual de tus productos</p>
                </div>
            </div>

            <!-- Tabs -->
            <div class="tabs">
                <a href="/inventory" class="tab tab--active">Stock Actual</a>
                <a href="/inventory/movements" @click.prevent="$inertia.visit('/inventory/movements')" class="tab">Historial de movimientos</a>
            </div>

            <!-- Summary Cards -->
            <div class="summary-cards">
                <div class="summary-card">
                    <div class="summary-icon text-blue-500 bg-blue-50">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.9 8.9v6.2a2 2 0 0 1-1.3 1.9l-6.5 2.5a2 2 0 0 1-1.4 0l-6.5-2.5a2 2 0 0 1-1.3-1.9V8.9a2 2 0 0 1 1.3-1.9l6.5-2.5a2 2 0 0 1 1.4 0l6.5 2.5a2 2 0 0 1 1.3 1.9Z"/><path d="M3.2 7.1 12 10.5l8.8-3.4"/><path d="M12 22.5v-12"/></svg>
                    </div>
                    <div class="summary-info">
                        <span class="summary-label">Con Stock</span>
                        <span class="summary-value">{{ summary.withStock }}</span>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="summary-icon text-amber-500 bg-amber-50">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                    </div>
                    <div class="summary-info">
                        <span class="summary-label">Stock Bajo</span>
                        <span class="summary-value text-amber-600">{{ summary.lowStock }}</span>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="summary-icon text-red-500 bg-red-50">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="4.93" x2="19.07" y1="4.93" y2="19.07"/></svg>
                    </div>
                    <div class="summary-info">
                        <span class="summary-label">Sin Stock</span>
                        <span class="summary-value text-red-600">{{ summary.noStock }}</span>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="page-filters">
                <div class="filter-field">
                    <input
                        v-model="filters.search"
                        type="text"
                        placeholder="Buscar producto..."
                        class="filter-input"
                        @input="applyFilters"
                    />
                </div>
                <div v-if="$page.props.auth.user.role === 'admin'" class="filter-field">
                    <select v-model="filters.branch_id" class="filter-select" @change="applyFilters">
                        <option value="">Todas las sucursales</option>
                        <option v-for="branch in branches" :key="branch.id" :value="branch.id">{{ branch.name }}</option>
                    </select>
                </div>
                <div class="filter-field" style="display:flex; align-items:center;">
                    <label class="form-toggle">
                        <input type="checkbox" v-model="filters.low_stock" @change="applyFilters" />
                        <span class="form-toggle__label" style="margin-left:0.5rem; font-size:0.85rem; font-weight:500; color:#475569;">
                            Solo alertas de stock bajo
                        </span>
                    </label>
                </div>
                <button v-if="hasFilters" class="filter-clear" @click="clearFilters">
                    Limpiar
                </button>
            </div>

            <!-- Table -->
            <AppTable :columns="columns" :rows="stocks.data">
                <template #cell(product)="{ row }">
                    <div style="display:flex; flex-direction:column;">
                        <span class="font-semibold text-slate-800">{{ row.product?.name }}</span>
                    </div>
                </template>
                <template #cell(category)="{ row }">
                    <AppBadge variant="gray">{{ row.product?.category?.name || '—' }}</AppBadge>
                </template>
                <template #cell(branch)="{ row }">
                    {{ row.branch?.name }}
                </template>
                <template #cell(current_stock)="{ row }">
                    <AppBadge :variant="getStockVariant(row.current_stock, row.min_stock)">
                        {{ parseFloat(row.current_stock) }}
                        <span v-if="row.product?.product_type === 'box'" style="font-size: 0.7em; margin-left: 0.2rem;">Cajas</span>
                    </AppBadge>
                </template>
                <template #cell(min_stock)="{ row }">
                    <div class="min-stock-edit">
                        <input 
                            type="number" 
                            step="0.01" 
                            v-model="row.min_stock_edit" 
                            @focus="row.min_stock_edit = row.min_stock"
                            class="min-stock-input" 
                            :placeholder="parseFloat(row.min_stock)"
                        />
                        <button class="min-stock-btn" @click="saveMinStock(row)" title="Guardar">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        </button>
                    </div>
                </template>
                <template #cell(actions)="{ row }">
                    <div class="actions-group" v-if="$page.props.auth.user.role === 'admin'">
                        <button class="action-btn action-add" title="Agregar stock" @click="openAddStock(row)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                            <span class="action-text">Ingresar</span>
                        </button>
                        <button class="action-btn action-adjust" title="Ajustar stock" @click="openAdjustStock(row)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" x2="12" y1="9" y2="13"/><line x1="12" x2="12.01" y1="17" y2="17"/></svg>
                        </button>
                    </div>
                    <span v-else class="text-slate-400" style="font-size:0.8rem;">Solo lectura</span>
                </template>
            </AppTable>

            <!-- Pagination -->
            <div v-if="stocks.links && stocks.last_page > 1" class="pagination">
                <template v-for="link in stocks.links" :key="link.label">
                    <button
                        v-if="link.url"
                        class="pagination__btn"
                        :class="{ 'pagination__btn--active': link.active }"
                        @click="$inertia.visit(link.url)"
                        v-html="link.label"
                    ></button>
                    <span v-else class="pagination__disabled" v-html="link.label"></span>
                </template>
            </div>
        </div>

        <!-- Add Stock Modal -->
        <AppModal :show="addModal.show" title="Agregar Stock" @close="addModal.show = false">
            <form @submit.prevent="submitAdd" id="addStockForm">
                <div class="modal-info">
                    <strong>{{ addModal.product?.name }}</strong>
                    <span class="text-slate-500">Sucursal: {{ addModal.branch?.name }}</span>
                    <span class="text-slate-500">Stock actual: {{ parseFloat(addModal.currentStock) }}</span>
                </div>
                <div class="form-grid">
                    <AppInput label="Cantidad a ingresar" type="number" step="0.01" v-model="addForm.quantity" :error="addForm.errors.quantity" required />
                    <AppInput label="Precio de compra (Ref.)" type="number" step="0.01" v-model="addForm.purchase_price" :error="addForm.errors.purchase_price" placeholder="Opcional" />
                </div>
                <div style="margin-top: 1rem;">
                    <AppTextarea label="Notas / Proveedor" v-model="addForm.notes" :error="addForm.errors.notes" :rows="2" />
                </div>
                <div class="result-stock">
                    <span>Stock resultante:</span>
                    <strong>{{ (parseFloat(addModal.currentStock) + (parseFloat(addForm.quantity) || 0)).toFixed(2) }}</strong>
                </div>
            </form>
            <template #footer>
                <AppButton variant="secondary" @click="addModal.show = false">Cancelar</AppButton>
                <AppButton type="submit" form="addStockForm" :loading="addForm.processing">Agregar Stock</AppButton>
            </template>
        </AppModal>

        <!-- Adjust Stock Modal -->
        <AppModal :show="adjustModal.show" title="Ajustar Stock" @close="adjustModal.show = false">
            <form @submit.prevent="submitAdjust" id="adjustStockForm">
                <div class="modal-info">
                    <strong>{{ adjustModal.product?.name }}</strong>
                    <span class="text-slate-500">Sucursal: {{ adjustModal.branch?.name }}</span>
                    <span class="text-slate-500">Stock actual: {{ parseFloat(adjustModal.currentStock) }}</span>
                </div>
                
                <div class="info-alert warning" v-if="adjustForm.quantity < 0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" x2="12" y1="9" y2="13"/><line x1="12" x2="12.01" y1="17" y2="17"/></svg>
                    <span>Se reducirá el stock. (Para mermas o pérdidas)</span>
                </div>

                <div class="form-grid">
                    <AppInput label="Cantidad a ajustar (+ o -)" type="number" step="0.01" v-model="adjustForm.quantity" :error="adjustForm.errors.quantity" required />
                </div>
                <div style="margin-top: 1rem;">
                    <AppTextarea label="Motivo del ajuste (obligatorio)" v-model="adjustForm.notes" :error="adjustForm.errors.notes" :rows="2" required />
                </div>
                <div class="result-stock" :class="{ 'result-stock--error': (parseFloat(adjustModal.currentStock) + (parseFloat(adjustForm.quantity) || 0)) < 0 }">
                    <span>Stock resultante:</span>
                    <strong>{{ (parseFloat(adjustModal.currentStock) + (parseFloat(adjustForm.quantity) || 0)).toFixed(2) }}</strong>
                </div>
            </form>
            <template #footer>
                <AppButton variant="secondary" @click="adjustModal.show = false">Cancelar</AppButton>
                <AppButton type="submit" form="adjustStockForm" :loading="adjustForm.processing" :disabled="(parseFloat(adjustModal.currentStock) + (parseFloat(adjustForm.quantity) || 0)) < 0">Guardar Ajuste</AppButton>
            </template>
        </AppModal>
    </AppLayout>
</template>

<script setup>
import { reactive, computed, ref } from 'vue'
import { router, usePage, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AppTable from '@/Components/AppTable.vue'
import AppBadge from '@/Components/AppBadge.vue'
import AppModal from '@/Components/AppModal.vue'
import AppInput from '@/Components/AppInput.vue'
import AppTextarea from '@/Components/AppTextarea.vue'
import AppButton from '@/Components/AppButton.vue'

const props = defineProps({
    stocks: Object,
    products: Array,
    branches: Array,
    filters: Object,
    summary: Object,
})

const page = usePage()
const isAdmin = computed(() => page.props.auth.user.role === 'admin')

const filters = reactive({
    search: props.filters?.search || '',
    branch_id: props.filters?.branch_id || '',
    low_stock: props.filters?.low_stock === 'true' || props.filters?.low_stock === true,
})

let debounceTimer = null

function applyFilters() {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(() => {
        let params = { ...filters }
        if (!params.search) delete params.search
        if (!params.branch_id) delete params.branch_id
        if (!params.low_stock) delete params.low_stock
        
        router.get('/inventory', params, { preserveState: true, replace: true })
    }, 300)
}

const hasFilters = computed(() => filters.search || filters.branch_id || filters.low_stock)

function clearFilters() {
    filters.search = ''
    filters.branch_id = ''
    filters.low_stock = false
    router.get('/inventory', {}, { preserveState: true, replace: true })
}

const columns = computed(() => {
    const cols = [
        { key: 'product', label: 'Producto' },
        { key: 'category', label: 'Categoría' },
    ]
    if (isAdmin.value) cols.push({ key: 'branch', label: 'Sucursal' })
    cols.push({ key: 'current_stock', label: 'Stock Actual' })
    cols.push({ key: 'min_stock', label: 'Stock Mínimo' })
    cols.push({ key: 'actions', label: 'Acciones', width: '200px' })
    return cols
})

function getStockVariant(stock, minStock) {
    if (stock <= 0) return 'danger'
    if (stock <= minStock) return 'warning'
    return 'success'
}

function saveMinStock(row) {
    if (row.min_stock_edit === undefined || row.min_stock_edit === '') return
    
    router.patch(`/inventory/${row.id}/min-stock`, {
        min_stock: row.min_stock_edit
    }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            row.min_stock = row.min_stock_edit
        }
    })
}

// Modals
const addModal = reactive({ show: false, product: null, branch: null, currentStock: 0 })
const addForm = useForm({
    branch_id: '',
    product_id: '',
    quantity: '',
    purchase_price: '',
    notes: '',
})

function openAddStock(row) {
    addModal.product = row.product
    addModal.branch = row.branch
    addModal.currentStock = row.current_stock
    
    addForm.branch_id = row.branch_id
    addForm.product_id = row.product_id
    addForm.quantity = ''
    addForm.purchase_price = ''
    addForm.notes = ''
    addForm.clearErrors()
    
    addModal.show = true
}

function submitAdd() {
    addForm.post('/inventory/add-stock', {
        preserveScroll: true,
        onSuccess: () => { addModal.show = false }
    })
}

const adjustModal = reactive({ show: false, product: null, branch: null, currentStock: 0 })
const adjustForm = useForm({
    branch_id: '',
    product_id: '',
    quantity: '',
    notes: '',
})

function openAdjustStock(row) {
    adjustModal.product = row.product
    adjustModal.branch = row.branch
    adjustModal.currentStock = row.current_stock
    
    adjustForm.branch_id = row.branch_id
    adjustForm.product_id = row.product_id
    adjustForm.quantity = ''
    adjustForm.notes = ''
    adjustForm.clearErrors()
    
    adjustModal.show = true
}

function submitAdjust() {
    adjustForm.post('/inventory/adjust-stock', {
        preserveScroll: true,
        onSuccess: () => { adjustModal.show = false }
    })
}
</script>

<style scoped>
.page-header { margin-bottom: 1.25rem; }
.page-title { font-size: 1.15rem; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -0.01em; }
.page-desc { font-size: 0.78rem; color: #64748b; margin: 0.15rem 0 0; }

/* Tabs */
.tabs { display: flex; gap: 1.5rem; border-bottom: 1px solid #e2e8f0; margin-bottom: 1.5rem; }
.tab {
    padding: 0.75rem 0.5rem; font-size: 0.9rem; font-weight: 600; color: #64748b;
    text-decoration: none; border-bottom: 2px solid transparent; transition: all 0.15s;
}
.tab:hover { color: #334155; }
.tab--active { color: #3b82f6; border-bottom-color: #3b82f6; }

/* Summary Cards */
.summary-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem; }
.summary-card {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.25rem;
    display: flex; align-items: center; gap: 1rem;
}
.summary-icon { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
.summary-info { display: flex; flex-direction: column; }
.summary-label { font-size: 0.75rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.2rem; }
.summary-value { font-size: 1.4rem; font-weight: 800; color: #1e293b; line-height: 1; }

/* Filters */
.page-filters { display: flex; align-items: center; gap: 0.6rem; margin-bottom: 1rem; flex-wrap: wrap; }
.filter-field { flex: 1; min-width: 140px; }
.filter-input, .filter-select {
    width: 100%; padding: 0.5rem 0.75rem; border: 1.5px solid #e2e8f0; border-radius: 8px;
    font-size: 0.82rem; color: #334155; background: #fff; outline: none;
    transition: border-color 0.15s; font-family: inherit;
}
.filter-input:focus, .filter-select:focus { border-color: #3b82f6; }
.filter-clear {
    display: inline-flex; align-items: center; justify-content: center; padding: 0.5rem 0.75rem;
    border: 1.5px solid #e2e8f0; border-radius: 8px; background: #fff; color: #64748b;
    font-size: 0.78rem; font-weight: 500; cursor: pointer; transition: all 0.15s;
}
.filter-clear:hover { border-color: #cbd5e1; background: #f8fafc; color: #334155; }

.font-semibold { font-weight: 600; }
.text-slate-800 { color: #1e293b; }

.min-stock-edit { display: flex; align-items: center; gap: 0.4rem; }
.min-stock-input {
    width: 60px; padding: 0.3rem 0.4rem; border: 1px solid #cbd5e1; border-radius: 4px;
    font-size: 0.8rem; text-align: center;
}
.min-stock-input:focus { outline: none; border-color: #3b82f6; }
.min-stock-btn {
    background: #f1f5f9; border: none; width: 24px; height: 24px; border-radius: 4px;
    display: flex; align-items: center; justify-content: center; cursor: pointer; color: #3b82f6;
}
.min-stock-btn:hover { background: #e2e8f0; }

/* Actions */
.actions-group { display: flex; gap: 0.4rem; }
.action-btn {
    display: inline-flex; align-items: center; gap: 0.3rem; padding: 0.35rem 0.6rem; border-radius: 6px; border: none;
    cursor: pointer; transition: all 0.15s; text-decoration: none; font-size: 0.75rem; font-weight: 600;
}
.action-add { background: #eff6ff; color: #2563eb; } .action-add:hover { background: #dbeafe; }
.action-adjust { background: #fffbeb; color: #d97706; } .action-adjust:hover { background: #fef3c7; }
.action-text { display: none; }
@media (min-width: 1024px) { .action-text { display: inline; } }

/* Pagination */
.pagination { display: flex; align-items: center; justify-content: center; gap: 0.25rem; margin-top: 1rem; }
.pagination__btn {
    padding: 0.35rem 0.7rem; border-radius: 6px; border: 1px solid #e2e8f0; background: #fff;
    color: #475569; font-size: 0.78rem; cursor: pointer; transition: all 0.15s; font-family: inherit;
}
.pagination__btn:hover { background: #f1f5f9; }
.pagination__btn--active { background: #3b82f6; color: #fff; border-color: #3b82f6; }
.pagination__disabled { padding: 0.35rem 0.7rem; color: #cbd5e1; font-size: 0.78rem; }

/* Modals */
.modal-info { display: flex; flex-direction: column; gap: 0.2rem; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px dashed #e2e8f0; }
.modal-info strong { font-size: 1rem; color: #1e293b; }
.modal-info .text-slate-500 { font-size: 0.85rem; color: #64748b; }

.form-grid { display: grid; grid-template-columns: 1fr; gap: 1rem; }
.result-stock { display: flex; justify-content: space-between; align-items: center; padding: 1rem; background: #f8fafc; border-radius: 8px; margin-top: 1.5rem; font-size: 0.95rem; }
.result-stock strong { font-size: 1.2rem; color: #0f172a; }
.result-stock--error strong { color: #ef4444; }

.info-alert { display: flex; align-items: center; gap: 0.5rem; padding: 0.8rem; border-radius: 8px; margin-bottom: 1rem; font-size: 0.82rem; font-weight: 500; }
.info-alert.warning { background: #fffbeb; color: #d97706; border: 1px solid #fde68a; }

/* Toggles */
.form-toggle { display: inline-flex; align-items: center; cursor: pointer; }
.form-toggle input[type="checkbox"] {
    width: 16px; height: 16px; border-radius: 4px; accent-color: #3b82f6; cursor: pointer;
}
</style>
