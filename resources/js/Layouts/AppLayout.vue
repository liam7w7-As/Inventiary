<template>
    <div class="app-layout">
        <!-- Mobile overlay -->
        <transition name="fade">
            <div
                v-if="sidebarOpen"
                class="sidebar-overlay"
                @click="sidebarOpen = false"
            ></div>
        </transition>

        <!-- Sidebar -->
        <aside :class="['sidebar', { 'sidebar--open': sidebarOpen }]">
            <!-- Header -->
            <div class="sidebar__header">
                <div class="sidebar__brand">
                    <div class="sidebar__logo">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="sidebar__logo-icon">
                            <path d="m7.5 4.27 9 5.15" /><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z" /><path d="m3.3 7 8.7 5 8.7-5" /><path d="M12 22V12" />
                        </svg>
                    </div>
                    <div class="sidebar__brand-text">
                        <span class="sidebar__brand-name">{{ system?.alias || system?.name || 'z8venta' }}</span>
                        <span class="sidebar__brand-tagline">Sistema de Ventas</span>
                    </div>
                </div>

                <!-- Close button mobile -->
                <button class="sidebar__close" @click="sidebarOpen = false">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>

            <!-- User info -->
            <div class="sidebar__user">
                <div class="sidebar__avatar">
                    <span>{{ user?.name?.charAt(0)?.toUpperCase() }}</span>
                </div>
                <div class="sidebar__user-info">
                    <span class="sidebar__user-name">{{ user?.name }}</span>
                    <span class="sidebar__user-role">{{ roleLabel }}</span>
                    <span v-if="user?.branch_name" class="sidebar__user-branch">{{ user.branch_name }}</span>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="sidebar__nav">
                <div class="sidebar__nav-section">
                    <span class="sidebar__nav-label">Principal</span>
                    <a v-for="item in mainMenu" :key="item.href" :href="item.href"
                       :class="['sidebar__link', { 'sidebar__link--active': isActive(item.href) }]"
                       @click.prevent="navigate(item.href)">
                        <span class="sidebar__link-icon" v-html="item.icon"></span>
                        <span>{{ item.label }}</span>
                        <span v-if="item.badge" class="sidebar__badge" :class="item.badgeClass">{{ item.badge }}</span>
                    </a>
                </div>

                <div v-if="adminMenu.length" class="sidebar__nav-section">
                    <span class="sidebar__nav-label">Administración</span>
                    <a v-for="item in adminMenu" :key="item.href" :href="item.href"
                       :class="['sidebar__link', { 'sidebar__link--active': isActive(item.href) }]"
                       @click.prevent="navigate(item.href)">
                        <span class="sidebar__link-icon" v-html="item.icon"></span>
                        <span>{{ item.label }}</span>
                    </a>
                </div>
            </nav>

            <!-- Footer -->
            <div class="sidebar__footer">
                <button class="sidebar__logout" @click="logout">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
                    <span>Cerrar Sesión</span>
                </button>
            </div>
        </aside>

        <!-- Main content -->
        <div class="main">
            <!-- Top header -->
            <header class="main__header">
                <div class="main__header-left">
                    <button class="main__menu-btn" @click="sidebarOpen = true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
                    </button>
                    <h1 class="main__title">
                        <slot name="title">Dashboard</slot>
                    </h1>
                </div>
                <div class="main__header-right">
                    <!-- Notifications bell (visual only) -->
                    <button class="main__notification">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                        <span class="main__notification-dot"></span>
                    </button>
                    <!-- User avatar -->
                    <div class="main__user-pill">
                        <div class="main__user-avatar">
                            {{ user?.name?.charAt(0)?.toUpperCase() }}
                        </div>
                        <span class="main__user-name">{{ user?.name }}</span>
                    </div>
                </div>
            </header>

            <!-- Page content -->
            <main class="main__content">
                <slot />
            </main>
        </div>

        <FlashMessage />
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { useAuth } from '@/Composables/useAuth.js'
import FlashMessage from '@/Components/FlashMessage.vue'

const { user, isAdmin, isEncargado } = useAuth()
const page = usePage()
const system = computed(() => page.props.system)

const sidebarOpen = ref(false)

const roleLabel = computed(() => {
    const labels = { admin: 'Administrador', encargado: 'Encargado', vendedor: 'Vendedor' }
    return labels[user.value?.role] || user.value?.role
})

const icons = {
    dashboard: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>',
    branches: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 11 18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/></svg>',
    products: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>',
    inventory: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>',
    clients: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
    presales: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 18v-1"/><path d="M14 18v-3"/><path d="M10 13V8l4 5"/></svg>',
    sales: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>',
    credits: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>',
    transfers: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m7 15 5 5 5-5"/><path d="m7 9 5-5 5 5"/></svg>',
    reports: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>',
    users: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 21a8 8 0 0 0-16 0"/><circle cx="10" cy="8" r="5"/><path d="M22 20c0-3.37-2-6.5-4-8a5 5 0 0 0-.45-8.3"/></svg>',
    settings: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>',
    categories: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 10h12"/><path d="M4 14h9"/><path d="M19 6H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2Z"/><path d="M15 2v4"/></svg>',
    suppliers: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
}

const pendingPreSales = computed(() => page.props.pendingPreSales || 0)
const pendingTransfers = computed(() => page.props.pendingTransfers || 0)
const overdueCredits = computed(() => page.props.overdueCredits || 0)

const mainMenu = computed(() => {
    const items = [
        { label: 'Dashboard', href: '/dashboard', icon: icons.dashboard, roles: ['admin', 'encargado'] },
        { label: 'Sucursales', href: '/branches', icon: icons.branches, roles: ['admin'] },
        { label: 'Productos', href: '/products', icon: icons.products, roles: ['admin', 'encargado'] },
        { label: 'Categorías', href: '/categories', icon: icons.categories, roles: ['admin'] },
        { label: 'Proveedores', href: '/suppliers', icon: icons.suppliers, roles: ['admin'] },
        { label: 'Inventario', href: '/inventory', icon: icons.inventory, roles: ['admin', 'encargado'] },
        { label: 'Clientes', href: '/clients', icon: icons.clients, roles: ['admin', 'encargado'] },
        { label: 'Caja', href: '/cash-registers', icon: icons.credits, roles: ['admin', 'encargado'] },
        { label: 'Preventa', href: '/pre-sales', icon: icons.presales, roles: ['admin', 'encargado'], badge: pendingPreSales.value > 0 ? pendingPreSales.value : null },
        { label: 'Ventas', href: '/sales', icon: icons.sales, roles: ['admin', 'encargado'] },
        { label: 'Crédito', href: '/credits', icon: icons.credits, roles: ['admin', 'encargado'], badge: overdueCredits.value > 0 ? overdueCredits.value : null, badgeClass: 'sidebar__badge--danger' },
        { label: 'Transferencias', href: '/transfers', icon: icons.transfers, roles: ['admin', 'encargado'], badge: pendingTransfers.value > 0 ? pendingTransfers.value : null, badgeClass: 'sidebar__badge--warning' },
        { label: 'Reportes', href: '/reports', icon: icons.reports, roles: ['admin', 'encargado'] },
    ]
    return items.filter(i => i.roles.includes(user.value?.role))
})

const adminMenu = computed(() => {
    if (!isAdmin.value) return []
    return [
        { label: 'Usuarios', href: '/users', icon: icons.users },
        { label: 'Configuración', href: '/settings', icon: icons.settings },
    ]
})

function isActive(href) {
    return page.url === href || page.url.startsWith(href + '/')
}

function navigate(href) {
    router.visit(href)
    sidebarOpen.value = false
}

function logout() {
    router.post('/logout')
}
</script>

<style>
/* ===== Layout ===== */
.app-layout {
    display: flex;
    min-height: 100vh;
    background: #f1f5f9;
}

/* ===== Sidebar ===== */
.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    width: 260px;
    background: #0f172a;
    display: flex;
    flex-direction: column;
    z-index: 50;
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow-y: auto;
    border-right: 1px solid rgba(255, 255, 255, 0.05);
}

.sidebar__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem 1rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.sidebar__brand {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.sidebar__logo {
    width: 38px;
    height: 38px;
    background: linear-gradient(135deg, #3b82f6, #6366f1);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.sidebar__logo-icon {
    width: 20px;
    height: 20px;
    color: #fff;
}

.sidebar__brand-text {
    display: flex;
    flex-direction: column;
}

.sidebar__brand-name {
    font-size: 1rem;
    font-weight: 700;
    color: #f8fafc;
    letter-spacing: -0.01em;
}

.sidebar__brand-tagline {
    font-size: 0.68rem;
    color: #64748b;
    font-weight: 500;
    letter-spacing: 0.03em;
    text-transform: uppercase;
}

.sidebar__close {
    display: none;
    background: none;
    border: none;
    color: #94a3b8;
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 6px;
    transition: all 0.15s;
}

.sidebar__close:hover {
    background: rgba(255, 255, 255, 0.08);
    color: #f8fafc;
}

/* User card */
.sidebar__user {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    margin: 0.5rem 0.75rem;
    background: rgba(255, 255, 255, 0.04);
    border-radius: 10px;
    border: 1px solid rgba(255, 255, 255, 0.04);
}

.sidebar__avatar {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 700;
    font-size: 0.85rem;
    flex-shrink: 0;
}

.sidebar__user-info {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.sidebar__user-name {
    font-size: 0.82rem;
    font-weight: 600;
    color: #e2e8f0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.sidebar__user-role {
    font-size: 0.7rem;
    color: #64748b;
    font-weight: 500;
}

.sidebar__user-branch {
    font-size: 0.68rem;
    color: #3b82f6;
    font-weight: 500;
}

/* Navigation */
.sidebar__nav {
    flex: 1;
    padding: 0.5rem 0.75rem;
    overflow-y: auto;
}

.sidebar__nav-section {
    margin-bottom: 1rem;
}

.sidebar__nav-label {
    display: block;
    font-size: 0.65rem;
    font-weight: 600;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    padding: 0.5rem 0.75rem 0.35rem;
}

.sidebar__link {
    display: flex;
    align-items: center;
    gap: 0.7rem;
    padding: 0.55rem 0.75rem;
    border-radius: 8px;
    color: #94a3b8;
    text-decoration: none;
    font-size: 0.82rem;
    font-weight: 500;
    transition: all 0.15s ease;
    margin-bottom: 1px;
}

.sidebar__link:hover {
    background: rgba(255, 255, 255, 0.06);
    color: #e2e8f0;
}

.sidebar__link--active {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.15), rgba(99, 102, 241, 0.1));
    color: #60a5fa;
    font-weight: 600;
}

.sidebar__link--active .sidebar__link-icon {
    color: #60a5fa;
}

.sidebar__link-icon {
    display: flex;
    align-items: center;
    width: 18px;
    height: 18px;
    flex-shrink: 0;
}

.sidebar__badge {
    margin-left: auto;
    background: #ef4444;
    color: #fff;
    font-size: 0.65rem;
    font-weight: 700;
    padding: 0.1rem 0.4rem;
    border-radius: 10px;
    min-width: 18px;
    text-align: center;
    line-height: 1.3;
}

.sidebar__badge--danger {
    background: #ef4444;
    color: #fff;
}

.sidebar__badge--warning {
    background: #f59e0b;
    color: #fff;
}

/* Footer */
.sidebar__footer {
    padding: 0.75rem;
    border-top: 1px solid rgba(255, 255, 255, 0.06);
}

.sidebar__logout {
    display: flex;
    align-items: center;
    gap: 0.7rem;
    width: 100%;
    padding: 0.6rem 0.75rem;
    border-radius: 8px;
    border: none;
    background: none;
    color: #94a3b8;
    cursor: pointer;
    font-size: 0.82rem;
    font-weight: 500;
    transition: all 0.15s ease;
}

.sidebar__logout:hover {
    background: rgba(239, 68, 68, 0.1);
    color: #f87171;
}

/* ===== Overlay ===== */
.sidebar-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    z-index: 40;
    display: none;
}

/* ===== Main Content ===== */
.main {
    flex: 1;
    margin-left: 260px;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

.main__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 1.5rem;
    height: 60px;
    background: #fff;
    border-bottom: 1px solid #e2e8f0;
    position: sticky;
    top: 0;
    z-index: 30;
}

.main__header-left {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.main__menu-btn {
    display: none;
    background: none;
    border: none;
    color: #475569;
    cursor: pointer;
    padding: 0.35rem;
    border-radius: 6px;
    transition: all 0.15s;
}

.main__menu-btn:hover {
    background: #f1f5f9;
    color: #1e293b;
}

.main__title {
    font-size: 1.05rem;
    font-weight: 700;
    color: #1e293b;
    letter-spacing: -0.01em;
}

.main__header-right {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.main__notification {
    position: relative;
    background: none;
    border: none;
    color: #64748b;
    cursor: pointer;
    padding: 0.4rem;
    border-radius: 8px;
    transition: all 0.15s;
}

.main__notification:hover {
    background: #f1f5f9;
    color: #475569;
}

.main__notification-dot {
    position: absolute;
    top: 6px;
    right: 6px;
    width: 7px;
    height: 7px;
    background: #3b82f6;
    border-radius: 50%;
    border: 2px solid #fff;
}

.main__user-pill {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.3rem 0.65rem 0.3rem 0.3rem;
    border-radius: 9999px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
}

.main__user-avatar {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 700;
    font-size: 0.75rem;
}

.main__user-name {
    font-size: 0.8rem;
    font-weight: 600;
    color: #334155;
}

.main__content {
    flex: 1;
    padding: 1.5rem;
}

/* ===== Transitions ===== */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.25s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

/* ===== Responsive ===== */
@media (max-width: 1024px) {
    .sidebar {
        transform: translateX(-100%);
    }

    .sidebar--open {
        transform: translateX(0);
    }

    .sidebar__close {
        display: block;
    }

    .sidebar-overlay {
        display: block;
    }

    .main {
        margin-left: 0;
    }

    .main__menu-btn {
        display: block;
    }

    .main__user-name {
        display: none;
    }
}
</style>
