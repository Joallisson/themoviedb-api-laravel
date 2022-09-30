<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UserLoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

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

    public function store(StoreUserRequest $request)
    {
        $data = $request->all();

        if($request->hasFile('photo_profile'))
        {
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
            Storage::disk('public')->delete($user->photo_profile);
            $user->delete();

            return response()->json([
                'message' => 'Usuário deletado com sucesso'
            ]);
        }
        catch (\Throwable $th)
        {
            return response()->json("USUÁRIO NÃO ENCONTRADO NO BANCO DE DADOS", 500);
        }
    }

    public function login(UserLoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if(!$user || !Hash::check($request->password, $user->password))
        {
            throw ValidationException::withMessages([
                'error' => 'As credenciais estão incorretas'
            ]);
        }

        $userId = $user->id;
        $existsToken = DB::table('personal_access_tokens')
                            ->where('tokenable_id', $userId)
                            ->get();

        if($existsToken->count() > 0){
            return response()->json(['error' => "O Usuário já está logado"]);
        }

        $token = $user->createToken('access_token')->plainTextToken;
        $user->update(['remember_token' => $token]);

        return response()->json([
            'user' => $user
        ]);
    }

    public function logout(Request $request)
    {
        try
        {
            $request->user()->update(['remember_token' => null]);
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'Logout realizado com sucesso'
            ]);
        }
        catch (\Throwable $th)
        {
            return response()->json([
                'Erro ao realizar logout'
            ]);
        }
    }

    public function resetPassword(Request $request)
    {
        try {
            $user = $this->user->where(["email" => $request->email])->select('email')->get();

            if($user->count() == 0){
                return response()->json(["error" => "Email não encontrado"], 404);
            }
            
            $email = $user[0]->email;
            $code = rand(000000, 999999);

            Mail::send('Mails.ResetPassword', ["code" => $code], function($mensagem) use($email){
                $mensagem->from('joallisson.teste@outlook.com', 'Reviews');
                $mensagem->subject('Código de segurança');
                $mensagem->to($email);
            });
        }
        catch (\Throwable $th)
        {
            return response()->json(['msg' => 'error']);
        }
        return response()->json(['msg' => 'Foi enviado um código de verificação pro seu email'], 200);
    }
}
