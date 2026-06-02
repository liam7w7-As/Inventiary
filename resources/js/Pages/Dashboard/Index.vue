<template>
    <AppLayout>
        <template #title>Dashboard</template>

        <div class="dashboard">
            <!-- Welcome -->
            <div class="dashboard__welcome">
                <div class="dashboard__welcome-text">
                    <h2 class="dashboard__greeting">Bienvenido, {{ user?.name }}</h2>
                    <p class="dashboard__meta">
                        <span class="dashboard__role-badge">{{ roleLabel }}</span>
                        <span v-if="user?.branch_name" class="dashboard__branch">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                            {{ user.branch_name }}
                        </span>
                    </p>
                </div>
                <div class="dashboard__date">
                    <span class="dashboard__date-day">{{ currentDate }}</span>
                    <span class="dashboard__date-time">{{ currentTime }}</span>
                </div>
            </div>

            <!-- Stats cards -->
            <div class="dashboard__grid">
                <div v-for="card in cards" :key="card.title" class="stat-card">
                        <div :class="['stat-card__icon', `stat-card__icon--${card.color}`]">
                            <span v-html="card.icon"></span>
                        </div>
                    <div class="stat-card__body">
                        <span class="stat-card__value">{{ card.value }}</span>
                        <span class="stat-card__title">{{ card.title }}</span>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { useAuth } from '@/Composables/useAuth.js'

const props = defineProps({ stats: Object })

const { user } = useAuth()

const roleLabel = computed(() => {
    const labels = { admin: 'Administrador', encargado: 'Encargado', vendedor: 'Vendedor' }
    return labels[user.value?.role] || user.value?.role
})

const currentDate = ref('')
const currentTime = ref('')
let timer = null

function updateClock() {
    const now = new Date()
    currentDate.value = now.toLocaleDateString('es-BO', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
    currentTime.value = now.toLocaleTimeString('es-BO', { hour: '2-digit', minute: '2-digit' })
}

onMounted(() => {
    updateClock()
    timer = setInterval(updateClock, 30000)
})

onUnmounted(() => {
    if (timer) clearInterval(timer)
})

const cards = computed(() => [
    {
        title: 'Ventas hoy',
        value: `Bs. ${parseFloat(props.stats?.sales_today || 0).toFixed(2)}`,
        color: 'blue',
        icon: '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>',
    },
    {
        title: 'Productos',
        value: props.stats?.products_count || 0,
        color: 'emerald',
        icon: '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>',
    },
    {
        title: 'Clientes',
        value: props.stats?.clients_count || 0,
        color: 'violet',
        icon: '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
    },
    {
        title: 'Preventas pendientes',
        value: props.stats?.presales_pending || 0,
        color: 'amber',
        icon: '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 18v-1"/><path d="M14 18v-3"/><path d="M10 13V8l4 5"/></svg>',
    },
])
</script>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

.dashboard {
    font-family: 'Inter', system-ui, sans-serif;
}

.dashboard__welcome {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    padding: 1.75rem 2rem;
    background: #fff;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    margin-bottom: 1.5rem;
}

.dashboard__greeting {
    font-size: 1.35rem;
    font-weight: 800;
    color: #0f172a;
    margin: 0 0 0.35rem;
    letter-spacing: -0.02em;
}

.dashboard__meta {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin: 0;
}

.dashboard__role-badge {
    display: inline-block;
    padding: 0.2rem 0.65rem;
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(99, 102, 241, 0.1));
    color: #3b82f6;
    border-radius: 20px;
    font-size: 0.72rem;
    font-weight: 600;
}

.dashboard__branch {
    display: flex;
    align-items: center;
    gap: 0.3rem;
    color: #64748b;
    font-size: 0.8rem;
    font-weight: 500;
}

.dashboard__date {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
}

.dashboard__date-day {
    font-size: 0.8rem;
    color: #64748b;
    font-weight: 500;
    text-transform: capitalize;
}

.dashboard__date-time {
    font-size: 1.6rem;
    font-weight: 800;
    color: #1e293b;
    letter-spacing: -0.03em;
    line-height: 1.1;
}

/* Stats grid */
.dashboard__grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
}

.stat-card {
    background: #fff;
    border-radius: 14px;
    padding: 1.25rem;
    border: 1px solid #e2e8f0;
    transition: all 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
    border-color: #cbd5e1;
}

.stat-card__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
}

.stat-card__icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.stat-card__icon--blue {
    background: rgba(59, 130, 246, 0.1);
    color: #3b82f6;
}
.stat-card__icon--emerald {
    background: rgba(16, 185, 129, 0.1);
    color: #10b981;
}
.stat-card__icon--violet {
    background: rgba(139, 92, 246, 0.1);
    color: #8b5cf6;
}
.stat-card__icon--amber {
    background: rgba(245, 158, 11, 0.1);
    color: #f59e0b;
}

.stat-card__badge {
    font-size: 0.62rem;
    font-weight: 600;
    color: #94a3b8;
    background: #f1f5f9;
    padding: 0.15rem 0.5rem;
    border-radius: 20px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.stat-card__body {
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
}

.stat-card__value {
    font-size: 1.75rem;
    font-weight: 800;
    color: #1e293b;
    letter-spacing: -0.03em;
}

.stat-card__title {
    font-size: 0.78rem;
    color: #64748b;
    font-weight: 500;
}

/* Responsive */
@media (max-width: 1024px) {
    .dashboard__grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 640px) {
    .dashboard__welcome {
        flex-direction: column;
        gap: 1rem;
        padding: 1.25rem;
    }

    .dashboard__date {
        align-items: flex-start;
    }

    .dashboard__grid {
        grid-template-columns: 1fr;
    }
}
</style>
