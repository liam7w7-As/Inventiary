<template>
    <AppLayout>
        <template #title>Productos</template>

        <div class="products-page">
            <div class="page-header">
                <div>
                    <h2 class="page-title">Catálogo de Productos</h2>
                    <p class="page-desc">Administra tus productos y controla el stock</p>
                </div>
                <a v-if="$page.props.auth.user.role === 'admin'" href="/products/create" @click.prevent="$inertia.visit('/products/create')" class="btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                    Nuevo Producto
                </a>
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
                <div class="filter-field">
                    <select v-model="filters.category_id" class="filter-select" @change="applyFilters">
                        <option value="">Todas las categorías</option>
                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                    </select>
                </div>
                <div class="filter-field">
                    <select v-model="filters.supplier_id" class="filter-select" @change="applyFilters">
                        <option value="">Todos los proveedores</option>
                        <option v-for="sup in suppliers" :key="sup.id" :value="sup.id">{{ sup.name }}</option>
                    </select>
                </div>
                <div class="filter-field">
                    <select v-model="filters.product_type" class="filter-select" @change="applyFilters">
                        <option value="">Todos los tipos</option>
                        <option value="unit">Unidad</option>
                        <option value="box">Caja</option>
                    </select>
                </div>
                <div v-if="$page.props.auth.user.role === 'admin'" class="filter-field">
                    <select v-model="filters.is_active" class="filter-select" @change="applyFilters">
                        <option value="">Todos los estados</option>
                        <option value="1">Activos</option>
                        <option value="0">Inactivos</option>
                    </select>
                </div>
                <button v-if="hasFilters" class="filter-clear" @click="clearFilters">
                    Limpiar
                </button>
            </div>

            <!-- Table -->
            <AppTable :columns="columns" :rows="products.data">
                <template #cell(name)="{ row }">
                    <div class="product-name">
                        <span class="font-semibold text-slate-800">{{ row.name }}</span>
                    </div>
                </template>
                <template #cell(category)="{ row }">
                    <AppBadge variant="gray">{{ row.category?.name || '—' }}</AppBadge>
                </template>
                <template #cell(type)="{ row }">
                    <span v-if="row.product_type === 'unit'">Unidad</span>
                    <span v-else>Caja ({{ row.units_per_box }} und/caja)</span>
                </template>
                <template #cell(purchase_price)="{ row }">
                    <span v-if="$page.props.auth.user.can_view_profits" class="text-slate-500">
                        Bs. {{ parseFloat(row.purchase_price).toFixed(2) }}
                    </span>
                    <span v-else class="text-slate-400">***</span>
                </template>
                <template #cell(sale_price)="{ row }">
                    <span class="font-semibold text-emerald-600">Bs. {{ parseFloat(row.sale_price).toFixed(2) }}</span>
                </template>
                <template #cell(box_discount)="{ row }">
                    {{ row.product_type === 'box' && row.box_discount > 0 ? `${parseFloat(row.box_discount)}%` : '—' }}
                </template>
                <template #cell(stock)="{ row }">
                    <span v-if="!row.has_inventory" class="text-slate-400 text-xs">Sin control</span>
                    <template v-else>
                        <span v-if="$page.props.auth.user.role === 'admin'" class="font-semibold" :class="row.branch_products_sum_current_stock > 0 ? 'text-slate-800' : 'text-red-500'">
                            {{ parseFloat(row.branch_products_sum_current_stock || 0) }}
                        </span>
                        <AppBadge v-else :variant="getStockVariant(row.current_stock, row.min_stock)">
                            {{ parseFloat(row.current_stock || 0) }}
                        </AppBadge>
                    </template>
                </template>
                <template #cell(is_active)="{ row }">
                    <AppBadge :variant="row.is_active ? 'success' : 'danger'">
                        {{ row.is_active ? 'Activo' : 'Inactivo' }}
                    </AppBadge>
                </template>
                <template #cell(actions)="{ row }">
                    <div class="actions-group">
                        <a :href="`/products/${row.id}`" @click.prevent="$inertia.visit(`/products/${row.id}`)" class="action-btn action-view" title="Ver detalle">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </a>
                        <template v-if="$page.props.auth.user.role === 'admin'">
                            <a :href="`/products/${row.id}/edit`" @click.prevent="$inertia.visit(`/products/${row.id}/edit`)" class="action-btn action-edit" title="Editar">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                            </a>
                            <button class="action-btn action-toggle" :title="row.is_active ? 'Desactivar' : 'Activar'" @click="toggleProduct(row)">
                                <svg v-if="row.is_active" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                            <button class="action-btn action-delete" title="Eliminar" @click="confirmDelete(row)">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                            </button>
                        </template>
                    </div>
                </template>
            </AppTable>

            <!-- Pagination -->
            <div v-if="products.links && products.last_page > 1" class="pagination">
                <template v-for="link in products.links" :key="link.label">
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

        <ConfirmModal
            v-if="$page.props.auth.user.role === 'admin'"
            :show="deleteModal.show"
            title="Eliminar Producto"
            :message="`¿Estás seguro de eliminar el producto '${deleteModal.product?.name}'? No debe tener ventas ni inventario.`"
            @confirm="executeDelete"
            @cancel="deleteModal.show = false"
        />
    </AppLayout>
</template>

<script setup>
import { reactive, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AppTable from '@/Components/AppTable.vue'
import AppBadge from '@/Components/AppBadge.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'

const props = defineProps({
    products: Object,
    categories: Array,
    suppliers: Array,
    filters: Object,
})

const page = usePage()
const isAdmin = computed(() => page.props.auth.user.role === 'admin')
const canViewProfits = computed(() => page.props.auth.user.can_view_profits)

const filters = reactive({
    search: props.filters?.search || '',
    category_id: props.filters?.category_id || '',
    supplier_id: props.filters?.supplier_id || '',
    product_type: props.filters?.product_type || '',
    is_active: props.filters?.is_active ?? '',
})

let debounceTimer = null

function applyFilters() {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(() => {
        let params = { ...filters }
        for (const key in params) {
            if (params[key] === '' || params[key] === null) delete params[key]
        }
        router.get('/products', params, { preserveState: true, replace: true })
    }, 300)
}

const hasFilters = computed(() => Object.values(filters).some(v => v !== ''))

function clearFilters() {
    filters.search = ''
    filters.category_id = ''
    filters.supplier_id = ''
    filters.product_type = ''
    filters.is_active = ''
    router.get('/products', {}, { preserveState: true, replace: true })
}

const columns = computed(() => {
    const cols = [
        { key: 'name', label: 'Nombre' },
        { key: 'category', label: 'Categoría' },
        { key: 'type', label: 'Tipo' },
    ]
    if (canViewProfits.value) cols.push({ key: 'purchase_price', label: 'Precio Compra' })
    cols.push({ key: 'sale_price', label: 'Precio Venta' })
    cols.push({ key: 'box_discount', label: 'Desc. Caja' })
    cols.push({ key: 'stock', label: isAdmin.value ? 'Stock Total' : 'Mi Stock' })
    
    if (isAdmin.value) cols.push({ key: 'is_active', label: 'Estado' })
    
    cols.push({ key: 'actions', label: 'Acciones', width: '130px' })
    return cols
})

function getStockVariant(stock, minStock) {
    if (stock <= 0) return 'danger'
    if (stock <= minStock) return 'warning'
    return 'success'
}

function toggleProduct(product) {
    if (!isAdmin.value) return
    router.patch(`/products/${product.id}/toggle-status`, {}, { preserveState: true })
}

const deleteModal = reactive({ show: false, product: null })

function confirmDelete(product) {
    if (!isAdmin.value) return
    deleteModal.product = product
    deleteModal.show = true
}

function executeDelete() {
    router.delete(`/products/${deleteModal.product.id}`, {
        preserveState: true,
        onSuccess: () => { deleteModal.show = false },
    })
}
</script>

<style scoped>
.page-header {
    display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem;
}
.page-title { font-size: 1.15rem; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -0.01em; }
.page-desc { font-size: 0.78rem; color: #64748b; margin: 0.15rem 0 0; }
.btn-primary {
    display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.55rem 1rem;
    background: linear-gradient(135deg, #3b82f6, #6366f1); color: #fff;
    border-radius: 8px; font-size: 0.82rem; font-weight: 600; text-decoration: none;
    transition: all 0.15s; white-space: nowrap;
}
.btn-primary:hover { box-shadow: 0 4px 14px rgba(59,130,246,0.3); transform: translateY(-1px); }

/* Filters */
.page-filters { display: flex; align-items: center; gap: 0.6rem; margin-bottom: 1rem; flex-wrap: wrap; }
.filter-field { flex: 1; min-width: 140px; }
.filter-input, .filter-select {
    width: 100%; padding: 0.5rem 0.75rem; border: 1.5px solid #e2e8f0; border-radius: 8px;
    font-size: 0.82rem; color: #334155; background: #fff; outline: none;
    transition: border-color 0.15s; font-family: inherit;
}
.filter-input:focus, .filter-select:focus { border-color: #3b82f6; }
.filter-input::placeholder { color: #94a3b8; }
.filter-clear {
    display: inline-flex; align-items: center; justify-content: center; padding: 0.5rem 0.75rem;
    border: 1.5px solid #e2e8f0; border-radius: 8px; background: #fff; color: #64748b;
    font-size: 0.78rem; font-weight: 500; cursor: pointer; transition: all 0.15s;
}
.filter-clear:hover { border-color: #cbd5e1; background: #f8fafc; color: #334155; }

.font-semibold { font-weight: 600; }
.text-slate-800 { color: #1e293b; }
.text-slate-500 { color: #64748b; }
.text-slate-400 { color: #94a3b8; }
.text-emerald-600 { color: #059669; }
.text-red-500 { color: #ef4444; }
.text-xs { font-size: 0.75rem; }

/* Actions */
.actions-group { display: flex; gap: 0.2rem; }
.action-btn {
    padding: 0.35rem; border-radius: 6px; border: none; background: none; cursor: pointer;
    transition: all 0.15s; display: flex; align-items: center; justify-content: center;
    text-decoration: none;
}
.action-view { color: #64748b; } .action-view:hover { background: rgba(100,116,139,0.1); color: #334155; }
.action-edit { color: #3b82f6; } .action-edit:hover { background: rgba(59,130,246,0.1); }
.action-toggle { color: #f59e0b; } .action-toggle:hover { background: rgba(245,158,11,0.1); }
.action-delete { color: #ef4444; } .action-delete:hover { background: rgba(239,68,68,0.1); }

/* Pagination */
.pagination { display: flex; align-items: center; justify-content: center; gap: 0.25rem; margin-top: 1rem; }
.pagination__btn {
    padding: 0.35rem 0.7rem; border-radius: 6px; border: 1px solid #e2e8f0; background: #fff;
    color: #475569; font-size: 0.78rem; cursor: pointer; transition: all 0.15s; font-family: inherit;
}
.pagination__btn:hover { background: #f1f5f9; }
.pagination__btn--active { background: #3b82f6; color: #fff; border-color: #3b82f6; }
.pagination__disabled { padding: 0.35rem 0.7rem; color: #cbd5e1; font-size: 0.78rem; }
</style>
