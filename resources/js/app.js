import { createApp } from 'vue';
import { createPinia } from 'pinia';
import TasksContainer from './components/TasksContainer.vue';

import '../css/app.css';

const app = createApp({
  components: {
    TasksContainer
  },
  template: '<TasksContainer />'
});

const pinia = createPinia();
app.use(pinia);

app.config.globalProperties.$formatDate = (dateString) => {
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

app.config.errorHandler = (err, instance, info) => {
  console.error('Vue error:', err);
  console.error('Component:', instance);
  console.error('Info:', info);

};

app.mount('#app');
