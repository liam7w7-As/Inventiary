<template>
    <div class="login-page">
        <!-- Background decoration -->
        <div class="login-bg">
            <div class="login-bg__shape login-bg__shape--1"></div>
            <div class="login-bg__shape login-bg__shape--2"></div>
            <div class="login-bg__shape login-bg__shape--3"></div>
        </div>

        <div class="login-card">
            <!-- Brand -->
            <div class="login-card__brand">
                <div class="login-card__logo">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="login-card__logo-icon">
                        <path d="m7.5 4.27 9 5.15" /><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z" /><path d="m3.3 7 8.7 5 8.7-5" /><path d="M12 22V12" />
                    </svg>
                </div>
                <h1 class="login-card__title">{{ system?.alias || system?.name || 'z8venta' }}</h1>
                <p class="login-card__subtitle">Sistema de Ventas e Inventario</p>
            </div>

            <!-- Flash error -->
            <div v-if="flashError" class="login-card__alert">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
                <span>{{ flashError }}</span>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="login-card__form">
                <!-- Username -->
                <div class="form-group">
                    <label for="username" class="form-label">Usuario</label>
                    <div class="form-input-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="form-input-icon"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        <input
                            id="username"
                            v-model="form.username"
                            type="text"
                            placeholder="Ingresa tu usuario"
                            class="form-input"
                            :class="{ 'form-input--error': form.errors.username }"
                            autocomplete="username"
                            autofocus
                        />
                    </div>
                    <p v-if="form.errors.username" class="form-error">{{ form.errors.username }}</p>
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label for="password" class="form-label">Contraseña</label>
                    <div class="form-input-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="form-input-icon"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <input
                            id="password"
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            placeholder="Ingresa tu contraseña"
                            class="form-input"
                            :class="{ 'form-input--error': form.errors.password }"
                            autocomplete="current-password"
                        />
                        <button type="button" class="form-input-toggle" @click="showPassword = !showPassword" tabindex="-1">
                            <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
                        </button>
                    </div>
                    <p v-if="form.errors.password" class="form-error">{{ form.errors.password }}</p>
                </div>

                <!-- Submit -->
                <button
                    type="submit"
                    class="login-btn"
                    :disabled="form.processing"
                >
                    <svg v-if="form.processing" class="login-btn__spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="login-btn__spinner-track" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="login-btn__spinner-head" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>{{ form.processing ? 'Ingresando...' : 'Iniciar Sesión' }}</span>
                </button>
            </form>

            <!-- Footer -->
            <p class="login-card__footer">&copy; {{ new Date().getFullYear() }} {{ system?.alias || 'z8venta' }} — Todos los derechos reservados</p>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'

const page = usePage()
const system = computed(() => page.props.system)
const flashError = computed(() => page.props.flash?.error)
const showPassword = ref(false)

const form = useForm({
    username: '',
    password: '',
})

function submit() {
    form.post('/login', {
        preserveScroll: true,
        onFinish: () => {
            form.password = ''
        },
    })
}
</script>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

.login-page {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #0f172a;
    padding: 1rem;
    position: relative;
    overflow: hidden;
    font-family: 'Inter', system-ui, sans-serif;
}

/* Background shapes */
.login-bg {
    position: absolute;
    inset: 0;
    overflow: hidden;
    pointer-events: none;
}

.login-bg__shape {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    opacity: 0.3;
}

.login-bg__shape--1 {
    width: 500px;
    height: 500px;
    background: #3b82f6;
    top: -15%;
    right: -10%;
    animation: float-1 15s ease-in-out infinite;
}

.login-bg__shape--2 {
    width: 400px;
    height: 400px;
    background: #8b5cf6;
    bottom: -10%;
    left: -10%;
    animation: float-2 18s ease-in-out infinite;
}

.login-bg__shape--3 {
    width: 300px;
    height: 300px;
    background: #06b6d4;
    top: 40%;
    left: 30%;
    animation: float-3 12s ease-in-out infinite;
}

@keyframes float-1 {
    0%, 100% { transform: translate(0, 0) scale(1); }
    50% { transform: translate(-40px, 30px) scale(1.1); }
}
@keyframes float-2 {
    0%, 100% { transform: translate(0, 0) scale(1); }
    50% { transform: translate(50px, -40px) scale(1.15); }
}
@keyframes float-3 {
    0%, 100% { transform: translate(0, 0) scale(1); }
    50% { transform: translate(-30px, -50px) scale(0.9); }
}

/* Card */
.login-card {
    width: 100%;
    max-width: 400px;
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 20px;
    padding: 2.5rem 2rem;
    position: relative;
    z-index: 10;
    animation: card-enter 0.5s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes card-enter {
    from {
        opacity: 0;
        transform: translateY(20px) scale(0.97);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.login-card__brand {
    text-align: center;
    margin-bottom: 2rem;
}

.login-card__logo {
    width: 56px;
    height: 56px;
    background: linear-gradient(135deg, #3b82f6, #6366f1);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    box-shadow: 0 8px 24px rgba(59, 130, 246, 0.25);
}

.login-card__logo-icon {
    width: 28px;
    height: 28px;
    color: #fff;
}

.login-card__title {
    font-size: 1.5rem;
    font-weight: 800;
    color: #f8fafc;
    letter-spacing: -0.02em;
    margin: 0 0 0.25rem;
}

.login-card__subtitle {
    font-size: 0.82rem;
    color: #64748b;
    font-weight: 500;
    margin: 0;
}

/* Alert */
.login-card__alert {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.7rem 0.9rem;
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.2);
    border-radius: 10px;
    color: #fca5a5;
    font-size: 0.8rem;
    font-weight: 500;
    margin-bottom: 1.25rem;
    animation: alert-enter 0.3s ease;
}

@keyframes alert-enter {
    from { opacity: 0; transform: translateY(-5px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Form */
.login-card__form {
    display: flex;
    flex-direction: column;
    gap: 1.1rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.form-label {
    font-size: 0.78rem;
    font-weight: 600;
    color: #cbd5e1;
    padding-left: 2px;
}

.form-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.form-input-icon {
    position: absolute;
    left: 0.85rem;
    color: #475569;
    pointer-events: none;
    transition: color 0.15s;
}

.form-input {
    width: 100%;
    padding: 0.7rem 0.85rem 0.7rem 2.75rem;
    background: rgba(255, 255, 255, 0.06);
    border: 1.5px solid rgba(255, 255, 255, 0.08);
    border-radius: 10px;
    color: #f1f5f9;
    font-size: 0.88rem;
    font-weight: 500;
    outline: none;
    transition: all 0.2s;
    font-family: inherit;
}

.form-input::placeholder {
    color: #475569;
}

.form-input:focus {
    border-color: #3b82f6;
    background: rgba(255, 255, 255, 0.08);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
}

.form-input:focus ~ .form-input-icon,
.form-input:focus + .form-input-icon {
    color: #60a5fa;
}

.form-input-wrapper:focus-within .form-input-icon {
    color: #60a5fa;
}

.form-input--error {
    border-color: #ef4444;
}

.form-input-toggle {
    position: absolute;
    right: 0.65rem;
    background: none;
    border: none;
    color: #475569;
    cursor: pointer;
    padding: 0.2rem;
    border-radius: 4px;
    transition: color 0.15s;
}

.form-input-toggle:hover {
    color: #94a3b8;
}

.form-error {
    font-size: 0.75rem;
    color: #f87171;
    margin: 0;
    padding-left: 2px;
    font-weight: 500;
    animation: alert-enter 0.2s ease;
}

/* Button */
.login-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    width: 100%;
    padding: 0.75rem;
    background: linear-gradient(135deg, #3b82f6, #6366f1);
    border: none;
    border-radius: 10px;
    color: #fff;
    font-size: 0.9rem;
    font-weight: 700;
    cursor: pointer;
    margin-top: 0.5rem;
    transition: all 0.2s;
    font-family: inherit;
    letter-spacing: 0.01em;
}

.login-btn:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 8px 24px rgba(59, 130, 246, 0.3);
}

.login-btn:active:not(:disabled) {
    transform: translateY(0);
}

.login-btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.login-btn__spinner {
    width: 18px;
    height: 18px;
    animation: spin 1s linear infinite;
}

.login-btn__spinner-track {
    opacity: 0.25;
}

.login-btn__spinner-head {
    opacity: 0.75;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Footer */
.login-card__footer {
    text-align: center;
    font-size: 0.7rem;
    color: #475569;
    margin: 1.5rem 0 0;
}
</style>
