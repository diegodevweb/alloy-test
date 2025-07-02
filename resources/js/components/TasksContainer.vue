<template>
    <div class="tasks-container min-h-screen bg-gray-50">
        <header class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-6">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">To-Do List</h1>
                        <p class="mt-1 text-gray-600">Gerencie suas tarefas de forma eficiente</p>
                    </div>
                    <button @click="openCreateModal"
                        class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors flex items-center space-x-2 shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        <span>Nova Tarefa</span>
                    </button>
                </div>
            </div>
        </header>

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div v-if="notification.show" class="mb-6">
                <div class="rounded-lg p-4 flex items-center justify-between" :class="{
                    'bg-green-50 border border-green-200 text-green-700': notification.type === 'success',
                    'bg-red-50 border border-red-200 text-red-700': notification.type === 'error',
                    'bg-blue-50 border border-blue-200 text-blue-700': notification.type === 'info',
                    'bg-yellow-50 border border-yellow-200 text-yellow-700': notification.type === 'warning'
                }">
                    <div class="flex items-center">
                        <svg v-if="notification.type === 'success'" class="w-5 h-5 mr-2" fill="currentColor"
                            viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                clip-rule="evenodd"></path>
                        </svg>
                        <svg v-else-if="notification.type === 'error'" class="w-5 h-5 mr-2" fill="currentColor"
                            viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                clip-rule="evenodd"></path>
                        </svg>
                        <span>{{ notification.message }}</span>
                    </div>
                    <button @click="hideNotification" class="text-current hover:text-opacity-75">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <TaskList :auto-refresh="true" @edit="openEditModal" @task-updated="handleTaskUpdated" />
        </main>

        <TaskModal :show="modal.show" :task="modal.task" :loading="modal.loading" @close="closeModal"
            @submit="handleTaskSubmit" />

        <footer class="bg-white border-t mt-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <div class="flex flex-col sm:flex-row justify-between items-center text-sm text-gray-600">
                    <p>&copy; 2025 Alloy To-Do List. Teste Técnico.</p>
                    <div class="flex items-center space-x-4 mt-2 sm:mt-0">
                        <span>Desenvolvido com Vue.js 3 + Laravel 12</span>
                        <div class="flex items-center space-x-1">
                            <div class="w-2 h-2 rounded-full" :class="{
                                'bg-green-500': connectionStatus === 'connected',
                                'bg-yellow-500': connectionStatus === 'connecting',
                                'bg-red-500': connectionStatus === 'disconnected'
                            }"></div>
                            <span class="text-xs">{{ getConnectionText() }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, onUnmounted } from 'vue';
import { useTaskStore } from '@/stores/taskStore.js';
import TaskList from '@/components/TaskList.vue';
import TaskModal from '@/components/TaskModal.vue';

const taskStore = useTaskStore();

const modal = reactive({
    show: false,
    task: null,
    loading: false
});

const notification = reactive({
    show: false,
    type: 'info',
    message: '',
    timeout: null
});

const connectionStatus = ref('connected');

const openCreateModal = () => {
    modal.task = null;
    modal.show = true;
    modal.loading = false;
};

const openEditModal = (task) => {
    modal.task = task;
    modal.show = true;
    modal.loading = false;
};

const closeModal = () => {
    modal.show = false;
    modal.task = null;
    modal.loading = false;
};

const handleTaskSubmit = async (taskData) => {
    modal.loading = true;

    try {
        let response;

        if (modal.task?.id) {
            response = await taskStore.updateTask(modal.task.id, taskData);
            showNotification('success', 'Tarefa atualizada com sucesso!');
        } else {
            response = await taskStore.createTask(taskData);
            showNotification('success', 'Tarefa criada com sucesso!');
        }

        closeModal();
        await taskStore.fetchTasks();
    } catch (error) {
        showNotification('error', error.message || 'Erro ao salvar tarefa');
    } finally {
        modal.loading = false;
    }
};

const handleTaskUpdated = () => {
};

const showNotification = (type, message, duration = 5000) => {
    if (notification.timeout) {
        clearTimeout(notification.timeout);
    }

    notification.type = type;
    notification.message = message;
    notification.show = true;

    notification.timeout = setTimeout(() => {
        hideNotification();
    }, duration);
};

const hideNotification = () => {
    notification.show = false;
    if (notification.timeout) {
        clearTimeout(notification.timeout);
        notification.timeout = null;
    }
};

const getConnectionText = () => {
    switch (connectionStatus.value) {
        case 'connected':
            return 'Online';
        case 'connecting':
            return 'Conectando...';
        case 'disconnected':
            return 'Offline';
        default:
            return 'Desconhecido';
    }
};

const checkConnection = async () => {
    try {
        connectionStatus.value = 'connecting';
        const response = await fetch('/api/tasks?limit=1');
        connectionStatus.value = response.ok ? 'connected' : 'disconnected';
    } catch (error) {
        connectionStatus.value = 'disconnected';
    }
};

const handleKeydown = (event) => {
    if ((event.ctrlKey || event.metaKey) && event.key === 'n' && !modal.show) {
        event.preventDefault();
        openCreateModal();
    }

    if (event.key === 'Escape') {
        if (notification.show) {
            hideNotification();
        } else if (modal.show) {
            closeModal();
        }
    }

    if ((event.ctrlKey || event.metaKey) && event.key === 'r' && !modal.show) {
        event.preventDefault();
        taskStore.refresh();
        showNotification('info', 'Lista atualizada');
    }
};

const handleGlobalError = (error) => {
    console.error('Global error:', error);
    showNotification('error', 'Erro inesperado. Tente atualizar a página.');
};

onMounted(() => {
    document.addEventListener('keydown', handleKeydown);
    window.addEventListener('error', handleGlobalError);
    window.addEventListener('online', () => {
        connectionStatus.value = 'connected';
        showNotification('success', 'Conexão restaurada', 3000);
    });
    window.addEventListener('offline', () => {
        connectionStatus.value = 'disconnected';
        showNotification('warning', 'Você está offline', 3000);
    });

    const connectionInterval = setInterval(checkConnection, 30000);

    checkConnection();

    onUnmounted(() => {
        document.removeEventListener('keydown', handleKeydown);
        window.removeEventListener('error', handleGlobalError);
        clearInterval(connectionInterval);

        if (notification.timeout) {
            clearTimeout(notification.timeout);
        }
    });
});
</script>

<style scoped>
.tasks-container {
    min-height: 100vh;
}

@keyframes slideIn {
    from {
        transform: translateY(-10px);
        opacity: 0;
    }

    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.notification-enter-active {
    animation: slideIn 0.3s ease-out;
}

.notification-leave-active {
    transition: all 0.3s ease-in;
}

.notification-leave-to {
    transform: translateY(-10px);
    opacity: 0;
}

@media (max-width: 640px) {
    .tasks-container header .flex {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }

    .tasks-container header button {
        width: 100%;
        justify-content: center;
    }
}

.loading-shimmer {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: loading 1.5s infinite;
}

@keyframes loading {
    0% {
        background-position: 200% 0;
    }

    100% {
        background-position: -200% 0;
    }
}

button:focus-visible,
input:focus-visible,
textarea:focus-visible,
select:focus-visible {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
}
</style>
