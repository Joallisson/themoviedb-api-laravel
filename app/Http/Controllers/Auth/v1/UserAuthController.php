<?php

namespace App\Http\Controllers\Auth\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use App\Http\Requests\User\UserLoginRequest;
use App\Jobs\expireAccessTokenJob;
use App\Jobs\resetPasswordJob;
use App\Jobs\revokeTokenJob;
use App\Models\PasswordReset;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'error' => 'As credenciais estão incorretas'
            ]);
        }

        $userId = $user->id;
        $existsToken = DB::table('personal_access_tokens')
            ->where('tokenable_id', $userId)
            ->get();

        if ($existsToken->count() > 0) {
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
        try {
            $request->user()->update(['remember_token' => null]);
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'Logout realizado com sucesso'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'Erro ao realizar logout'
            ]);
        }
    }

    public function resetPassword(Request $request)
    {
        try {
            $user = $this->user->where(["email" => $request->email])->first();

            if (!$user) {
                return response()->json(["error" => "Email não encontrado"], 404);
            }

            $email = $user->email;

            $tryResetPassword = PasswordReset::where('email', $user->email)->first();
            if ($tryResetPassword) { //Se o usuário resetar a senha enquanto o token não expirou
                $tryResetPassword->delete();
            }

            $tokenCreated = Str::random(60);

            $passwordReset = new PasswordReset();
            $passwordReset->email = $user->email;
            $passwordReset->token = $tokenCreated;
            $passwordReset->user_id = $user->id;
            $passwordReset->save();

            $linkResetPassword = "http://127.0.0.1:8000/reset_password_reviews/" . $user->email . "/" . $tokenCreated; //essa rota é a do frontend, e lá dentro vai ter o endpoint para resetar a senha e deve ser passao o token do user

            resetPasswordJob::dispatch($linkResetPassword, $email)->delay(now());

            revokeTokenJob::dispatch($passwordReset)->delay(now()->addMinutes(15));
        } catch (\Throwable $th) {
            throw $th;
        }

        return response()->json(['msg' => 'Foi enviado um link para resetar sua senha no seu email'], 200);
    }

    public function validateCode($email, $token)
    {
        $passwordReset = PasswordReset::where('email', $email)
            ->where('token', $token)
            ->first();

        if (!$passwordReset) {
            return response()->json([
                'msg' => 'O link para resetar a senha expirou'
            ]);
        }

        $user = User::find($passwordReset->user_id);

        $expireToken = PasswordReset::where('email', $user->email)->first();
        if ($expireToken) { //Se o token de reset da senha do usuário não tiver expirado na hora que ele criar uma nova senha
            $expireToken->delete();
        }

        $access_token = DB::table('personal_access_tokens')->where('tokenable_id', $user->id)->first();

        if ($access_token) { //deslogar o usuário caso ele esteja logado em outro dispositivo
            $user->update(['remember_token' => null]);
            $user->tokens()->delete();
        }

        $tokenResetPassword = $user->createToken('access_token')->plainTextToken;

        expireAccessTokenJob::dispatch($user)->delay(now()->addMinutes(15));

        return response()->json([
            'id' => $user->id,
            'tokenResetPassword' => $tokenResetPassword
        ]);
    }

    public function updateForgotPassword(UpdatePasswordRequest $request, $user_id)
    {
        try {
            $user = $this->user->find($user_id);
            $user->update(['password' => bcrypt($request->password)]);

            //deletar o token da senha
            $user->update(['remember_token' => null]);
            DB::table('personal_access_tokens')->where('tokenable_id', $user_id)->delete();

            return response()->json([
                'msg' => 'senha alterada com sucesso'
            ], 200);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function updatePassword(UpdatePasswordRequest $request, $user_id)
    {
        try {
            $user = $this->user->find($user_id);

            if (!$user) {
                return response()->json([
                    'error' => 'Usuário não encontrado'
                ], 404);
            }

            if (!Hash::check($request->old_password, $user->password)) {
                return response()->json([
                    'error' => 'Senha atual enviada não coincide com a senha cadastrada'
                ], 404);
            }

            $user->update(['password' => bcrypt($request->password)]);

            return response()->json([
                'msg' => 'senha alterada com sucesso'
            ], 200);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
