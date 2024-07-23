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
