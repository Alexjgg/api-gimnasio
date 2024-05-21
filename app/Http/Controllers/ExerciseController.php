<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Exercise;

class ExerciseController extends Controller
{
    public function index()
    {
        try {
            $userId = auth()->user()->id;
            if (auth()->user()->role == 'admin') {
                $exercises = Exercise::all();
            } else {
                $exercises = Exercise::where('user_id', $userId)->get();
            }
            return response()->json(['exercises' => $exercises], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error fetching exercises: ' . $e->getMessage()], 500);
        }
    }

    public function create()
    {
        try {
            $textoView = "Nuevo ejercicio";
            $controller = route('Exercises.store');
            return response()->json(['controller' => $controller, 'textoView' => $textoView], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error preparing new exercise: ' . $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'description' => 'required|string',
            'repetitions' => 'required|string',
            'duration' => 'required|string',
        ]);

        try {
            $exercise = new Exercise;
            $exercise->user_id = auth()->user()->id;
            $exercise->name = $request->input('name');
            $exercise->description = $request->input('description');
            $exercise->repetitions = $request->input('repetitions');
            $exercise->duration = $request->input('duration');
            $exercise->save();

            return response()->json(['success' => 'Ejercicio creado correctamente', 'exercise' => $exercise], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al crear el ejercicio: ' . $e->getMessage()], 500);
        }
    }

    public function edit($id)
    {
        try {
            $exercise = Exercise::find($id);
            if (!$exercise) {
                return response()->json(['error' => 'Ejercicio no encontrado'], 404);
            }
            $action = 'PUT';
            $textoView = "Editar ejercicio";
            $controller = route('Exercises.update', ['id' => $id]);

            return response()->json(['exercise' => $exercise, 'controller' => $controller, 'action' => $action, 'textoView' => $textoView], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al preparar la edición del ejercicio: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string',
            'description' => 'required|string',
            'repetitions' => 'required|string',
            'duration' => 'required|string',
        ]);

        try {
            $exercise = Exercise::findOrFail($id);

            $exercise->name = $request->input('name');
            $exercise->description = $request->input('description');
            $exercise->repetitions = $request->input('repetitions');
            $exercise->duration = $request->input('duration');
            $exercise->save();

            return response()->json(['success' => 'Ejercicio actualizado correctamente', 'exercise' => $exercise], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al actualizar el ejercicio: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $exercise = Exercise::find($id);

            if (!$exercise) {
                return response()->json(['error' => 'Ejercicio no encontrado'], 404);
            }

            if ($exercise->user_id == auth()->user()->id || auth()->user()->role == 'admin') {
                $exercise->delete();
                return response()->json(['success' => 'Ejercicio eliminado correctamente'], 200);
            } else {
                return response()->json(['error' => 'No tienes permisos para eliminar este ejercicio'], 403);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al intentar borrar el ejercicio: ' . $e->getMessage()], 500);
        }
    }
}
