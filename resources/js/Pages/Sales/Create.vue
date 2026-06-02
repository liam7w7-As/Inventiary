<template>
    <AppLayout>
        <template #title>Nueva Venta</template>

        <div class="pos-page">
            <!-- Header Banner for PreSale -->
            <div v-if="preSale" class="presale-banner">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
                <span>Venta generada desde Preventa <strong>#{{ preSale.id }}</strong> (Aprobada)</span>
            </div>

            <div class="pos-layout">
                <!-- PANEL IZQUIERDO: PRODUCTOS -->
                <div class="pos-left">
                    <div class="panel-card">
                        <div class="panel-header">
                            <h2 class="panel-title">Catálogo de Productos</h2>
                            <input type="text" v-model="productSearch" placeholder="Buscar por nombre o código..." class="search-input" />
                        </div>
                        <div class="products-grid">
                            <div v-for="p in filteredProducts" :key="p.id" class="product-card" @click="addProduct(p)">
                                <div class="product-info">
                                    <h3 class="product-name">{{ p.name }}</h3>
                                    <p class="product-cat">{{ p.category?.name }}</p>
                                    <p v-if="p.has_inventory" class="product-stock" :class="{'text-red': p.current_stock <= 0}">
                                        Stock: {{ parseFloat(p.current_stock) }}
                                    </p>
                                </div>
                                <div class="product-price">Bs. {{ parseFloat(p.sale_price).toFixed(2) }}</div>
                                <div v-if="p.product_type === 'caja' || (p.units_per_box > 0 && p.box_discount > 0)" class="box-badge">
                                    -{{ parseFloat(p.box_discount) }}% al llevar ≥{{ p.units_per_box }}
                                </div>
                            </div>
                            <div v-if="!filteredProducts.length" class="empty-state">
                                No se encontraron productos.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PANEL DERECHO: CARRITO Y DATOS -->
                <div class="pos-right">
                    <form @submit.prevent="submit" class="cart-form">
                        <div class="panel-card cart-panel">
                            <div class="panel-header">
                                <h2 class="panel-title">Detalle de Venta</h2>
                            </div>
                            
                            <div class="cart-items">
                                <table class="items-table">
                                    <thead>
                                        <tr>
                                            <th>Producto</th>
                                            <th style="width:60px;">Cant.</th>
                                            <th style="width:75px;">Precio</th>
                                            <th style="width:60px;">Desc.%</th>
                                            <th style="width:75px;" class="text-right">Subtotal</th>
                                            <th style="width:30px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(item, idx) in form.items" :key="idx">
                                            <td class="item-name">
                                                <div>{{ item.name }}</div>
                                                <div v-if="item.has_inventory" class="item-stock">Stock: {{ item.current_stock }}</div>
                                                <div v-if="item.units_per_box > 0" class="item-meta">Caja de {{ item.units_per_box }} u.</div>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0.01" :max="item.has_inventory ? item.current_stock : null" v-model.number="item.quantity" class="cell-input cell-input--qty" />
                                            </td>
                                            <td class="text-right">Bs. {{ item.sale_price.toFixed(2) }}</td>
                                            <td class="text-right" :class="{'text-emerald-600 font-bold': item.discount > 0}">
                                                {{ item.discount > 0 ? `-${item.discount}%` : '—' }}
                                            </td>
                                            <td class="text-right font-bold">Bs. {{ itemSubtotal(item).toFixed(2) }}</td>
                                            <td><button type="button" class="remove-btn" @click="removeItem(idx)">&times;</button></td>
                                        </tr>
                                    </tbody>
                                </table>
                                <div v-if="!form.items.length" class="empty-cart">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="color:#cbd5e1;"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
                                    <p>Carrito vacío</p>
                                </div>
                            </div>

                            <div class="cart-totals">
                                <div class="total-row">
                                    <span>Subtotal:</span>
                                    <span>Bs. {{ totalSubtotal.toFixed(2) }}</span>
                                </div>
                                <div class="total-row">
                                    <span>Descuento:</span>
                                    <span class="text-red">-Bs. {{ totalDiscount.toFixed(2) }}</span>
                                </div>
                                <div class="total-row total-row--grand">
                                    <span>TOTAL:</span>
                                    <span>Bs. {{ grandTotal.toFixed(2) }}</span>
                                </div>
                            </div>

                            <div class="cart-details">
                                <!-- Cliente -->
                                <div class="field-group">
                                    <label class="field-label">Cliente (opcional)</label>
                                    <div class="client-field__row">
                                        <div style="flex:1; position: relative;">
                                            <input v-model="clientSearch" type="text" class="search-input w-full" placeholder="Buscar cliente..." @input="searchClients" />
                                            <div v-if="clientResults.length > 0" class="search-dropdown">
                                                <button v-for="c in clientResults" :key="c.id" type="button" class="search-item" @click="selectClient(c)">
                                                    <span class="search-item__name">{{ c.name }}</span>
                                                    <span class="search-item__phone">{{ c.phone || '' }}</span>
                                                </button>
                                            </div>
                                            <div v-if="selectedClient" class="selected-client">
                                                <span>{{ selectedClient.name }}</span>
                                                <button type="button" class="remove-client" @click="removeClient">&times;</button>
                                            </div>
                                        </div>
                                        <button type="button" class="btn-add-client" @click="quickClientModal.show = true">+</button>
                                    </div>
                                </div>

                                <!-- Pago -->
                                <div class="field-group">
                                    <label class="field-label">Tipo de Pago</label>
                                    <div class="payment-options">
                                        <label class="pay-option">
                                            <input type="radio" v-model="form.payment_type" value="cash" name="pay_type" />
                                            <div class="pay-option__content">💵 Efectivo</div>
                                        </label>
                                        <label class="pay-option">
                                            <input type="radio" v-model="form.payment_type" value="credit" name="pay_type" />
                                            <div class="pay-option__content">📝 Crédito</div>
                                        </label>
                                        <label class="pay-option">
                                            <input type="radio" v-model="form.payment_type" value="transfer" name="pay_type" />
                                            <div class="pay-option__content">📱 Transf.</div>
                                        </label>
                                    </div>
                                    <div v-if="form.payment_type === 'credit'" class="credit-warning">
                                        Se generará una deuda a 30 días.
                                    </div>
                                </div>

                                <!-- Notas -->
                                <div class="field-group">
                                    <AppTextarea label="Notas / Observaciones" v-model="form.notes" :rows="1" />
                                </div>

                                <!-- Info -->
                                <div class="field-group info-group">
                                    <span class="info-badge">Sucursal: {{ branch?.name }}</span>
                                    <span class="info-badge">Caja: #{{ cashRegister?.id }}</span>
                                </div>

                                <div v-if="form.errors.items" class="error-msg">{{ form.errors.items }}</div>
                                <div v-if="form.errors.cash_register_id" class="error-msg">{{ form.errors.cash_register_id }}</div>

                                <button type="submit" class="btn-checkout" :disabled="!form.items.length || form.processing">
                                    {{ form.processing ? 'Procesando...' : 'REGISTRAR VENTA' }}
                                </button>
                            </div>
                        </div>
                    </form>
                    
                    <!-- Calculadora auxiliar -->
                    <div class="panel-card calc-panel">
                        <details class="calc-details">
                            <summary class="calc-summary">Calculadora de Cambio (No afecta la venta)</summary>
                            <div class="calc-body">
                                <div class="calc-row">
                                    <label>Total a pagar:</label>
                                    <input type="text" readonly :value="grandTotal.toFixed(2)" class="calc-input calc-input--total" />
                                </div>
                                <div class="calc-row">
                                    <label>Monto recibido:</label>
                                    <input type="number" step="0.01" v-model.number="calcReceived" class="calc-input" />
                                </div>
                                <div class="calc-row calc-row--change">
                                    <label>Cambio a devolver:</label>
                                    <div class="calc-change" :class="{'text-red': calcChange < 0}">Bs. {{ calcChange.toFixed(2) }}</div>
                                </div>
                            </div>
                        </details>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Client Modal -->
        <AppModal :show="quickClientModal.show" title="Crear Cliente Rápido" @close="quickClientModal.show = false">
            <form @submit.prevent="submitQuickClient" id="quickClientForm">
                <div class="form-stack">
                    <AppInput label="Nombre" v-model="quickClientForm.name" :error="quickClientForm.errors.name" required />
                    <AppInput label="Teléfono" v-model="quickClientForm.phone" :error="quickClientForm.errors.phone" />
                </div>
            </form>
            <template #footer>
                <AppButton variant="secondary" @click="quickClientModal.show = false">Cancelar</AppButton>
                <AppButton type="submit" form="quickClientForm" :loading="quickClientForm.processing">Crear</AppButton>
            </template>
        </AppModal>
    </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AppTextarea from '@/Components/AppTextarea.vue'
import AppInput from '@/Components/AppInput.vue'
import AppModal from '@/Components/AppModal.vue'
import AppButton from '@/Components/AppButton.vue'

const props = defineProps({ products: Array, clients: Array, cashRegister: Object, preSale: Object, branch: Object })
const page = usePage()

const form = useForm({
    branch_id: props.branch?.id,
    cash_register_id: props.cashRegister?.id,
    client_id: props.preSale?.client_id || '',
    pre_sale_id: props.preSale?.id || '',
    sale_type: props.preSale ? 'presale' : 'direct',
    payment_type: 'cash',
    notes: props.preSale?.notes || '',
    items: [],
})

// Initialize PreSale items if present
onMounted(() => {
    if (props.preSale) {
        selectedClient.value = props.preSale.client || null;
        form.items = props.preSale.items.map(i => ({
            product_id: i.product_id,
            name: i.product?.name,
            quantity: parseFloat(i.quantity),
            sale_price: parseFloat(i.sale_price),
            discount: parseFloat(i.discount),
            purchase_price: parseFloat(i.product?.purchase_price || 0),
            has_inventory: i.product?.has_inventory,
            current_stock: props.products.find(p => p.id === i.product_id)?.current_stock || 0,
            is_box: false
        }))
    }
})

// Product search
const productSearch = ref('')
const filteredProducts = computed(() => {
    const search = productSearch.value.toLowerCase()
    return props.products.filter(p => p.name.toLowerCase().includes(search) || (p.code && p.code.toLowerCase().includes(search))).slice(0, 20)
})

function addProduct(p) {
    const existingIdx = form.items.findIndex(i => i.product_id === p.id)
    if (existingIdx >= 0) {
        form.items[existingIdx].quantity += 1
    } else {
        form.items.unshift({
            product_id: p.id,
            name: p.name,
            quantity: 1,
            sale_price: parseFloat(p.sale_price),
            discount: 0,
            box_discount: parseFloat(p.box_discount || 0),
            purchase_price: parseFloat(p.purchase_price),
            has_inventory: p.has_inventory,
            current_stock: parseFloat(p.current_stock || 0),
            units_per_box: p.units_per_box,
            product_type: p.product_type
        })
    }
}
function removeItem(idx) { form.items.splice(idx, 1) }
function itemSubtotal(item) { return item.quantity * item.sale_price * (1 - (item.discount || 0) / 100) }

const totalSubtotal = computed(() => form.items.reduce((s, i) => s + i.quantity * i.sale_price, 0))
const totalDiscount = computed(() => form.items.reduce((s, i) => s + i.quantity * i.sale_price * ((i.discount || 0) / 100), 0))
const grandTotal = computed(() => totalSubtotal.value - totalDiscount.value)

import { watch } from 'vue'
// Auto discount logic
watch(() => form.items, (items) => {
    items.forEach(item => {
        if (item.units_per_box > 0 && item.quantity >= item.units_per_box) {
            item.discount = parseFloat(item.box_discount || 0)
        } else {
            item.discount = 0
        }
    })
}, { deep: true })

// Client search
const clientSearch = ref('')
const clientResults = ref([])
const selectedClient = ref(null)
let clientTimer = null
function searchClients() {
    clearTimeout(clientTimer)
    if (clientSearch.value.length < 2) { clientResults.value = []; return }
    clientTimer = setTimeout(async () => {
        try {
            const resp = await fetch(`/clients/search?q=${encodeURIComponent(clientSearch.value)}`)
            clientResults.value = await resp.json()
        } catch { clientResults.value = [] }
    }, 300)
}
function selectClient(c) {
    selectedClient.value = c; form.client_id = c.id; clientSearch.value = ''; clientResults.value = []
}
function removeClient() { selectedClient.value = null; form.client_id = '' }

function submit() { form.post('/sales') }

// Quick client
const quickClientModal = reactive({ show: false })
const quickClientForm = useForm({ name: '', phone: '', branch_id: '' })
function submitQuickClient() {
    quickClientForm.branch_id = form.branch_id
    quickClientForm.post('/clients', {
        preserveScroll: true,
        onSuccess: (p) => {
            quickClientModal.show = false
            selectedClient.value = { id: null, name: quickClientForm.name, phone: quickClientForm.phone }
            quickClientForm.reset()
        },
    })
}

// Calc
const calcReceived = ref(0)
const calcChange = computed(() => calcReceived.value - grandTotal.value)
</script>

<style scoped>
.pos-page { max-width: 100%; padding-bottom: 2rem; }
.presale-banner { display: flex; align-items: center; gap: 0.75rem; background: #f0fdf4; border: 1px solid #bbf7d0; padding: 0.85rem 1rem; border-radius: 8px; margin-bottom: 1rem; color: #166534; font-size: 0.85rem; }

.pos-layout { display: grid; grid-template-columns: 1fr minmax(420px, 480px); gap: 1.5rem; align-items: start; }

.panel-card { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; display: flex; flex-direction: column; }
.panel-header { padding: 1rem 1.25rem; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; gap: 1rem; background: #f8fafc; }
.panel-title { font-size: 0.95rem; font-weight: 700; color: #334155; margin: 0; white-space: nowrap; }

.search-input { width: 100%; max-width: 300px; padding: 0.5rem 0.75rem; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 0.82rem; outline: none; transition: border-color 0.15s; }
.search-input:focus { border-color: #3b82f6; }
.w-full { width: 100%; max-width: none; }

/* Left - Products */
.pos-left .panel-card { height: calc(100vh - 120px); }
.products-grid { padding: 1rem; display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 1rem; overflow-y: auto; flex: 1; align-content: start; }
.product-card { border: 1px solid #e2e8f0; border-radius: 8px; padding: 0.85rem; cursor: pointer; transition: all 0.15s; display: flex; flex-direction: column; background: #fff; position: relative; }
.product-card:hover { border-color: #3b82f6; box-shadow: 0 4px 12px rgba(59,130,246,0.1); transform: translateY(-2px); }
.product-info { flex: 1; margin-bottom: 0.5rem; }
.product-name { font-size: 0.85rem; font-weight: 700; color: #1e293b; margin: 0 0 0.2rem; line-height: 1.2; }
.product-cat { font-size: 0.7rem; color: #64748b; margin: 0 0 0.3rem; }
.product-stock { font-size: 0.72rem; font-weight: 600; color: #059669; margin: 0; }
.text-red { color: #ef4444; }
.product-price { font-size: 1.1rem; font-weight: 800; color: #3b82f6; text-align: right; }
.box-badge { margin-top: 0.5rem; padding: 0.35rem; font-size: 0.7rem; font-weight: 600; color: #92400e; background: #fef3c7; border: 1px solid #fde68a; border-radius: 6px; text-align: center; }
.empty-state { grid-column: 1 / -1; text-align: center; padding: 3rem 0; color: #94a3b8; font-size: 0.9rem; }

/* Right - Cart */
.pos-right { display: flex; flex-direction: column; gap: 1rem; position: sticky; top: 1.5rem; height: calc(100vh - 120px); }
.cart-panel { height: 100%; display: flex; flex-direction: column; }
.cart-items { flex: 1; overflow-y: auto; overflow-x: hidden; border-bottom: 1px solid #e2e8f0; }
.items-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
.items-table th { position: sticky; top: 0; background: #f8fafc; font-size: 0.7rem; font-weight: 600; color: #64748b; text-transform: uppercase; text-align: left; padding: 0.6rem 0.4rem; border-bottom: 1px solid #e2e8f0; z-index: 10; }
.items-table td { padding: 0.6rem 0.4rem; border-bottom: 1px solid #f1f5f9; font-size: 0.8rem; color: #334155; vertical-align: middle; word-wrap: break-word; }
.item-name { font-weight: 600; line-height: 1.2; }
.item-meta, .item-stock { font-size: 0.65rem; color: #94a3b8; font-weight: 400; margin-top: 0.1rem; }
.cell-input { width: 100%; padding: 0.25rem 0.4rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.78rem; text-align: right; font-family: inherit; }
.cell-input:focus { outline: none; border-color: #3b82f6; }
.text-right { text-align: right; }
.font-bold { font-weight: 700; color: #1e293b; }
.remove-btn { border: none; background: none; color: #ef4444; font-size: 1.2rem; cursor: pointer; padding: 0 0.3rem; }
.empty-cart { text-align: center; padding: 2rem 1rem; color: #94a3b8; display: flex; flex-direction: column; align-items: center; gap: 0.5rem; }
.empty-cart p { font-size: 0.85rem; margin: 0; }

.cart-totals { padding: 1rem 1.25rem; border-bottom: 1px solid #e2e8f0; background: #f8fafc; }
.total-row { display: flex; justify-content: space-between; padding: 0.25rem 0; font-size: 0.85rem; color: #475569; font-weight: 600; }
.total-row--grand { font-size: 1.2rem; font-weight: 800; color: #1e293b; border-top: 1px dashed #cbd5e1; margin-top: 0.5rem; padding-top: 0.5rem; }

.cart-details { padding: 1rem 1.25rem; display: flex; flex-direction: column; gap: 1rem; }
.field-group { display: flex; flex-direction: column; gap: 0.35rem; }
.field-label { font-size: 0.75rem; font-weight: 600; color: #475569; }

.client-field__row { display: flex; gap: 0.5rem; }
.search-dropdown { position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); z-index: 50; max-height: 150px; overflow-y: auto; margin-top: 2px; }
.search-item { display: flex; justify-content: space-between; width: 100%; padding: 0.5rem 0.75rem; border: none; background: none; cursor: pointer; text-align: left; }
.search-item:hover { background: #f1f5f9; }
.search-item__name { font-size: 0.8rem; font-weight: 600; color: #1e293b; }
.search-item__phone { font-size: 0.75rem; color: #64748b; }
.selected-client { display: flex; justify-content: space-between; align-items: center; padding: 0.4rem 0.75rem; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; font-size: 0.8rem; color: #1e40af; font-weight: 600; margin-top: 0.35rem; }
.remove-client { border: none; background: none; color: #3b82f6; cursor: pointer; }
.btn-add-client { width: 34px; border-radius: 8px; border: 1.5px solid #e2e8f0; background: #f8fafc; color: #3b82f6; font-size: 1.2rem; font-weight: 700; cursor: pointer; }

.payment-options { display: flex; gap: 0.5rem; }
.pay-option { flex: 1; position: relative; }
.pay-option input { position: absolute; opacity: 0; }
.pay-option__content { padding: 0.5rem 0.25rem; text-align: center; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 0.75rem; font-weight: 600; color: #64748b; cursor: pointer; transition: all 0.15s; background: #fff; }
.pay-option input:checked + .pay-option__content { border-color: #3b82f6; background: #eff6ff; color: #1d4ed8; }
.credit-warning { font-size: 0.7rem; color: #f59e0b; font-weight: 600; background: #fef3c7; padding: 0.4rem; border-radius: 6px; text-align: center; }

.info-group { flex-direction: row; justify-content: space-between; }
.info-badge { font-size: 0.7rem; background: #f1f5f9; padding: 0.2rem 0.5rem; border-radius: 4px; color: #64748b; font-weight: 500; }

.error-msg { font-size: 0.75rem; color: #ef4444; font-weight: 500; text-align: center; }
.btn-checkout { width: 100%; padding: 0.85rem; border: none; border-radius: 8px; background: linear-gradient(135deg, #10b981, #059669); color: #fff; font-size: 1rem; font-weight: 800; letter-spacing: 0.05em; cursor: pointer; transition: all 0.15s; box-shadow: 0 4px 12px rgba(16,185,129,0.2); }
.btn-checkout:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(16,185,129,0.3); }
.btn-checkout:disabled { background: #94a3b8; cursor: not-allowed; box-shadow: none; transform: none; }

/* Calc panel */
.calc-details { background: #f8fafc; }
.calc-summary { padding: 0.75rem 1.25rem; font-size: 0.8rem; font-weight: 600; color: #475569; cursor: pointer; list-style: none; }
.calc-summary::-webkit-details-marker { display: none; }
.calc-body { padding: 0 1.25rem 1.25rem; display: flex; flex-direction: column; gap: 0.5rem; }
.calc-row { display: flex; justify-content: space-between; align-items: center; }
.calc-row label { font-size: 0.75rem; font-weight: 600; color: #64748b; }
.calc-input { width: 120px; padding: 0.4rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.85rem; text-align: right; }
.calc-input--total { background: #e2e8f0; font-weight: 700; color: #1e293b; border-color: #cbd5e1; outline: none; }
.calc-row--change { margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px dashed #cbd5e1; }
.calc-change { font-size: 1.1rem; font-weight: 800; color: #10b981; }

@media (max-width: 1024px) { .pos-layout { grid-template-columns: 1fr; } .pos-left .panel-card { height: 50vh; } }
</style>
