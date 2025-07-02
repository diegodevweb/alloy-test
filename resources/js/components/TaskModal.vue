<template>
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="show"
        class="modal-overlay"
        @click="handleBackdropClick"
      >
        <div class="modal-backdrop"></div>

        <div class="modal-container">
          <div
            ref="modalContent"
            class="modal-content"
            @click.stop
          >
            <div class="modal-header">
              <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900">
                  {{ isEdit ? 'Editar Tarefa' : 'Nova Tarefa' }}
                </h3>
                <button
                  @click="$emit('close')"
                  class="modal-close-btn"
                >
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                  </svg>
                </button>
              </div>
            </div>

            <div class="modal-body">
              <TaskForm
                :task="task"
                :loading="loading"
                @submit="handleSubmit"
                @cancel="$emit('close')"
              />
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, watch, nextTick, computed, onMounted, onUnmounted } from 'vue';
import TaskForm from '@/components/TaskForm.vue';

const props = defineProps({
  show: { type: Boolean, default: false },
  task: { type: Object, default: null },
  loading: { type: Boolean, default: false }
});

const emit = defineEmits(['close', 'submit']);
const modalContent = ref(null);
const isEdit = computed(() => props.task && props.task.id);

const handleBackdropClick = () => {
  emit('close');
};

const handleSubmit = (taskData) => {
  emit('submit', taskData);
};

watch(() => props.show, async (newValue) => {
  if (newValue) {
    await nextTick();
    const firstInput = modalContent.value?.querySelector('input, textarea');
    if (firstInput) {
      firstInput.focus();
    }
    document.body.style.overflow = 'hidden';
  } else {
    document.body.style.overflow = '';
  }
});

const handleKeydown = (event) => {
  if (event.key === 'Escape' && props.show) {
    emit('close');
  }
};

onMounted(() => {
  document.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
  document.body.style.overflow = '';
  document.removeEventListener('keydown', handleKeydown);
});
</script>

<style scoped>
/* Modal Layout */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 50;
  overflow-y: auto;
}

.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  transition: opacity 0.3s ease;
}

.modal-container {
  display: flex;
  min-height: 100vh;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

.modal-content {
  background: white;
  border-radius: 0.5rem;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  transform: translateY(0);
  transition: all 0.3s ease;
  max-width: 32rem;
  width: 100%;
  max-height: 90vh;
  overflow: hidden;
  position: relative;
  z-index: 51;
}

.modal-header {
  padding: 1.5rem 1.5rem 1rem 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.modal-body {
  padding: 1.5rem;
  overflow-y: auto;
  max-height: calc(90vh - 140px);
}

.modal-close-btn {
  color: #9ca3af;
  transition: color 0.2s ease;
}

.modal-close-btn:hover {
  color: #4b5563;
}

/* Animações */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-active .modal-content,
.modal-leave-active .modal-content {
  transition: transform 0.3s ease;
}

.modal-enter-from .modal-content,
.modal-leave-to .modal-content {
  transform: scale(0.95) translateY(-10px);
}
</style>
