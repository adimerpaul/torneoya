<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCampeonatoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'tipo' => ['required', 'in:unico,categorias'],
            'fase_formato' => ['nullable', 'in:todos_contra_todos,todos_contra_todos_eliminatoria,eliminatoria'],
            'deporte_id' => ['nullable', 'integer', 'exists:deportes,id'],
            'descripcion' => ['nullable', 'string'],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_inscripcion' => ['nullable', 'date'],
            'color_primario' => ['nullable', 'string', 'max:20'],
            'color_secundario' => ['nullable', 'string', 'max:20'],
            'logo' => ['nullable', 'string', 'max:255'],
            'portada' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', 'in:borrador,publicado,en_juego,finalizado,cancelado'],
            'categorias' => ['nullable', 'array'],
            'categorias.*.nombre' => ['required_with:categorias', 'string', 'max:255'],
            'categorias.*.deporte_id' => ['required_with:categorias', 'integer', 'exists:deportes,id'],
            'categorias.*.formato' => ['nullable', 'string', 'max:100'],
            'categorias.*.estado' => ['nullable', 'in:borrador,publicado,en_juego,finalizado,cancelado'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $tipo = $this->input('tipo');
            $deporte = $this->input('deporte_id');
            $categorias = $this->input('categorias', []);

            if ($tipo === 'unico' && ! $deporte) {
                $validator->errors()->add('deporte_id', 'El deporte es obligatorio para campeonato unico.');
            }

            if ($tipo === 'unico' && ! $this->input('fase_formato')) {
                $validator->errors()->add('fase_formato', 'La fase del campeonato es obligatoria para campeonato unico.');
            }

            if ($tipo === 'categorias' && count($categorias) === 0) {
                $validator->errors()->add('categorias', 'Debe agregar al menos una categoria.');
            }
        });
    }
}
