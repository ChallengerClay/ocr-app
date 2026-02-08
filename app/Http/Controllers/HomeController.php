<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use thiagoalessio\TesseractOCR\TesseractOCR;
use Illuminate\Support\Facades\Log;
use Exception;

class HomeController extends Controller
{
    private $nutrients = [
    'proteinas' => [
        'labels' => ['PROTEINAS'],
        'unit' => '%'
    ],
    'carbohidratos' => [
        'labels' => ['CARBOHIDRATOS'],
        'unit' => '%'
    ],
    'grasa' => [
        'labels' => ['GRASA'],
        'unit' => '%'
    ],
    'energia' => [
        'labels' => ['ENERGIA'],
        'unit' => 'KCAL'
    ],
    'vitamina_a' => [
        'labels' => ['VITAMINA A'],
        'unit' => 'UI'
    ],
    'vitamina_d' => [
        'labels' => ['VITAMINA D'],
        'unit' => 'UI'
    ],
];
    public function index(){
        return view('ocr_home');
    }

    public function process(Request $request){
        $request->validate([
            'image' => 'required|image|max:5120',
        ]);
        try{
            $text = $this->getImgText($request);
            return view('ocr_result',compact('text'));
        }catch(\Throwable $e){
            Log::channel('ocr')->warning('OCR falló', ['error' => $e->getMessage()]);
            return redirect()->back()->withInput()->withErrors(['error' => 'Ha ocurrido un error al procesar la imagen, por favor mejorar la calidad o acercar la información que quieres extraer']);
        }

    }

    private function getImgText(Request $request){
        $ocr = new TesseractOCR();

        $path = $request->file('image')->store('ocr', 'public');

        $fullPath = storage_path('app/public/' . $path);
            $ocr->image($fullPath)->lang('spa')->quiet(true);;
            $text =  $ocr->run();
            $text = strtoupper($text);
            $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);

            // normalizaciones OCR
            $text = str_replace(['{','}','[',']','|', '—', '*'], ' ', $text);
            $text = preg_replace('/\s+/', ' ', $text);
            $text = trim($text);
            $text = $this->normalizarOCR($text);
            // Borrar la imagen después de procesar
            Storage::disk('public')->delete($path);
            return $this->formatResponse($text);
    }

    private function normalizarOCR(string $texto): string
    {
        $texto = mb_strtoupper($texto, 'UTF-8');
        $texto = preg_replace('/\s+/', ' ', $texto);

        $map = [
            'PROTEiNAS' => 'PROTEINAS',
            'ENERGiA' => 'ENERGIA',
            'VITAMINA D (U' => 'VITAMINA D (UI)',
            'VITAMINA A (U' => 'VITAMINA A (UI)',
            'OXiGENO' => 'OXIGENO',
            'GeRMENES' => 'GERMENES',
        ];

        return str_replace(array_keys($map), array_values($map), $texto);
    }

    private function extraerNutrientes(String $text){
        $resultado = [];

    foreach ($this->nutrients as $key => $data) {
        foreach ($data['labels'] as $label) {

            // Regex tolerante: número con coma o punto + unidad opcional
            $pattern = '/'.$label.'\s*[:\-]?\s*([\d]+[\,\.]?[\d]*)\s*%?|'.$label.'.*?([\d]+[\,\.]?[\d]*)/';

            if (preg_match($pattern, $text, $match)) {
                $valor = $match[1] ?? $match[2] ?? null;

                if ($valor !== null) {
                    $resultado[$key] = [
                        'valor' => str_replace(',', '.', $valor),
                        'unidad' => strtolower($data['unit'])
                    ];
                }
                break;
            }
        }
    }
        return $resultado;
    }

    private function detectarNombreGenerico(string $texto): ?string
    {
        // Intenta capturar una línea larga sin números (probable nombre)
        if (preg_match('/^([A-Z\s]{10,})/', $texto, $m)) {
            return trim($m[1]);
        }

        return null;
    }

    private function extraerProcesamiento(string $texto): array
    {
        return [
            'proceso' => str_contains($texto, 'ULTRAPASTEURIZACION') || str_contains($texto, 'UHT')
                ? 'Ultrapasteurización (UHT)'
                : null,

            'conservacion' => str_contains($texto, 'REFRIGERADO')
                ? 'Refrigerar después de abrir'
                : 'No requiere refrigeración antes de abrir'
        ];
    }
    private function extraerAdvertencias(string $texto): array
    {
        $advertencias = [];

        if (preg_match('/ALERGIC|LACTOSA|LECHE/', $texto)) {
            $advertencias[] = 'Contiene leche y lactosa';
        }

        if (str_contains($texto, 'HIPERSENSIBIL')) {
            $advertencias[] = 'Puede causar hipersensibilidad en personas alérgicas';
        }

        return $advertencias;
    }

    private function extraerContacto(string $texto): array
    {
        preg_match('/WWW\.[A-Z0-9\.]+/', $texto, $web);
        preg_match('/\(?0?\d{3,4}\)?[-\s]?[A-Z\s0-9]+/', $texto, $tel);

        return [
            'web' => $web[0] ?? null,
            'telefono' => $tel[0] ?? null
        ];
    }

    private function textoResidual(string $texto, array $extraido): string
    {
        foreach ($extraido as $bloque) {
            if (is_array($bloque)) {
                foreach ($bloque as $v) {
                    if (is_string($v)) {
                        $texto = str_replace($v, '', $texto);
                    }
                }
            }
        }
        return trim($texto);
    }

    private function extraerIngredientes(string $text){
         if (!$text) {
        return null;
        }

        if (preg_match('/INGREDIENTES:\s*([^\.]+)/i', $text, $matches)) {
            return explode(",",trim($matches[1]));
        }

        return null;
    }

    private function formatResponse(string $text){
        $data = [
        'producto' => $this->detectarNombreGenerico($text),
        'nutricional' => $this->extraerNutrientes($text),
        'ingredientes' => $this->extraerIngredientes($text),
        'procesamiento' => $this->extraerProcesamiento($text),
        'advertencias' => $this->extraerAdvertencias($text),
        'contacto' => $this->extraerContacto($text),
        ];

        $data['residual'] = $this->textoResidual($text, $data);
        return $data;
    }
}