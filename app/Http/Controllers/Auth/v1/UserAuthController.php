<?php

namespace App\Http\Controllers\Auth\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use App\Http\Requests\User\UserLoginRequest;
use App\Models\ResetCode;
use App\Models\User;
use Carbon\Carbon;
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
            'token' => $token
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
            $user = $this->user->where(["email" => $request->email])->first();

            if(!$user){
                return response()->json(["error" => "Email não encontrado"], 404);
            }

            $email = $user->email;
            $code = Str::random(8);

            $codeExist = ResetCode::where('user_id', $user->id)->first();



            if($codeExist)
            {
                $codeExist->update([
                    'code' => $code,
                    'count' => 0
                ]);
            }else{
                ResetCode::create([
                    'code' => $code,
                    'count' => 0,
                    'user_id' => $user->id
                ]);
            }

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

    public function validateCode($user_id, $code)
    {
        $user = $this->user->find($user_id);
        $codeExist = ResetCode::where('user_id', $user_id)
                                ->where('code', $code)
                                ->first();

        if(!$codeExist){
            return response()->json([
                'error' => 'CÓDIGO INVÁLIDO'
            ], 498);
        }

        if($codeExist->count > 4)
        {
            $codeExist->delete();
            return response()->json([
                'error' => 'TENTATIVAS EXCEDIDAS'
            ], 401);
        }

        if($codeExist->updated_at->diffInMinutes(Carbon::now()) > 15)
        {
            $codeExist->delete();
            return response()->json([
                'error' => 'CÓDIGO EXPIRADO'
            ], 401);
        }

        $codeExist->delete();

        if($user->remember_token != null){//Se o usuário tiver logado em otro dispositivo e quiser resetar a senha
            $user->update(['remember_token' => null]);
            $tokenId = DB::table('personal_access_tokens')->where('tokenable_id', $user_id)->first()->id;
            $user->tokens()->where('id', $tokenId)->delete();
        }

        $tokenResetPassword = $user->createToken('access_token')->plainTextToken;
        $user->update(['remember_token' => $tokenResetPassword]);

        return response()->json([
            'tokenResetPassword' => $tokenResetPassword
        ]);
    }

    public function updatePassword(UpdatePasswordRequest $request, $user_id)
    {
        try {
            $user = $this->user->find($user_id);
            $user->update(['password' => bcrypt($request->password)]);

            //deletar o token da senha
            $user->update(['remember_token' => null]);
            DB::table('personal_access_tokens')->where('tokenable_id', $user_id)->delete();

            return response()->json([
                'msg' => 'senha alterada com sucesso'
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
