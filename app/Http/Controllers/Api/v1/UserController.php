<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    protected $user;
    public function __construct(User $user)
    {
        $this->user = $user;
    }


    public function index(){

        $user = $this->user->get();

        return response()->json($user);
    }

    public function show($id){

        try {
            $user = $this->user->findOrFail($id);

            return response()->json([
                'user' => $user
            ]);
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }

    public function store(StoreUserRequest $request){
        $data = $request->all();

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

    public function update(Request $request, $id){

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

    public function destroy($id){

        try {
            $user = $this->user->findOrFail($id);
            $user->delete();

            return response()->json([
                'message' => 'Usuário deletado com sucesso'
            ]);

        } catch (\Throwable $th) {

            return $th->getMessage();
        }
    }
}
