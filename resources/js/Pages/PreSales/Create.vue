<template>
    <AppLayout>
        <template #title>Nueva Preventa</template>

        <div class="create-page">
            <a href="/pre-sales" @click.prevent="$inertia.visit('/pre-sales')" class="back-link">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                Volver a preventas
            </a>

            <form @submit.prevent="submit">
                <div class="two-columns">
                    <!-- Left -->
                    <div class="form-section">
                        <div class="form-section__header">
                            <h2 class="form-section__title">Datos Generales</h2>
                        </div>
                        <div class="form-section__body">
                            <AppSelect v-if="isAdmin" label="Sucursal" v-model="form.branch_id" :options="branchOptions" :error="form.errors.branch_id" required />
                            <div v-else class="readonly-field">
                                <span class="readonly-label">Sucursal</span>
                                <span class="readonly-value">{{ $page.props.auth.user.branch_name }}</span>
                            </div>

                            <div class="client-field">
                                <div class="client-field__row">
                                    <div style="flex:1;">
                                        <label class="field-label">Cliente (opcional)</label>
                                        <div class="search-wrap">
                                            <input v-model="clientSearch" type="text" class="search-input" placeholder="Buscar por nombre o teléfono..." @input="searchClients" />
                                            <div v-if="clientResults.length > 0" class="search-dropdown">
                                                <button v-for="c in clientResults" :key="c.id" type="button" class="search-item" @click="selectClient(c)">
                                                    <span class="search-item__name">{{ c.name }}</span>
                                                    <span class="search-item__phone">{{ c.phone || '' }}</span>
                                                </button>
                                            </div>
                                        </div>
                                        <div v-if="selectedClient" class="selected-client">
                                            <span>{{ selectedClient.name }}</span>
                                            <button type="button" class="remove-client" @click="removeClient">&times;</button>
                                        </div>
                                    </div>
                                    <button type="button" class="btn-add-client" @click="quickClientModal.show = true" title="Crear cliente rápido">+</button>
                                </div>
                            </div>

                            <div class="field-wrap">
                                <label class="field-label">Tipo de Pago Solicitado</label>
                                <div class="radio-group">
                                    <label class="radio-label">
                                        <input type="radio" v-model="form.payment_type" value="cash" />
                                        <span>Efectivo / Transferencia</span>
                                    </label>
                                    <label class="radio-label">
                                        <input type="radio" v-model="form.payment_type" value="credit" />
                                        <span>A Crédito</span>
                                    </label>
                                </div>
                            </div>

                            <div v-if="form.payment_type === 'credit'" class="credit-section">
                                <h3 class="credit-section__title">Condiciones del crédito solicitadas</h3>
                                
                                <div class="credit-grid">
                                    <div class="credit-field">
                                        <AppInput type="number" label="Número de cuotas" v-model.number="form.credit_installments" :error="form.errors.credit_installments" min="1" max="36" required />
                                        <div class="quick-options">
                                            <button type="button" @click="form.credit_installments = 1">1</button>
                                            <button type="button" @click="form.credit_installments = 3">3</button>
                                            <button type="button" @click="form.credit_installments = 6">6</button>
                                        </div>
                                    </div>
                                    <div class="credit-field">
                                        <AppInput type="number" label="Plazo total (días)" v-model.number="form.credit_period_days" :error="form.errors.credit_period_days" min="7" required />
                                        <div class="quick-options">
                                            <button type="button" @click="form.credit_period_days = 30">30d</button>
                                            <button type="button" @click="form.credit_period_days = 60">60d</button>
                                            <button type="button" @click="form.credit_period_days = 90">90d</button>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="form.credit_installments && form.credit_period_days && grandTotal > 0" class="credit-summary">
                                    Aprox. <strong>Bs. {{ (grandTotal / form.credit_installments).toFixed(2) }}</strong> por cuota 
                                    cada <strong>{{ Math.round(form.credit_period_days / form.credit_installments) }}</strong> días.
                                </div>
                            </div>

                            <AppTextarea label="Notas" v-model="form.notes" :error="form.errors.notes" :rows="2" />
                        </div>
                    </div>

                    <!-- Right -->
                    <div class="form-section">
                        <div class="form-section__header">
                            <h2 class="form-section__title">Productos</h2>
                        </div>
                        <div class="form-section__body">
                            <div class="search-wrap">
                                <input v-model="productSearch" type="text" class="search-input" placeholder="Buscar producto para agregar..." @focus="isFocused = true" @blur="onBlur" />
                                <div v-if="filteredProducts.length && (productSearch.length > 0 || isFocused)" class="search-dropdown">
                                    <button v-for="p in filteredProducts" :key="p.id" type="button" class="search-item" @click="addProduct(p)">
                                        <span class="search-item__name">{{ p.name }}</span>
                                        <span class="search-item__phone">Bs. {{ parseFloat(p.sale_price).toFixed(2) }}</span>
                                    </button>
                                </div>
                            </div>

                            <div v-if="form.errors.items" class="field-error" style="margin-top:0.5rem;">{{ form.errors.items }}</div>

                            <table v-if="form.items.length" class="items-table">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th style="width:80px;">Cant.</th>
                                        <th style="width:100px;">Precio</th>
                                        <th style="width:70px;">Desc.%</th>
                                        <th style="width:100px;">Subtotal</th>
                                        <th style="width:40px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, idx) in form.items" :key="idx">
                                        <td class="item-name">{{ item.name }}</td>
                                        <td><input type="number" step="0.01" min="0.01" v-model.number="item.quantity" class="cell-input" /></td>
                                        <td><input type="number" step="0.01" min="0" v-model.number="item.sale_price" class="cell-input" /></td>
                                        <td><input type="number" step="0.01" min="0" max="100" v-model.number="item.discount" class="cell-input" /></td>
                                        <td class="item-subtotal">Bs. {{ itemSubtotal(item).toFixed(2) }}</td>
                                        <td><button type="button" class="remove-btn" @click="removeItem(idx)">&times;</button></td>
                                    </tr>
                                </tbody>
                            </table>

                            <div v-if="form.items.length" class="totals">
                                <div class="total-row">
                                    <span>Subtotal:</span>
                                    <strong>Bs. {{ totalSubtotal.toFixed(2) }}</strong>
                                </div>
                                <div class="total-row">
                                    <span>Descuento:</span>
                                    <strong class="text-red">-Bs. {{ totalDiscount.toFixed(2) }}</strong>
                                </div>
                                <div class="total-row total-row--grand">
                                    <span>TOTAL:</span>
                                    <strong>Bs. {{ grandTotal.toFixed(2) }}</strong>
                                </div>
                            </div>

                            <div v-if="!form.items.length" class="empty-products">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="color:#cbd5e1;"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/></svg>
                                <p>Busca y agrega productos arriba</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="form.payment_type === 'credit'" class="info-alert info-alert--warning">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" x2="12" y1="9" y2="13"/><line x1="12" x2="12.01" y1="17" y2="17"/></svg>
                    <p>⚠ Las preventas a crédito siempre requieren aprobación del administrador antes de poder realizar la venta.</p>
                </div>
                <div v-else-if="hasStockForAllItems" class="info-alert info-alert--success">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <p>✓ Stock disponible. La preventa se aprobará automáticamente.</p>
                </div>
                <div v-else class="info-alert info-alert--warning">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" x2="12" y1="9" y2="13"/><line x1="12" x2="12.01" y1="17" y2="17"/></svg>
                    <p>⚠ Stock insuficiente. La preventa requerirá aprobación del administrador.</p>
                </div>

                <div class="form-actions">
                    <AppButton variant="secondary" @click="$inertia.visit('/pre-sales')">Cancelar</AppButton>
                    <AppButton type="submit" :loading="form.processing" :disabled="!form.items.length">Crear Preventa</AppButton>
                </div>
            </form>
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
import { ref, reactive, computed } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AppSelect from '@/Components/AppSelect.vue'
import AppTextarea from '@/Components/AppTextarea.vue'
import AppInput from '@/Components/AppInput.vue'
import AppButton from '@/Components/AppButton.vue'
import AppModal from '@/Components/AppModal.vue'

const props = defineProps({ clients: Array, products: Array, branches: Array })
const page = usePage()
const isAdmin = computed(() => page.props.auth.user.role === 'admin')

const branchOptions = computed(() => props.branches.map(b => ({ value: b.id, label: b.name })))

const form = useForm({
    branch_id: isAdmin.value ? '' : page.props.auth.user.branch_id,
    client_id: '',
    payment_type: 'cash',
    credit_installments: 1,
    credit_period_days: 30,
    notes: '',
    items: [],
})

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

// Product search
const productSearch = ref('')
const isFocused = ref(false)
function onBlur() { setTimeout(() => { isFocused.value = false }, 200) }
const filteredProducts = computed(() => {
    const search = productSearch.value.toLowerCase()
    if (!search) return props.products.filter(p => !form.items.find(i => i.product_id === p.id)).slice(0, 15)
    return props.products.filter(p => p.name.toLowerCase().includes(search) && !form.items.find(i => i.product_id === p.id)).slice(0, 15)
})
function addProduct(p) {
    form.items.push({ product_id: p.id, name: p.name, quantity: 1, sale_price: parseFloat(p.sale_price), discount: 0 })
    productSearch.value = ''
}
function removeItem(idx) { form.items.splice(idx, 1) }
function itemSubtotal(item) { return item.quantity * item.sale_price * (1 - (item.discount || 0) / 100) }

const totalSubtotal = computed(() => form.items.reduce((s, i) => s + i.quantity * i.sale_price, 0))
const totalDiscount = computed(() => form.items.reduce((s, i) => s + i.quantity * i.sale_price * ((i.discount || 0) / 100), 0))
const grandTotal = computed(() => totalSubtotal.value - totalDiscount.value)

const hasStockForAllItems = computed(() => {
    if (form.items.length === 0) return false;
    for (const item of form.items) {
        const product = props.products.find(p => p.id === item.product_id);
        if (product && product.has_inventory) {
            // we have product.current_stock passed from backend
            if (item.quantity > product.current_stock) {
                return false;
            }
        }
    }
    return true;
})

function submit() { form.post('/pre-sales') }

// Quick client
const quickClientModal = reactive({ show: false })
const quickClientForm = useForm({ name: '', phone: '', branch_id: '' })
function submitQuickClient() {
    quickClientForm.branch_id = isAdmin.value ? form.branch_id : page.props.auth.user.branch_id
    quickClientForm.post('/clients', {
        preserveScroll: true,
        onSuccess: (p) => {
            quickClientModal.show = false
            // After creating, set as selected
            selectedClient.value = { id: null, name: quickClientForm.name, phone: quickClientForm.phone }
            quickClientForm.reset()
        },
    })
}
</script>

<style scoped>
.create-page { max-width: 1100px; padding-bottom: 2rem; }
.back-link { display: inline-flex; align-items: center; gap: 0.35rem; color: #64748b; font-size: 0.82rem; font-weight: 500; text-decoration: none; margin-bottom: 1rem; transition: color 0.15s; }
.back-link:hover { color: #3b82f6; }

.two-columns { display: grid; grid-template-columns: 1fr 1.3fr; gap: 1.5rem; align-items: flex-start; }

.form-section { background: #fff; border-radius: 14px; border: 1px solid #e2e8f0; overflow: hidden; }
.form-section__header { padding: 1.25rem 1.5rem 0.5rem; border-bottom: 1px solid #f1f5f9; }
.form-section__title { font-size: 0.95rem; font-weight: 700; color: #334155; margin: 0; }
.form-section__body { padding: 1.25rem 1.5rem 1.5rem; display: flex; flex-direction: column; gap: 1rem; }

.readonly-field { display: flex; flex-direction: column; gap: 0.25rem; }
.readonly-label { font-size: 0.75rem; font-weight: 600; color: #64748b; }
.readonly-value { font-size: 0.9rem; color: #1e293b; font-weight: 600; padding: 0.5rem 0.75rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; }

.field-label { display: block; font-size: 0.82rem; font-weight: 600; color: #334155; margin-bottom: 0.35rem; }

.client-field__row { display: flex; gap: 0.5rem; align-items: flex-end; }
.btn-add-client { width: 38px; height: 38px; border-radius: 8px; border: 1.5px solid #e2e8f0; background: #f8fafc; color: #3b82f6; font-size: 1.2rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.15s; flex-shrink: 0; }
.btn-add-client:hover { background: #eff6ff; border-color: #3b82f6; }

.search-wrap { position: relative; }
.search-input { width: 100%; padding: 0.5rem 0.75rem; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 0.82rem; color: #334155; background: #fff; outline: none; transition: border-color 0.15s; font-family: inherit; }
.search-input:focus { border-color: #3b82f6; }
.search-input::placeholder { color: #94a3b8; }
.search-dropdown { position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 8px 25px rgba(0,0,0,0.1); z-index: 50; max-height: 200px; overflow-y: auto; margin-top: 2px; }
.search-item { display: flex; justify-content: space-between; width: 100%; padding: 0.6rem 0.85rem; border: none; background: none; cursor: pointer; text-align: left; font-family: inherit; transition: background 0.1s; }
.search-item:hover { background: #f1f5f9; }
.search-item__name { font-size: 0.85rem; font-weight: 600; color: #1e293b; }
.search-item__phone { font-size: 0.78rem; color: #64748b; }

.selected-client { display: inline-flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem; padding: 0.35rem 0.7rem; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; font-size: 0.82rem; color: #1e40af; font-weight: 500; }
.remove-client { border: none; background: none; color: #3b82f6; font-size: 1.1rem; cursor: pointer; padding: 0 0.2rem; }

/* Items Table */
.items-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
.items-table th { font-size: 0.72rem; font-weight: 600; color: #64748b; text-transform: uppercase; text-align: left; padding: 0.5rem 0.4rem; border-bottom: 1px solid #e2e8f0; }
.items-table td { padding: 0.5rem 0.4rem; border-bottom: 1px solid #f1f5f9; font-size: 0.82rem; color: #334155; }
.item-name { font-weight: 600; max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.item-subtotal { font-weight: 700; color: #1e293b; }
.cell-input { width: 100%; padding: 0.3rem 0.4rem; border: 1px solid #e2e8f0; border-radius: 4px; font-size: 0.8rem; text-align: right; font-family: inherit; }
.cell-input:focus { outline: none; border-color: #3b82f6; }
.remove-btn { border: none; background: none; color: #ef4444; font-size: 1.2rem; cursor: pointer; padding: 0; }

.totals { margin-top: 1rem; padding-top: 1rem; border-top: 2px solid #e2e8f0; }
.total-row { display: flex; justify-content: space-between; padding: 0.35rem 0; font-size: 0.85rem; color: #475569; }
.total-row strong { color: #1e293b; }
.total-row--grand { border-top: 1px dashed #cbd5e1; padding-top: 0.75rem; margin-top: 0.5rem; font-size: 1rem; }
.total-row--grand strong { font-size: 1.15rem; }
.text-red { color: #ef4444; }

.empty-products { text-align: center; padding: 2rem 1rem; color: #94a3b8; }
.empty-products p { font-size: 0.85rem; margin-top: 0.5rem; }

.info-alert { display: flex; align-items: flex-start; gap: 0.75rem; background: #f0f9ff; border: 1px solid #bae6fd; padding: 1rem; border-radius: 8px; margin: 1.5rem 0; color: #0369a1; }
.info-alert svg { flex-shrink: 0; margin-top: 0.1rem; }
.info-alert p { font-size: 0.82rem; margin: 0; line-height: 1.5; }

.form-actions { display: flex; justify-content: flex-end; gap: 0.5rem; }
.form-stack { display: flex; flex-direction: column; gap: 1rem; }
.field-error { font-size: 0.78rem; color: #ef4444; }

.radio-group { display: flex; gap: 1rem; margin-top: 0.25rem; }
.radio-label { display: flex; align-items: center; gap: 0.4rem; font-size: 0.85rem; color: #334155; cursor: pointer; }
.radio-label input { accent-color: #3b82f6; width: 16px; height: 16px; cursor: pointer; }

.credit-section { margin-top: 0.5rem; padding: 1.25rem; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; animation: fadeIn 0.3s ease; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }
.credit-section__title { font-size: 0.85rem; font-weight: 700; color: #475569; margin: 0 0 1rem 0; text-transform: uppercase; letter-spacing: 0.02em; }
.credit-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.quick-options { display: flex; gap: 0.3rem; margin-top: 0.4rem; }
.quick-options button { flex: 1; padding: 0.25rem; font-size: 0.75rem; border: 1px solid #e2e8f0; border-radius: 4px; background: #fff; color: #64748b; cursor: pointer; transition: all 0.1s; }
.quick-options button:hover { border-color: #3b82f6; color: #3b82f6; }
.credit-summary { margin-top: 1rem; padding-top: 0.75rem; border-top: 1px solid #e2e8f0; font-size: 0.85rem; color: #3b82f6; text-align: center; background: #eff6ff; border-radius: 6px; padding: 0.75rem; }

.info-alert--warning { background: #fffbeb; border-color: #fde68a; color: #d97706; }
.info-alert--success { background: #f0fdf4; border-color: #bbf7d0; color: #15803d; }

@media (max-width: 768px) { .two-columns { grid-template-columns: 1fr; } }
</style>
