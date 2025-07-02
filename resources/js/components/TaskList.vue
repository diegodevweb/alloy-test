<template>
    <div class="task-list">
        <div class="mb-6 bg-white rounded-lg shadow-sm border p-4">
            <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
                <div class="flex-1 max-w-md">
                    <div class="relative">
                        <input v-model="searchQuery" type="text" placeholder="Buscar tarefas..."
                            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            @input="handleSearch">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="flex gap-2">
                    <select v-model="statusFilter"
                        class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        @change="handleFilterChange">
                        <option value="">Todas</option>
                        <option value="pendente">Pendentes</option>
                        <option value="finalizada">Finalizadas</option>
                        <option value="vencida">Vencidas</option>
                    </select>

                    <button @click="refreshTasks" :disabled="loading"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                        <svg class="h-4 w-4" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                            </path>
                        </svg>
                        <span class="hidden sm:inline">Atualizar</span>
                    </button>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="text-center p-3 bg-gray-50 rounded-lg">
                    <div class="text-2xl font-bold text-gray-900">{{ statistics.total }}</div>
                    <div class="text-sm text-gray-600">Total</div>
                </div>
                <div class="text-center p-3 bg-blue-50 rounded-lg">
                    <div class="text-2xl font-bold text-blue-600">{{ statistics.pending }}</div>
                    <div class="text-sm text-gray-600">Pendentes</div>
                </div>
                <div class="text-center p-3 bg-green-50 rounded-lg">
                    <div class="text-2xl font-bold text-green-600">{{ statistics.completed }}</div>
                    <div class="text-sm text-gray-600">Finalizadas</div>
                </div>
                <div class="text-center p-3 bg-red-50 rounded-lg">
                    <div class="text-2xl font-bold text-red-600">{{ statistics.overdue }}</div>
                    <div class="text-sm text-gray-600">Vencidas</div>
                </div>
            </div>
        </div>

        <div v-if="error" class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
            {{ error }}
            <button @click="clearError" class="ml-2 text-red-500 hover:text-red-700">
                ✕
            </button>
        </div>

        <div v-if="loading && tasks.length === 0" class="text-center py-8">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <p class="mt-2 text-gray-600">Carregando tarefas...</p>
        </div>

        <div v-else-if="!loading && filteredTasks.length === 0" class="text-center py-12">
            <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                </path>
            </svg>
            <h3 class="mt-4 text-lg font-medium text-gray-900">
                {{ searchQuery || statusFilter ? 'Nenhuma tarefa encontrada' : 'Nenhuma tarefa criada' }}
            </h3>
            <p class="mt-2 text-gray-600">
                {{ searchQuery || statusFilter ? 'Tente alterar os filtros de busca.' : 'Comece criando sua primeira tarefa!' }}
            </p>
        </div>

        <div v-else class="space-y-4">
            <TaskItem v-for="task in filteredTasks" :key="task.id" :task="task" @edit="$emit('edit', task)"
                @toggle="handleToggle" @delete="handleDelete" />
        </div>

        <div v-if="hasMore" class="text-center mt-6">
            <button @click="loadMore" :disabled="loading"
                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                {{ loading ? 'Carregando...' : 'Carregar mais' }}
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch, toRefs } from 'vue';
import { storeToRefs } from 'pinia';
import { useTaskStore } from '../stores/taskStore.js';
import TaskItem from './TaskItem.vue';

const props = defineProps({
    autoRefresh: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['edit', 'task-updated']);

const taskStore = useTaskStore();

const searchQuery = ref('');
const statusFilter = ref('');
const hasMore = ref(false);

const { tasks, filteredTasks, loading, error, statistics } = toRefs(taskStore);

const handleSearch = () => {
    taskStore.setFilters({ search: searchQuery.value });
};

const handleFilterChange = () => {
    taskStore.setFilters({ status: statusFilter.value });
};

const refreshTasks = async () => {
    await taskStore.refresh();
};

const handleToggle = async (taskId) => {
    try {
        await taskStore.toggleTask(taskId);
        emit('task-updated');
    } catch (error) {
    }
};

const handleDelete = async (taskId) => {
    if (confirm('Tem certeza que deseja excluir esta tarefa?')) {
        try {
            await taskStore.deleteTask(taskId);
            emit('task-updated');
        } catch (error) {
        }
    }
};

const clearError = () => {
    taskStore.clearError();
};

const loadMore = () => {
    console.log('Load more clicked');
};

watch([searchQuery, statusFilter], () => {
    taskStore.setFilters({
        search: searchQuery.value,
        status: statusFilter.value
    });
});

let refreshInterval;
if (props.autoRefresh) {
    refreshInterval = setInterval(() => {
        if (!loading.value) {
            refreshTasks();
        }
    }, 30000);
}

onMounted(async () => {
    await taskStore.fetchTasks();
});

onUnmounted(() => {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
});
</script>

<style scoped>
.task-list {
    @apply w-full;
}

/* Animações personalizadas */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.slide-fade-enter-active {
    transition: all 0.3s ease-out;
}

.slide-fade-leave-active {
    transition: all 0.3s cubic-bezier(1.0, 0.5, 0.8, 1.0);
}

.slide-fade-enter-from,
.slide-fade-leave-to {
    transform: translateX(20px);
    opacity: 0;
}
</style>
