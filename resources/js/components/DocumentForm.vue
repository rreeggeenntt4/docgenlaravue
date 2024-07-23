<template>
  <h1>Создание документа</h1>
    <div>
      <form @submit.prevent="generateDocument">
        <div>
          <label for="title">Название документа:</label>
          <input type="text" v-model="title" id="title" required>
        </div>
        <div>
          <label for="date">Дата создания:</label>
          <input type="date" v-model="date" id="date" required>
        </div>
        <button type="submit">Сгенерировать</button>
      </form>
    </div>
  </template>
  
  <script>
  import axios from 'axios';
  
  export default {
    data() {
      return {
        title: '',
        date: ''
      }
    },
    methods: {
      async generateDocument() {
        const response = await axios.post('/generate', {
          title: this.title,
          date: this.date
        }, {
          responseType: 'blob'
        });
  
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', 'document.docx');
        document.body.appendChild(link);
        link.click();
      }
    }
  }
  </script>