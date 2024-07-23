## Задание:

Используя:
- PhpOffice/PHPWord - https://github.com/PHPOffice/PHPWord
- Laravel
- Vue.js

"Создать приложение для генерации документа в формате Word по 2 полям:
- Название документа - обязательное поле
- Дата создания документ

Приложение состоит из формы с кнопкой.
Кнопка 'Сгенерировать' - генерирует документ и позволяет пользователю скачать."

## Решение:
### 1) Установка Laravel

```
composer create-project --prefer-dist laravel/laravel docgenlaravue "11.*"
cd docgenlaravue
```

### 2) Установите Vue:
```bash
npm install vue@latest vue-loader@latest
npm install --save-dev @vitejs
npm install
npm run dev
```

### 3) Создание Vue компонента
Создайте Vue компонент:
В resources/js/components создайте файл DocumentForm.vue:

```html
<template>
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
```


### 4) Установка PhpOffice/PHPWord
```bash
composer require phpoffice/phpword
```
### 5) Создание контроллера и маршрутов
   Созданеие контрооллера:
```bash
   php artisan make:controller DocumentController
```
   Создание маршрутов:
   В routes/web.php добавьте следующие строки:
```php
use App\Http\Controllers\DocumentController;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/generate', [DocumentController::class, 'generate']);

```
### 6) Логика контроллера для генерации документа
В app/Http/Controllers/DocumentController.php добавьте логику для генерации документа:
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function generate(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'date' => 'required|date'
        ]);

        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        $section->addTitle('Название документа: ' . $request->input('title'), 1);
        $section->addText('Дата создания: ' . $request->input('date'));

        $fileName = 'doc_' . time() . '.docx';
        $filePath = storage_path('app/public/' . $fileName);

        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($filePath);

        // Save the file to the fake storage
        Storage::disk('public')->put($fileName, file_get_contents($filePath));

        return response()->download($filePath, $fileName)->deleteFileAfterSend(true);
    }
}

```

### 7) Отредактируйте `resources\js\app.js`
```js
import './bootstrap';

import { createApp } from 'vue';
import DocumentForm from './components/DocumentForm.vue';

createApp(DocumentForm).mount('#app');
```

### 8) Отредактируйте `vite.config.js`
```js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue(),
    ],
});
```

### 9) Отредактируйте `resources\views\welcome.blade.php`
```html
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Laravel</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />

        <!-- Styles -->
        @vite('resources/css/app.css')
    </head>
    <body>
        <div id="app"></div>
        @vite('resources/js/app.js')
    </body>
</html>
```

---
## Пример теста для приложения

Для написания теста для контроллера, который генерирует и скачивает документ Word, можно использовать тестирование HTTP запросов в Laravel. Это позволяет проверить, что ваш контроллер корректно обрабатывает входные данные и генерирует ожидаемый файл. Для этого можно использовать встроенные возможности тестирования в Laravel, такие как TestResponse и проверки содержимого ответа.

Вот пример теста для вашего контроллера:
1) Создайте тестовый класс:
```
php artisan make:test DocumentControllerTest
```
2) Напишите тест в tests/Feature/DocumentControllerTest.php:
```php
<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Storage;

class DocumentControllerTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function genandup()
    {
        // Mocking storage
        Storage::fake('public');

        // Create a test request payload
        $data = [
            'title' => 'Test Document',
            'date' => '2024-07-23',
        ];

        // Mock time to ensure consistent filename
        $fixedTime = time();
        $this->travelTo(now()->setTimestamp($fixedTime));

        // Send POST request to the controller
        $response = $this->post('/generate', $data);

        // Assert that the response is a file download
        $response->assertStatus(200);

        // Get the content-disposition header
        $contentDisposition = $response->headers->get('content-disposition');

        // Check if the response headers contain 'content-disposition' header with expected filename
        $expectedFilename = 'attachment; filename=doc_' . $fixedTime . '.docx';
        $this->assertStringContainsString(
            $expectedFilename,
            $contentDisposition,
            'Content-Disposition header does not contain the expected filename. Actual header: ' . $contentDisposition
        );

        // Assert the file was stored in the fake storage
        $expectedFilePath = 'doc_' . $fixedTime . '.docx';
        $this->assertTrue(Storage::disk('public')->exists($expectedFilePath), 'The file does not exist in fake storage.');

        // Assert that exactly one file was created
        $files = Storage::disk('public')->allFiles();
        $this->assertCount(1, $files, 'No files were found in storage.');
    }
}

```
---
## Объяснение теста:
1) Mocking storage:
Мы используем Storage::fake('public') для мока файловой системы. Это позволяет тесту создавать файлы в виртуальном хранилище, которое будет очищено после завершения теста.

2) Создание тестовых данных:
Мы создаем массив данных, который будет отправлен в запросе. В вашем случае это title и date.

3) Отправка POST запроса:
Мы используем метод $this->post('/generate', $data) для отправки POST запроса к вашему маршруту генерации документа.

4) Проверка ответа:
Мы проверяем, что ответ имеет статус 200 (успешно) и что заголовок content-disposition указывает на скачивание файла с правильным именем.

5) Проверка сохраненного файла:
Мы проверяем, что файл был создан и сохранен в публичном хранилище, и что имя файла заканчивается на .docx.

## Запуск теста
Чтобы запустить тест, используйте команду:
```
php artisan test
```
Этот тест проверит, что ваш контроллер корректно обрабатывает запрос и генерирует ожидаемый документ Word.