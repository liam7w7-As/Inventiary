<template>
    <div class="app-table-wrap">
        <!-- Loading skeleton -->
        <div v-if="loading" class="table-skeleton">
            <div v-for="i in 5" :key="i" class="table-skeleton__row">
                <div v-for="j in columns.length" :key="j" class="table-skeleton__cell"></div>
            </div>
        </div>

        <!-- Table -->
        <table v-else-if="rows.length" class="app-table">
            <thead>
                <tr>
                    <th v-for="col in columns" :key="col.key" :style="col.width ? { width: col.width } : {}">
                        {{ col.label }}
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(row, idx) in rows" :key="row.id || idx">
                    <td v-for="col in columns" :key="col.key">
                        <slot :name="`cell(${col.key})`" :row="row" :value="row[col.key]">
                            {{ row[col.key] ?? '—' }}
                        </slot>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Empty -->
        <div v-else class="table-empty">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M9 15h6"/></svg>
            <p>Sin registros</p>
        </div>
    </div>
</template>

<script setup>
defineProps({
    columns: { type: Array, default: () => [] },
    rows: { type: Array, default: () => [] },
    loading: Boolean,
})
</script>

<style scoped>
.app-table-wrap {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
}
.app-table {
    width: 100%;
    border-collapse: collapse;
}
.app-table thead th {
    padding: 0.7rem 1rem;
    text-align: left;
    font-size: 0.72rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}
.app-table tbody td {
    padding: 0.65rem 1rem;
    font-size: 0.83rem;
    color: #334155;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}
.app-table tbody tr:last-child td { border-bottom: none; }
.app-table tbody tr:hover { background: #f8fafc; }

.table-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem 1rem;
    color: #94a3b8;
    gap: 0.5rem;
}
.table-empty p { font-size: 0.85rem; margin: 0; font-weight: 500; }

/* Skeleton */
.table-skeleton { padding: 0.75rem 1rem; }
.table-skeleton__row {
    display: flex; gap: 1rem; padding: 0.65rem 0;
    border-bottom: 1px solid #f1f5f9;
}
.table-skeleton__row:last-child { border-bottom: none; }
.table-skeleton__cell {
    height: 14px; border-radius: 4px; background: #e2e8f0;
    flex: 1;
    animation: skeleton-pulse 1.5s ease-in-out infinite;
}
@keyframes skeleton-pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.4; }
}
</style>
