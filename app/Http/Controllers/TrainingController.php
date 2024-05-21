<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Exercise;
use App\Models\Training;
use Illuminate\Support\Facades\Auth;

class TrainingController extends Controller
{
    public function index()
    {
        try {
            $trainings = Training::all();
            return response()->json(['trainings' => $trainings], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error fetching trainings: ' . $e->getMessage()], 500);
        }
    }

    public function new()
    {
        try {
            $titel = "Nuevo entrenamiento";
            $controller = route('Trainings.store');
            $exercisesInTraining = [];
            $exercisesWithoutTraining = Exercise::all();

            return response()->json([
                'titel' => $titel,
                'controller' => $controller,
                'exercisesInTraining' => $exercisesInTraining,
                'exercisesWithoutTraining' => $exercisesWithoutTraining,
                'js' => ['tableChanges.js']
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error preparing new training: ' . $e->getMessage()], 500);
        }
    }

    public function edit($id)
    {
        try {
            $titel = "Editando entrenamiento";
            $controller = route('Trainings.store');
            $training = Training::findOrFail($id);
            $exercisesInTraining = $training->exercises;
            $exercisesWithoutTraining = Exercise::all()->diff($exercisesInTraining);

            return response()->json([
                'training' => $training,
                'titel' => $titel,
                'controller' => $controller,
                'id_training' => $id,
                'exercisesInTraining' => $exercisesInTraining,
                'exercisesWithoutTraining' => $exercisesWithoutTraining,
                'js' => ['tableChanges.js']
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error editing training: ' . $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'nameTraining' => 'required|string|max:255',
            'dia' => ['required', 'string', 'in:Lunes,Martes,Miércoles,Jueves,Viernes,Sábado,Domingo'],
        ]);

        $nameTraining = htmlspecialchars(strip_tags($request->input('nameTraining')));
        $dia = ucfirst($request->input('dia'));
        $trainingId = ucfirst($request->input('id_training'));

        try {
            if ($trainingId == "New") {
                $training = new Training();
            } else {
                $training = Training::findOrFail($trainingId);
            }

            $training->name = $nameTraining;
            $training->day = $dia;
            $training->trainer_id = auth()->user()->id;
            $training->save();

            if ($request->has('exercisesInTraining')) {
                $exercisesIds = explode(';', $request->input('exercisesInTraining'));
                $training->exercises()->sync($exercisesIds);
            }

            if ($request->has('exercisesWithoutTraining')) {
                $exercisesIds = explode(';', $request->input('exercisesWithoutTraining'));
                $exercisesIds = array_unique($exercisesIds);
                foreach ($exercisesIds as $exerciseId) {
                    if ($training->exercises->contains($exerciseId)) {
                        $training->exercises()->detach($exerciseId);
                    }
                }
            }

            return response()->json(['success' => 'Entrenamiento creado correctamente'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al guardar el entrenamiento: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $training = Training::find($id);

            if (!$training) {
                return response()->json(['error' => 'Entrenamiento no encontrado'], 404);
            }

            if ($training->trainer_id == auth()->user()->id || auth()->user()->role == 'admin') {
                $training->delete();
                return response()->json(['success' => 'Entrenamiento borrado correctamente'], 200);
            } else {
                return response()->json(['error' => 'No tienes permisos para borrar este entrenamiento'], 403);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al intentar borrar el entrenamiento: ' . $e->getMessage()], 500);
        }
    }
}