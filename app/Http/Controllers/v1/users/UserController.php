<?php

namespace App\Http\Controllers\v1\users;

use App\Http\Controllers\Controller;
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

    public function store(Request $request){

        $message = [ 'required' => 'The :attribute field is required.'
            // 'name.required' => 'O campo name é obrigatório',
            // 'email.required' => 'O campo email é obrigatório',
            // 'username.required' => 'O campo username é obrigatório',
            // 'password.required' => 'O campo password é obrigatório'
        ];

        $validator = Validator::make($request->all(),
                                    [
                                        'name' => 'required|min:6|max:255|string',
                                        'email' => 'max:255|required|email|unique:App\Models\User,email',
                                        'username' => 'required|min:4|max:50|string',
                                        'password' => 'required|min:8|max:50|string'
                                    ],
                                    $message);

        if($validator->fails()){
            return $message;
        }

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
