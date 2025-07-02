<template>
    <div class="task-item bg-white rounded-lg shadow-sm border hover:shadow-md transition-shadow duration-200" :class="{
        'border-green-200 bg-green-50': task.finalizado,
        'border-red-200 bg-red-50': isOverdue && !task.finalizado,
        'border-gray-200': !task.finalizado && !isOverdue
    }">
        <div class="p-4">
            <div class="flex items-start justify-between">
                <div class="flex items-start space-x-3 flex-1">
                    <button @click="$emit('toggle', task.id)"
                        class="mt-1 flex-shrink-0 w-5 h-5 rounded border-2 flex items-center justify-center transition-colors duration-200"
                        :class="{
                            'bg-green-500 border-green-500 text-white': task.finalizado,
                            'border-gray-300 hover:border-green-400': !task.finalizado
                        }">
                        <svg v-if="task.finalizado" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                clip-rule="evenodd"></path>
                        </svg>
                    </button>

                    <div class="flex-1 min-w-0">
                        <!-- Título -->
                        <h3 class="text-lg font-medium cursor-pointer transition-colors duration-200" :class="{
                            'text-gray-500 line-through': task.finalizado,
                            'text-gray-900 hover:text-blue-600': !task.finalizado
                        }" @click="toggleExpanded">
                            {{ task.nome }}
                        </h3>

                        <p v-if="task.descricao && (expanded || task.descricao.length <= 100)"
                            class="mt-1 text-gray-600" :class="{ 'line-through': task.finalizado }">
                            {{ expanded ? task.descricao : truncatedDescription }}
                        </p>

                        <button v-if="task.descricao && task.descricao.length > 100" @click="toggleExpanded"
                            class="mt-1 text-sm text-blue-600 hover:text-blue-800">
                            {{ expanded ? 'Ver menos' : 'Ver mais' }}
                        </button>

                        <div class="mt-2 flex items-center space-x-4 text-sm text-gray-500">
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Criada {{ formatDate(task.created_at) }}
                            </span>

                            <span v-if="task.data_limite" class="flex items-center" :class="{
                                'text-red-600 font-medium': isOverdue && !task.finalizado,
                                'text-green-600': task.finalizado,
                                'text-orange-600': isNearDeadline && !task.finalizado && !isOverdue
                            }">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                    </path>
                                </svg>
                                {{ getDeadlineText() }}
                            </span>

                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                :class="getStatusBadgeClass()">
                                {{ getStatusText() }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center space-x-2 ml-4">
                    <button @click="$emit('edit', task)"
                        class="p-2 text-gray-400 hover:text-blue-600 transition-colors duration-200"
                        title="Editar tarefa">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                            </path>
                        </svg>
                    </button>

                    <button @click="$emit('delete', task.id)"
                        class="p-2 text-gray-400 hover:text-red-600 transition-colors duration-200"
                        title="Excluir tarefa">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                            </path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import taskService from '@/services/taskService.js';

const props = defineProps({
    task: {
        type: Object,
        required: true
    }
});

defineEmits(['edit', 'toggle', 'delete']);

const expanded = ref(false);

const isOverdue = computed(() => {
    return taskService.isOverdue(props.task.data_limite) && !props.task.finalizado;
});

const isNearDeadline = computed(() => {
    if (!props.task.data_limite || props.task.finalizado) return false;

    const deadline = new Date(props.task.data_limite);
    const now = new Date();
    const hoursDiff = (deadline - now) / (1000 * 60 * 60);

    return hoursDiff > 0 && hoursDiff <= 24;
});

const truncatedDescription = computed(() => {
    if (!props.task.descricao) return '';
    return props.task.descricao.length > 100
        ? props.task.descricao.substring(0, 100) + '...'
        : props.task.descricao;
});

const toggleExpanded = () => {
    expanded.value = !expanded.value;
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffTime = Math.abs(now - date);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    if (diffDays === 1) {
        return 'hoje';
    } else if (diffDays === 2) {
        return 'ontem';
    } else if (diffDays <= 7) {
        return `há ${diffDays - 1} dias`;
    } else {
        return date.toLocaleDateString('pt-BR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    }
};

const getDeadlineText = () => {
    if (!props.task.data_limite) return '';

    const deadline = new Date(props.task.data_limite);
    const now = new Date();

    if (props.task.finalizado) {
        return `Prazo: ${deadline.toLocaleDateString('pt-BR')}`;
    }

    if (deadline < now) {
        const diffTime = Math.abs(now - deadline);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        return `Vencida há ${diffDays} dia${diffDays > 1 ? 's' : ''}`;
    }

    const diffTime = deadline - now;
    const diffHours = Math.ceil(diffTime / (1000 * 60 * 60));
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    if (diffHours <= 24) {
        return `Vence em ${diffHours}h`;
    } else {
        return `Vence em ${diffDays} dia${diffDays > 1 ? 's' : ''}`;
    }
};

const getStatusText = () => {
    if (props.task.finalizado) return 'Finalizada';
    if (isOverdue.value) return 'Vencida';
    if (isNearDeadline.value) return 'Urgente';
    return 'Pendente';
};

const getStatusBadgeClass = () => {
    if (props.task.finalizado) {
        return 'bg-green-100 text-green-800';
    }
    if (isOverdue.value) {
        return 'bg-red-100 text-red-800';
    }
    if (isNearDeadline.value) {
        return 'bg-orange-100 text-orange-800';
    }
    return 'bg-blue-100 text-blue-800';
};
</script>
