<template>
    <AppLayout>
        <template #title>Nueva Transferencia</template>

        <div class="create-page">
            <div class="page-header">
                <a href="/transfers" @click.prevent="$inertia.visit('/transfers')" class="back-link">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                    Volver a transferencias
                </a>
            </div>

            <div class="form-card">
                <div class="info-banner">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
                    <div>
                        <strong>Información Importante:</strong>
                        <p>La transferencia quedará en estado <em>Pendiente</em> hasta que un administrador la apruebe. El stock se moverá automáticamente al ser aprobada.</p>
                    </div>
                </div>

                <form @submit.prevent="submit" class="transfer-form">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Desde (Sucursal Origen)</label>
                            <select v-model="form.from_branch_id" class="form-input" required>
                                <option value="" disabled>Seleccionar origen...</option>
                                <option v-for="b in availableFromBranches" :key="b.id" :value="b.id">{{ b.name }}</option>
                            </select>
                            <div v-if="form.errors.from_branch_id" class="form-error">{{ form.errors.from_branch_id }}</div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Hacia (Sucursal Destino)</label>
                            <select v-model="form.to_branch_id" class="form-input" :disabled="!isAdmin" required>
                                <option value="" disabled>Seleccionar destino...</option>
                                <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                            </select>
                            <div v-if="form.errors.to_branch_id" class="form-error">{{ form.errors.to_branch_id }}</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Producto</label>
                        <select v-model="form.product_id" class="form-input" required>
                            <option value="" disabled>Buscar o seleccionar producto...</option>
                            <option v-for="p in products" :key="p.id" :value="p.id">
                                {{ p.name }}
                            </option>
                        </select>
                        <div v-if="form.errors.product_id" class="form-error">{{ form.errors.product_id }}</div>
                    </div>

                    <div v-if="form.product_id && form.from_branch_id" class="stock-info" :class="{'stock-info--empty': currentStock <= 0}">
                        <span class="stock-label">Stock disponible en origen:</span>
                        <span class="stock-value">{{ currentStock }} unidades</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Cantidad a transferir</label>
                        <input type="number" step="0.01" min="0.01" v-model="form.quantity" class="form-input" required />
                        <div v-if="form.errors.quantity" class="form-error">{{ form.errors.quantity }}</div>
                        <div v-if="form.quantity > currentStock" class="form-warning">
                            La cantidad solicitada supera el stock actual disponible en el origen.
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Notas o justificación (opcional)</label>
                        <textarea v-model="form.notes" class="form-textarea" rows="3"></textarea>
                        <div v-if="form.errors.notes" class="form-error">{{ form.errors.notes }}</div>
                    </div>

                    <div class="form-actions">
                        <AppButton type="button" variant="secondary" @click="$inertia.visit('/transfers')">Cancelar</AppButton>
                        <AppButton type="submit" :loading="form.processing" :disabled="!isValid">Solicitar Transferencia</AppButton>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AppButton from '@/Components/AppButton.vue'

const props = defineProps({
    branches: Array,
    products: Array,
    defaultToBranch: [Number, String]
})

const page = usePage()
const isAdmin = computed(() => page.props.auth.user.role === 'admin')

const form = useForm({
    from_branch_id: '',
    to_branch_id: props.defaultToBranch || '',
    product_id: '',
    quantity: '',
    notes: ''
})

const availableFromBranches = computed(() => {
    if (isAdmin.value) return props.branches
    // Encargado solo puede pedir a OTRA sucursal
    return props.branches.filter(b => b.id !== page.props.auth.user.branch_id)
})

const currentStock = computed(() => {
    if (!form.product_id || !form.from_branch_id) return 0
    const product = props.products.find(p => p.id === form.product_id)
    if (!product || !product.branch_products) return 0
    const branchStock = product.branch_products.find(bp => bp.branch_id === form.from_branch_id)
    return branchStock ? parseFloat(branchStock.current_stock) : 0
})

const isValid = computed(() => {
    return form.from_branch_id && form.to_branch_id && form.product_id && form.quantity > 0 && form.from_branch_id !== form.to_branch_id
})

function submit() {
    form.post('/transfers')
}
</script>

<style scoped>
.create-page { max-width: 700px; padding-bottom: 2rem; margin: 0 auto; }
.page-header { margin-bottom: 1.25rem; }
.back-link { display: inline-flex; align-items: center; gap: 0.35rem; color: #64748b; font-size: 0.85rem; font-weight: 500; text-decoration: none; transition: color 0.15s; }
.back-link:hover { color: #3b82f6; }

.form-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 2rem; }

.info-banner { display: flex; gap: 1rem; align-items: flex-start; padding: 1rem; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; margin-bottom: 1.5rem; color: #1e3a8a; font-size: 0.85rem; }
.info-banner svg { flex-shrink: 0; color: #3b82f6; margin-top: 0.1rem; }
.info-banner p { margin: 0.25rem 0 0 0; color: #3b82f6; }

.transfer-form { display: flex; flex-direction: column; gap: 1.25rem; }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
@media (max-width: 640px) { .form-grid { grid-template-columns: 1fr; } }

.form-group { display: flex; flex-direction: column; gap: 0.4rem; }
.form-label { font-size: 0.85rem; font-weight: 600; color: #334155; }
.form-input, .form-textarea { width: 100%; padding: 0.6rem 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; color: #1e293b; background: #fff; outline: none; transition: all 0.15s; }
.form-input:focus, .form-textarea:focus { border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59,130,246,0.1); }
.form-input:disabled { background: #f8fafc; color: #94a3b8; cursor: not-allowed; }
.form-error { font-size: 0.75rem; color: #ef4444; }
.form-warning { font-size: 0.75rem; color: #d97706; margin-top: 0.2rem; }

.stock-info { display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 1rem; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; }
.stock-info--empty { background: #fef2f2; border-color: #fca5a5; }
.stock-label { font-size: 0.85rem; color: #475569; font-weight: 500; }
.stock-value { font-size: 1.1rem; font-weight: 800; color: #16a34a; }
.stock-info--empty .stock-value { color: #dc2626; }

.form-actions { display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1rem; padding-top: 1.25rem; border-top: 1px solid #e2e8f0; }
</style>
