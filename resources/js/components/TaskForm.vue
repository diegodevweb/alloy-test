<template>
    <form @submit.prevent="handleSubmit" class="space-y-6">
        <div>
            <label for="nome" class="block text-sm font-medium text-gray-700 mb-2">
                Nome da tarefa *
            </label>
            <input id="nome" ref="nomeInput" v-model="form.nome" type="text" required maxlength="255"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                :class="{ 'border-red-300': errors.nome }" placeholder="Digite o nome da tarefa...">
            <p v-if="errors.nome" class="mt-1 text-sm text-red-600">
                {{ errors.nome }}
            </p>
            <p class="mt-1 text-sm text-gray-500">
                {{ form.nome.length }}/255 caracteres
            </p>
        </div>

        <div>
            <label for="descricao" class="block text-sm font-medium text-gray-700 mb-2">
                Descrição
            </label>
            <textarea id="descricao" v-model="form.descricao" rows="4" maxlength="1000"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-vertical"
                :class="{ 'border-red-300': errors.descricao }"
                placeholder="Descreva os detalhes da tarefa (opcional)..."></textarea>
            <p v-if="errors.descricao" class="mt-1 text-sm text-red-600">
                {{ errors.descricao }}
            </p>
            <p class="mt-1 text-sm text-gray-500">
                {{ (form.descricao || '').length }}/1000 caracteres
            </p>
        </div>

        <div>
            <label for="data_limite" class="block text-sm font-medium text-gray-700 mb-2">
                Data limite
            </label>
            <input id="data_limite" v-model="form.data_limite" type="datetime-local" :min="minDate"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                :class="{ 'border-red-300': errors.data_limite }">
            <p v-if="errors.data_limite" class="mt-1 text-sm text-red-600">
                {{ errors.data_limite }}
            </p>
            <p class="mt-1 text-sm text-gray-500">
                Deixe em branco se não houver prazo específico
            </p>
        </div>

        <div v-if="isEdit">
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Status
            </label>
            <div class="flex items-center space-x-4">
                <label class="flex items-center">
                    <input v-model="form.finalizado" type="radio" :value="false"
                        class="mr-2 text-blue-600 focus:ring-blue-500">
                    <span class="text-sm text-gray-700">Pendente</span>
                </label>
                <label class="flex items-center">
                    <input v-model="form.finalizado" type="radio" :value="true"
                        class="mr-2 text-blue-600 focus:ring-blue-500">
                    <span class="text-sm text-gray-700">Finalizada</span>
                </label>
            </div>
        </div>

        <div v-if="showPreview" class="bg-gray-50 border border-gray-200 rounded-lg p-4">
            <h4 class="text-sm font-medium text-gray-700 mb-2">Preview:</h4>
            <div class="space-y-2">
                <p class="font-medium">{{ form.nome || 'Nome da tarefa' }}</p>
                <p v-if="form.descricao" class="text-gray-600 text-sm">{{ form.descricao }}</p>
                <div v-if="form.data_limite" class="flex items-center text-sm text-gray-500">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                        </path>
                    </svg>
                    Prazo: {{ formatDate(form.data_limite) }}
                </div>
            </div>
        </div>

        <div class="flex justify-between items-center pt-4 border-t border-gray-200">
            <div class="flex space-x-2">
                <button type="button" @click="togglePreview"
                    class="px-3 py-2 text-sm text-gray-600 hover:text-gray-800 transition-colors">
                    {{ showPreview ? 'Ocultar' : 'Mostrar' }} preview
                </button>
                <button type="button" @click="clearForm"
                    class="px-3 py-2 text-sm text-gray-600 hover:text-gray-800 transition-colors">
                    Limpar
                </button>
            </div>

            <div class="flex space-x-3">
                <button type="button" @click="$emit('cancel')"
                    class="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors"
                    :disabled="loading">
                    Cancelar
                </button>
                <button type="submit" :disabled="!isFormValid || loading"
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center">
                    <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none"
                        viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                        </circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                    {{ loading ? 'Salvando...' : (isEdit ? 'Atualizar' : 'Criar') }}
                </button>
            </div>
        </div>
    </form>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue';

const props = defineProps({
    task: {
        type: Object,
        default: null
    },
    loading: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['submit', 'cancel']);

const nomeInput = ref(null);
const showPreview = ref(false);

const form = reactive({
    nome: '',
    descricao: '',
    data_limite: '',
    finalizado: false
});

const errors = reactive({
    nome: '',
    descricao: '',
    data_limite: ''
});

const isEdit = computed(() => props.task && props.task.id);

const isFormValid = computed(() => {
    return form.nome.trim().length > 0 &&
        form.nome.length <= 255 &&
        (form.descricao?.length || 0) <= 1000 &&
        !Object.values(errors).some(error => error);
});

const minDate = computed(() => {
    if (isEdit.value) {
        return '';
    }
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    return now.toISOString().slice(0, 16);
});

const validateForm = () => {
    Object.keys(errors).forEach(key => errors[key] = '');

    if (!form.nome.trim()) {
        errors.nome = 'O nome da tarefa é obrigatório.';
    } else if (form.nome.length > 255) {
        errors.nome = 'O nome deve ter no máximo 255 caracteres.';
    }

    if (form.descricao && form.descricao.length > 1000) {
        errors.descricao = 'A descrição deve ter no máximo 1000 caracteres.';
    }

    if (form.data_limite && !isEdit.value) {
        const deadline = new Date(form.data_limite);
        const now = new Date();
        if (deadline <= now) {
            errors.data_limite = 'A data limite deve ser posterior à data atual.';
        }
    }

    return Object.values(errors).every(error => !error);
};

const handleSubmit = () => {
    if (!validateForm()) {
        return;
    }

    const taskData = {
        nome: form.nome.trim(),
        descricao: form.descricao?.trim() || null,
        data_limite: form.data_limite || null,
        finalizado: form.finalizado
    };

    emit('submit', taskData);
};

const clearForm = () => {
    if (confirm('Tem certeza que deseja limpar o formulário?')) {
        Object.assign(form, {
            nome: '',
            descricao: '',
            data_limite: '',
            finalizado: false
        });
        Object.keys(errors).forEach(key => errors[key] = '');
        nomeInput.value?.focus();
    }
};

const togglePreview = () => {
    showPreview.value = !showPreview.value;
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const loadTaskData = () => {
    if (props.task) {
        form.nome = props.task.nome || '';
        form.descricao = props.task.descricao || '';
        form.finalizado = props.task.finalizado || false;

        if (props.task.data_limite) {
            const date = new Date(props.task.data_limite);
            date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
            form.data_limite = date.toISOString().slice(0, 16);
        } else {
            form.data_limite = '';
        }
    }
};

watch(() => props.task, loadTaskData, { immediate: true });

watch([() => form.nome, () => form.descricao, () => form.data_limite], () => {
    validateForm();
});

onMounted(() => {
    loadTaskData();
    if (nomeInput.value) {
        nomeInput.value.focus();
    }
});
</script>

<style scoped></style>
