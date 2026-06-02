<template>
    <AppLayout>
        <template #title>{{ product.name }}</template>

        <div class="show-page">
            <div class="page-header">
                <a href="/products" @click.prevent="$inertia.visit('/products')" class="back-link">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                    Volver a productos
                </a>
                <div v-if="$page.props.auth.user.role === 'admin'" class="actions">
                    <a :href="`/products/${product.id}/edit`" @click.prevent="$inertia.visit(`/products/${product.id}/edit`)" class="btn-outline">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                        Editar
                    </a>
                </div>
            </div>

            <div class="grid-layout">
                <!-- Info Column -->
                <div class="info-column">
                    <div class="info-card">
                        <div class="card-header">
                            <h2 class="product-title">{{ product.name }}</h2>
                            <AppBadge :variant="product.is_active ? 'success' : 'danger'">
                                {{ product.is_active ? 'Activo' : 'Inactivo' }}
                            </AppBadge>
                        </div>
                        
                        <div class="details-list">
                            <div class="detail-item">
                                <span class="detail-label">Categoría</span>
                                <span class="detail-value">{{ product.category?.name || '—' }}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Proveedor</span>
                                <span class="detail-value">{{ product.supplier?.name || '—' }}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Presentación</span>
                                <span class="detail-value">
                                    {{ product.product_type === 'unit' ? 'Unidad' : `Caja (${product.units_per_box} und/caja)` }}
                                </span>
                            </div>
                            <div v-if="product.description" class="detail-item detail-item--full">
                                <span class="detail-label">Descripción</span>
                                <p class="detail-text">{{ product.description }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="info-card">
                        <h3 class="card-subtitle">Precios</h3>
                        <div class="prices-wrap">
                            <div v-if="$page.props.auth.user.can_view_profits" class="price-box">
                                <span class="price-label">Precio de compra</span>
                                <span class="price-value text-slate-600">Bs. {{ parseFloat(product.purchase_price).toFixed(2) }}</span>
                            </div>
                            <div class="price-box price-box--highlight">
                                <span class="price-label">Precio de venta</span>
                                <span class="price-value text-emerald-600">Bs. {{ parseFloat(product.sale_price).toFixed(2) }}</span>
                            </div>
                            <div v-if="product.product_type === 'box' && product.box_discount > 0" class="price-box">
                                <span class="price-label">Descuento x Caja</span>
                                <span class="price-value text-blue-600">-{{ parseFloat(product.box_discount) }}%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Inventory Column -->
                <div class="inventory-column">
                    <!-- Stock by branch -->
                    <div class="info-card" v-if="product.has_inventory">
                        <div class="card-header" style="border:none; padding-bottom: 0; margin-bottom: 0.5rem;">
                            <h3 class="card-subtitle" style="margin:0;">Stock Actual</h3>
                            <a :href="`/inventory?product_id=${product.id}`" @click.prevent="$inertia.visit(`/inventory?product_id=${product.id}`)" class="manage-stock-link">
                                Inventario
                            </a>
                        </div>
                        <div class="stock-list">
                            <div v-for="bs in branch_stocks" :key="bs.id" class="stock-item">
                                <div class="stock-branch">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                                    {{ bs.branch?.name }}
                                </div>
                                <div class="stock-amount">
                                    <span class="stock-badge" :class="getStockClass(bs.current_stock, bs.min_stock)">
                                        {{ parseFloat(bs.current_stock) }}
                                    </span>
                                </div>
                            </div>
                            <div v-if="!branch_stocks.length" class="empty-state">
                                No hay registros de stock.
                            </div>
                        </div>
                    </div>
                    <div class="info-card" v-else>
                        <p class="text-slate-500 text-sm text-center">Este producto no maneja inventario.</p>
                    </div>

                    <!-- Last Movements -->
                    <div class="info-card" v-if="product.has_inventory">
                        <h3 class="card-subtitle">Últimos Movimientos</h3>
                        <div class="movements-list">
                            <div v-for="mov in movements" :key="mov.id" class="mov-item">
                                <div class="mov-icon" :class="`mov-icon--${getMovVariant(mov.movement_type)}`">
                                    <svg v-if="['in', 'transfer_in'].includes(mov.movement_type)" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                                    <svg v-else-if="['out', 'transfer_out'].includes(mov.movement_type)" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>
                                    <svg v-else xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                                </div>
                                <div class="mov-info">
                                    <div class="mov-header">
                                        <span class="mov-type">{{ getMovLabel(mov.movement_type) }}</span>
                                        <span class="mov-qty" :class="`text-${getMovVariant(mov.movement_type)}`">
                                            {{ ['in', 'transfer_in'].includes(mov.movement_type) ? '+' : (['out', 'transfer_out'].includes(mov.movement_type) ? '-' : '') }}{{ parseFloat(mov.quantity) }}
                                        </span>
                                    </div>
                                    <div class="mov-meta">
                                        <span>{{ new Date(mov.created_at).toLocaleDateString() }} {{ new Date(mov.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}</span>
                                        <span v-if="$page.props.auth.user.role === 'admin'">&bull; {{ mov.branch?.name }}</span>
                                        <span>&bull; {{ mov.user?.name }}</span>
                                    </div>
                                    <div v-if="mov.notes" class="mov-notes">
                                        {{ mov.notes }}
                                    </div>
                                </div>
                            </div>
                            <div v-if="!movements.length" class="empty-state">
                                No hay movimientos recientes.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import AppBadge from '@/Components/AppBadge.vue'

const props = defineProps({
    product: Object,
    branch_stocks: Array,
    movements: Array,
})

function getStockClass(stock, minStock) {
    if (stock <= 0) return 'stock-danger'
    if (stock <= minStock) return 'stock-warning'
    return 'stock-success'
}

function getMovVariant(type) {
    switch (type) {
        case 'in': return 'success'
        case 'out': return 'danger'
        case 'adjustment': return 'warning'
        case 'transfer_in': return 'info'
        case 'transfer_out': return 'gray'
        default: return 'gray'
    }
}

function getMovLabel(type) {
    switch (type) {
        case 'in': return 'Ingreso'
        case 'out': return 'Salida'
        case 'adjustment': return 'Ajuste'
        case 'transfer_in': return 'Transf. Entrante'
        case 'transfer_out': return 'Transf. Saliente'
        default: return type
    }
}
</script>

<style scoped>
.show-page { max-width: 1000px; padding-bottom: 2rem; }

.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }

.back-link {
    display: inline-flex; align-items: center; gap: 0.35rem; color: #64748b;
    font-size: 0.85rem; font-weight: 500; text-decoration: none; transition: color 0.15s;
}
.back-link:hover { color: #3b82f6; }

.btn-outline {
    display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.45rem 0.85rem;
    border: 1.5px solid #cbd5e1; border-radius: 8px; color: #475569; font-size: 0.8rem;
    font-weight: 600; text-decoration: none; transition: all 0.15s;
}
.btn-outline:hover { background: #f8fafc; border-color: #94a3b8; color: #1e293b; }

.grid-layout { display: grid; grid-template-columns: 2fr 1.2fr; gap: 1.5rem; align-items: flex-start; }

.info-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; }
.card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #f1f5f9; }
.product-title { font-size: 1.25rem; font-weight: 800; color: #0f172a; margin: 0; line-height: 1.2; }
.card-subtitle { font-size: 0.95rem; font-weight: 700; color: #334155; margin: 0 0 1rem; }

.details-list { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; }
.detail-item { display: flex; flex-direction: column; gap: 0.2rem; }
.detail-item--full { grid-column: 1 / -1; }
.detail-label { font-size: 0.75rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
.detail-value { font-size: 0.9rem; color: #1e293b; font-weight: 500; }
.detail-text { font-size: 0.85rem; color: #475569; line-height: 1.5; margin: 0; }

.prices-wrap { display: flex; gap: 1rem; flex-wrap: wrap; }
.price-box { flex: 1; min-width: 120px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem; display: flex; flex-direction: column; gap: 0.3rem; }
.price-box--highlight { background: #ecfdf5; border-color: #a7f3d0; }
.price-label { font-size: 0.75rem; font-weight: 600; color: #64748b; }
.price-value { font-size: 1.1rem; font-weight: 800; }
.text-slate-600 { color: #475569; }
.text-emerald-600 { color: #059669; }
.text-blue-600 { color: #2563eb; }

/* Stock */
.manage-stock-link { font-size: 0.8rem; font-weight: 600; color: #3b82f6; text-decoration: none; }
.manage-stock-link:hover { text-decoration: underline; }

.stock-list { display: flex; flex-direction: column; gap: 0.5rem; }
.stock-item { display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px dashed #e2e8f0; }
.stock-item:last-child { border-bottom: none; }
.stock-branch { display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; font-weight: 500; color: #334155; }
.stock-branch svg { color: #94a3b8; }
.stock-badge { display: inline-block; padding: 0.2rem 0.6rem; border-radius: 1rem; font-weight: 700; font-size: 0.8rem; }
.stock-success { background: #dcfce7; color: #166534; }
.stock-warning { background: #fef9c3; color: #854d0e; }
.stock-danger { background: #fee2e2; color: #991b1b; }

/* Movements */
.movements-list { display: flex; flex-direction: column; gap: 1rem; }
.mov-item { display: flex; gap: 0.75rem; align-items: flex-start; }
.mov-icon { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.mov-icon--success { background: #dcfce7; color: #166534; }
.mov-icon--danger { background: #fee2e2; color: #991b1b; }
.mov-icon--warning { background: #fef9c3; color: #854d0e; }
.mov-icon--info { background: #dbeafe; color: #1e40af; }
.mov-icon--gray { background: #f1f5f9; color: #475569; }

.mov-info { flex: 1; }
.mov-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.2rem; }
.mov-type { font-size: 0.85rem; font-weight: 600; color: #1e293b; }
.mov-qty { font-size: 0.85rem; font-weight: 800; }
.text-success { color: #059669; }
.text-danger { color: #ef4444; }
.text-warning { color: #d97706; }
.text-info { color: #2563eb; }
.text-gray { color: #64748b; }

.mov-meta { display: flex; gap: 0.4rem; font-size: 0.75rem; color: #64748b; margin-bottom: 0.3rem; }
.mov-notes { font-size: 0.75rem; color: #475569; background: #f8fafc; padding: 0.4rem 0.6rem; border-radius: 6px; border: 1px solid #f1f5f9; }

.empty-state { font-size: 0.85rem; color: #64748b; text-align: center; padding: 1.5rem 0; font-style: italic; }

@media (max-width: 768px) {
    .grid-layout { grid-template-columns: 1fr; }
}
</style>
