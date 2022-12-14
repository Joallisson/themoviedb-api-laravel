<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    protected $user;
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function index()
    {

        $user = $this->user->get();

        return response()->json($user);
    }

    public function show($id)
    {
        try {
            $user = $this->user->find($id);

            if (!$user) {
                return response()->json([
                    'error' => 'Usuário não encontrado'
                ], 404);
            }

            return response()->json([
                'user' => $user
            ], 200);
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }

    public function store(StoreUserRequest $request)
    {
        $data = $request->all();

        if ($request->hasFile('photo_profile')) {
            $image = $request->file('photo_profile');
            $imagePath = $image->store('photo_profile', 'public');
            $data["photo_profile"] = $imagePath;
        }

        try {

            $data["password"] = bcrypt($data["password"]);

            $user = $this->user->create($data);

            return response()->json([
                'message' => 'usuário cadastrado com sucesso',
                'data' => $user
            ]);
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $data = $request->all();
            $user = $this->user->findOrFail($id);

            $user->update($data);

            return response()->json([
                'message' => 'user atualizado com sucesso',
                'data' => $user
            ]);
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }

    public function destroy($id)
    {

        try {
            $user = $this->user->findOrFail($id);
            Storage::disk('public')->delete($user->photo_profile);
            $user->delete();

            return response()->json([
                'message' => 'Usuário deletado com sucesso'
            ]);
        } catch (\Throwable $th) {
            return response()->json("USUÁRIO NÃO ENCONTRADO NO BANCO DE DADOS", 500);
        }
    }
}
