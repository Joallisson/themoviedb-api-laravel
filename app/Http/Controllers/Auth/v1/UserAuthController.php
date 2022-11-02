<?php

namespace App\Http\Controllers\Auth\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UserLoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class UserAuthController extends Controller
{
    protected $user;
    public function __construct(User $user)
    {
        $this->user = $user;
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
            $code = Str::random(8);

            Mail::send('Mails.ResetPassword', ["code" => $code], function($mensagem) use($email){
                $mensagem->from('joallisson.teste@outlook.com', 'Reviews');
                $mensagem->subject('Código de segurança');
                $mensagem->to($email);
            });
        }
        catch (\Throwable $th)
        {
            throw $th;
            //return response()->json(['msg' => 'error']);
        }
        return response()->json(['msg' => 'Foi enviado um código de verificação pro seu email'], 200);
    }
}
