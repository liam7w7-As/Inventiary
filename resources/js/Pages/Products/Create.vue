<template>
    <AppLayout>
        <template #title>Nuevo Producto</template>

        <div class="form-page">
            <a href="/products" @click.prevent="$inertia.visit('/products')" class="back-link">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                Volver a productos
            </a>

            <form @submit.prevent="submit">
                <!-- Section 1 -->
                <div class="form-section">
                    <div class="form-section__header">
                        <h2 class="form-section__title">1. Información General</h2>
                    </div>
                    <div class="form-section__body">
                        <div class="form-grid">
                            <AppInput label="Nombre del producto" v-model="form.name" :error="form.errors.name" required />
                            <AppSelect label="Categoría" v-model="form.category_id" :options="categoryOptions" :error="form.errors.category_id" required />
                            <AppSelect label="Proveedor" v-model="form.supplier_id" :options="supplierOptions" :error="form.errors.supplier_id" placeholder="Ninguno" />
                        </div>
                        <div style="margin-top: 1rem;">
                            <AppTextarea label="Descripción" v-model="form.description" :error="form.errors.description" :rows="2" />
                        </div>

                        <div class="form-toggles">
                            <label class="form-toggle">
                                <input type="checkbox" v-model="form.is_active" />
                                <span class="form-toggle__label">
                                    <strong>Activo</strong>
                                    <small>El producto estará visible para ventas</small>
                                </span>
                            </label>
                            <label class="form-toggle">
                                <input type="checkbox" v-model="form.has_inventory" />
                                <span class="form-toggle__label">
                                    <strong>Maneja inventario</strong>
                                    <small>Activa el control de stock para este producto</small>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Section 2 -->
                <div class="form-section">
                    <div class="form-section__header">
                        <h2 class="form-section__title">2. Tipo y Presentación</h2>
                    </div>
                    <div class="form-section__body">
                        <div class="radio-group">
                            <label class="radio-option" :class="{ 'radio-option--active': form.product_type === 'unit' }">
                                <input type="radio" v-model="form.product_type" value="unit" class="sr-only" />
                                <div class="radio-option__icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>
                                </div>
                                <span class="radio-option__text">Unidad</span>
                            </label>
                            <label class="radio-option" :class="{ 'radio-option--active': form.product_type === 'box' }">
                                <input type="radio" v-model="form.product_type" value="box" class="sr-only" />
                                <div class="radio-option__icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                                </div>
                                <span class="radio-option__text">Caja</span>
                            </label>
                        </div>

                        <div v-if="form.product_type === 'box'" class="box-fields">
                            <div class="form-grid">
                                <AppInput label="Unidades por caja" type="number" v-model="form.units_per_box" :error="form.errors.units_per_box" required />
                                <div class="field-with-desc">
                                    <AppInput label="Descuento por caja (%)" type="number" step="0.01" v-model="form.box_discount" :error="form.errors.box_discount" />
                                    <small class="field-desc">Porcentaje de descuento al vender por caja completa</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 3 -->
                <div class="form-section">
                    <div class="form-section__header">
                        <h2 class="form-section__title">3. Precios</h2>
                    </div>
                    <div class="form-section__body">
                        <div class="prices-grid">
                            <div class="price-inputs">
                                <AppInput label="Precio de compra (Bs.)" type="number" step="0.01" v-model="form.purchase_price" :error="form.errors.purchase_price" required />
                                <AppInput label="Precio de venta (Bs.)" type="number" step="0.01" v-model="form.sale_price" :error="form.errors.sale_price" required />
                            </div>
                            
                            <div class="margin-card">
                                <h4 class="margin-card__title">Proyección de Ganancia</h4>
                                <div class="margin-card__stats">
                                    <div class="stat">
                                        <span class="stat-label">Ganancia Neta</span>
                                        <span class="stat-value" :class="profit >= 0 ? 'text-emerald-600' : 'text-red-500'">
                                            Bs. {{ profit.toFixed(2) }}
                                        </span>
                                    </div>
                                    <div class="stat">
                                        <span class="stat-label">Margen</span>
                                        <span class="stat-value" :class="margin >= 0 ? 'text-emerald-600' : 'text-red-500'">
                                            {{ margin.toFixed(2) }}%
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div style="margin-top: 1.25rem;">
                            <AppTextarea label="Notas de precios" v-model="form.notes" :error="form.errors.notes" :rows="2" />
                        </div>
                    </div>
                </div>

                <div class="info-alert">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                    <p>Al crear el producto se inicializará con stock 0 en todas las sucursales activas. Para agregar stock ve a Inventario.</p>
                </div>

                <div class="form-actions">
                    <AppButton variant="secondary" @click="$inertia.visit('/products')">Cancelar</AppButton>
                    <AppButton type="submit" :loading="form.processing">Guardar Producto</AppButton>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AppInput from '@/Components/AppInput.vue'
import AppSelect from '@/Components/AppSelect.vue'
import AppTextarea from '@/Components/AppTextarea.vue'
import AppButton from '@/Components/AppButton.vue'

const props = defineProps({
    categories: Array,
    suppliers: Array,
    branches: Array,
})

const categoryOptions = computed(() => props.categories.map(c => ({ value: c.id, label: c.name })))
const supplierOptions = computed(() => props.suppliers.map(s => ({ value: s.id, label: s.name })))

const form = useForm({
    name: '',
    description: '',
    category_id: '',
    supplier_id: '',
    product_type: 'unit',
    units_per_box: '',
    purchase_price: '',
    sale_price: '',
    box_discount: 0,
    notes: '',
    has_inventory: true,
    is_active: true,
})

const profit = computed(() => {
    const buy = parseFloat(form.purchase_price) || 0
    const sell = parseFloat(form.sale_price) || 0
    return sell - buy
})

const margin = computed(() => {
    const sell = parseFloat(form.sale_price) || 0
    if (sell <= 0) return 0
    return (profit.value / sell) * 100
})

function submit() {
    form.post('/products')
}
</script>

<style scoped>
.form-page { max-width: 800px; padding-bottom: 2rem; }

.back-link {
    display: inline-flex; align-items: center; gap: 0.35rem; color: #64748b;
    font-size: 0.82rem; font-weight: 500; text-decoration: none; margin-bottom: 1rem;
    transition: color 0.15s;
}
.back-link:hover { color: #3b82f6; }

.form-section {
    background: #fff; border-radius: 14px; border: 1px solid #e2e8f0; margin-bottom: 1.25rem; overflow: hidden;
}
.form-section__header { padding: 1.25rem 1.5rem 0.5rem; border-bottom: 1px solid #f1f5f9; }
.form-section__title { font-size: 0.95rem; font-weight: 700; color: #334155; margin: 0; }
.form-section__body { padding: 1.25rem 1.5rem 1.5rem; }

.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }

/* Toggles */
.form-toggles { display: flex; gap: 1rem; margin-top: 1.5rem; flex-wrap: wrap; }
.form-toggle {
    display: inline-flex; align-items: flex-start; gap: 0.65rem; cursor: pointer; flex: 1; min-width: 250px;
    padding: 0.8rem 1rem; border-radius: 8px; border: 1px solid #e2e8f0; transition: all 0.15s; background: #fafbfc;
}
.form-toggle:hover { border-color: #cbd5e1; }
.form-toggle input[type="checkbox"] {
    width: 18px; height: 18px; border-radius: 4px; accent-color: #3b82f6; margin-top: 2px; flex-shrink: 0; cursor: pointer;
}
.form-toggle__label { display: flex; flex-direction: column; }
.form-toggle__label strong { font-size: 0.85rem; color: #1e293b; font-weight: 600; }
.form-toggle__label small { font-size: 0.72rem; color: #64748b; margin-top: 0.15rem; }

/* Radio Type */
.radio-group { display: flex; gap: 1rem; }
.radio-option {
    flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.5rem;
    padding: 1rem; border: 2px solid #e2e8f0; border-radius: 10px; cursor: pointer;
    transition: all 0.2s; background: #fff;
}
.radio-option:hover { border-color: #cbd5e1; }
.radio-option--active { border-color: #3b82f6; background: #eff6ff; color: #1d4ed8; }
.radio-option__icon { color: inherit; }
.radio-option__text { font-weight: 600; font-size: 0.9rem; }
.sr-only { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0,0,0,0); border: 0; }

.box-fields { margin-top: 1.25rem; padding-top: 1.25rem; border-top: 1px dashed #cbd5e1; }
.field-with-desc { display: flex; flex-direction: column; }
.field-desc { font-size: 0.7rem; color: #64748b; margin-top: 0.3rem; }

/* Prices */
.prices-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; align-items: flex-start; }
.price-inputs { display: flex; flex-direction: column; gap: 1rem; }
.margin-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 1.25rem; height: 100%; }
.margin-card__title { font-size: 0.85rem; font-weight: 600; color: #475569; margin: 0 0 1rem; text-transform: uppercase; letter-spacing: 0.05em; }
.margin-card__stats { display: flex; flex-direction: column; gap: 1rem; }
.stat { display: flex; justify-content: space-between; align-items: center; padding-bottom: 0.5rem; border-bottom: 1px dashed #cbd5e1; }
.stat:last-child { border-bottom: none; padding-bottom: 0; }
.stat-label { font-size: 0.85rem; color: #334155; font-weight: 500; }
.stat-value { font-size: 1.1rem; font-weight: 800; }
.text-emerald-600 { color: #059669; }
.text-red-500 { color: #ef4444; }

.info-alert {
    display: flex; align-items: flex-start; gap: 0.75rem; background: #f0f9ff;
    border: 1px solid #bae6fd; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; color: #0369a1;
}
.info-alert svg { flex-shrink: 0; margin-top: 0.1rem; }
.info-alert p { font-size: 0.82rem; margin: 0; line-height: 1.5; }

.form-actions { display: flex; justify-content: flex-end; gap: 0.5rem; }

@media (max-width: 640px) {
    .form-grid { grid-template-columns: 1fr; }
    .prices-grid { grid-template-columns: 1fr; }
    .form-toggles { flex-direction: column; }
}
</style>
