import axios from 'axios';

const api = axios.create({
  baseURL: '/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest'
  }
});

api.interceptors.request.use(
  (config) => {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (token) {
      config.headers['X-CSRF-TOKEN'] = token;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

api.interceptors.response.use(
  (response) => {
    return response;
  },
  (error) => {
    if (error.response?.status === 422) {
      const errors = error.response.data.errors || {};
      const message = Object.values(errors).flat().join(', ');
      throw new Error(message || 'Dados inválidos');
    }

    if (error.response?.status === 404) {
      throw new Error('Tarefa não encontrada');
    }

    if (error.response?.status >= 500) {
      throw new Error('Erro interno do servidor. Tente novamente mais tarde.');
    }

    throw new Error(error.response?.data?.message || 'Erro desconhecido');
  }
);

class TaskService {
  async getTasks(params = {}) {
    try {
      const response = await api.get('/tasks', { params });
      return response.data;
    } catch (error) {
      console.error('Erro ao buscar tarefas:', error);
      throw error;
    }
  }

  async getTask(id) {
    try {
      const response = await api.get(`/tasks/${id}`);
      return response.data;
    } catch (error) {
      console.error(`Erro ao buscar tarefa ${id}:`, error);
      throw error;
    }
  }

  async createTask(taskData) {
    try {
      const response = await api.post('/tasks', taskData);
      return response.data;
    } catch (error) {
      console.error('Erro ao criar tarefa:', error);
      throw error;
    }
  }

  async updateTask(id, taskData) {
    try {
      const response = await api.put(`/tasks/${id}`, taskData);
      return response.data;
    } catch (error) {
      console.error(`Erro ao atualizar tarefa ${id}:`, error);
      throw error;
    }
  }

  async deleteTask(id) {
    try {
      const response = await api.delete(`/tasks/${id}`);
      return response.data;
    } catch (error) {
      console.error(`Erro ao excluir tarefa ${id}:`, error);
      throw error;
    }
  }

  async toggleTask(id) {
    try {
      const response = await api.patch(`/tasks/${id}/toggle`);
      return response.data;
    } catch (error) {
      console.error(`Erro ao alterar status da tarefa ${id}:`, error);
      throw error;
    }
  }

  formatDateForApi(date) {
    if (!date) return null;

    if (typeof date === 'string') {
      date = new Date(date);
    }

    return date.toISOString();
  }

  formatDateForDisplay(dateString) {
    if (!dateString) return '';

    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  }

  isOverdue(dateString) {
    if (!dateString) return false;
    return new Date(dateString) < new Date();
  }
}

export default new TaskService();
