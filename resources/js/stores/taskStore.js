import { defineStore } from 'pinia';
import taskService from '../services/taskService.js';

export const useTaskStore = defineStore('tasks', {
  state: () => ({
    tasks: [],
    currentTask: null,
    loading: false,
    error: null,
    meta: {
      total: 0,
      finalizadas: 0,
      pendentes: 0
    },
    filters: {
      status: '',
      search: ''
    }
  }),

  getters: {
    filteredTasks: (state) => {
      let filtered = [...state.tasks];

      if (state.filters.status) {
        filtered = filtered.filter(task => {
          switch (state.filters.status) {
            case 'finalizada':
              return task.finalizado;
            case 'pendente':
              return !task.finalizado && !taskService.isOverdue(task.data_limite);
            case 'vencida':
              return !task.finalizado && taskService.isOverdue(task.data_limite);
            default:
              return true;
          }
        });
      }

      if (state.filters.search) {
        const search = state.filters.search.toLowerCase();
        filtered = filtered.filter(task =>
          task.nome.toLowerCase().includes(search) ||
          (task.descricao && task.descricao.toLowerCase().includes(search))
        );
      }

      return filtered;
    },


    completedTasks: (state) => {
      return state.tasks.filter(task => task.finalizado);
    },

    pendingTasks: (state) => {
      return state.tasks.filter(task => !task.finalizado);
    },

    overdueTasks: (state) => {
      return state.tasks.filter(task =>
        !task.finalizado && taskService.isOverdue(task.data_limite)
      );
    },

    statistics: (state) => {
      const total = state.tasks.length;
      const completed = state.tasks.filter(task => task.finalizado).length;
      const pending = state.tasks.filter(task => !task.finalizado).length;
      const overdue = state.tasks.filter(task =>
        !task.finalizado && taskService.isOverdue(task.data_limite)
      ).length;

      return {
        total,
        completed,
        pending,
        overdue,
        completionRate: total > 0 ? Math.round((completed / total) * 100) : 0
      };
    }
  },

  actions: {
    setError(error) {
      this.error = error?.message || error || 'Erro desconhecido';
      console.error('Task Store Error:', error);
    },

    clearError() {
      this.error = null;
    },

    async fetchTasks() {
      this.loading = true;
      this.clearError();

      try {
        const response = await taskService.getTasks(this.filters);
        this.tasks = response.data || [];
        this.meta = response.meta || this.meta;
      } catch (error) {
        this.setError(error);
        this.tasks = [];
      } finally {
        this.loading = false;
      }
    },

    async fetchTask(id) {
      this.loading = true;
      this.clearError();

      try {
        const response = await taskService.getTask(id);
        this.currentTask = response.data;
        return response.data;
      } catch (error) {
        this.setError(error);
        this.currentTask = null;
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async createTask(taskData) {
      this.loading = true;
      this.clearError();

      try {
        const response = await taskService.createTask(taskData);

        this.tasks.unshift(response.data);

        this.meta.total++;
        if (response.data.finalizado) {
          this.meta.finalizadas++;
        } else {
          this.meta.pendentes++;
        }

        return response;
      } catch (error) {
        this.setError(error);
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async updateTask(id, taskData) {
      this.loading = true;
      this.clearError();

      try {
        const response = await taskService.updateTask(id, taskData);

        const index = this.tasks.findIndex(task => task.id === id);
        if (index !== -1) {
          this.tasks[index] = response.data;
        }

        if (this.currentTask?.id === id) {
          this.currentTask = response.data;
        }

        return response;
      } catch (error) {
        this.setError(error);
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async deleteTask(id) {
      this.loading = true;
      this.clearError();

      try {
        const response = await taskService.deleteTask(id);

        const index = this.tasks.findIndex(task => task.id === id);
        if (index !== -1) {
          const deletedTask = this.tasks[index];
          this.tasks.splice(index, 1);

          this.meta.total--;
          if (deletedTask.finalizado) {
            this.meta.finalizadas--;
          } else {
            this.meta.pendentes--;
          }
        }

        if (this.currentTask?.id === id) {
          this.currentTask = null;
        }

        return response;
      } catch (error) {
        this.setError(error);
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async toggleTask(id) {
      this.clearError();

      try {
        const response = await taskService.toggleTask(id);

        const index = this.tasks.findIndex(task => task.id === id);
        if (index !== -1) {
          const oldStatus = this.tasks[index].finalizado;
          this.tasks[index] = response.data;

          if (oldStatus !== response.data.finalizado) {
            if (response.data.finalizado) {
              this.meta.finalizadas++;
              this.meta.pendentes--;
            } else {
              this.meta.finalizadas--;
              this.meta.pendentes++;
            }
          }
        }

        if (this.currentTask?.id === id) {
          this.currentTask = response.data;
        }

        return response;
      } catch (error) {
        this.setError(error);
        throw error;
      }
    },

    setFilters(filters) {
      this.filters = { ...this.filters, ...filters };
    },

    clearFilters() {
      this.filters = {
        status: '',
        search: ''
      };
    },

    async refresh() {
      await this.fetchTasks();
    },

    reset() {
      this.tasks = [];
      this.currentTask = null;
      this.loading = false;
      this.error = null;
      this.meta = {
        total: 0,
        finalizadas: 0,
        pendentes: 0
      };
      this.clearFilters();
    }
  }
});
