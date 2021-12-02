<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Http\Requests\UpdateInfoRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    # Registrar usuario
    public function register(RegisterRequest $request) // Para hacer Requests personalizadas
    {
        $user = User::create(
            $request->only('first_name', 'last_name', 'email')
                + [
                    'password' => \Hash::make($request->input('password')), // Para hashear la contraseña
                    'is_admin' => $request->path() === 'api/admin/register' ? 1 : 0 // Para indicar si es ambassador o admin
                ]
        );
        return response($user, Response::HTTP_CREATED);
    }

    # Iniciar sesión
    public function login(Request $request)
    {
        if (!\Auth::attempt($request->only('email', 'password'))) {
            // Si no es satisfactorio
            return response([
                'error' => 'invalid credentials'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user = \Auth::user();

        $adminLogin = $request->path() === 'api/admin/login';

        # Comprobar que el login es de un admin
        if ($adminLogin && !$user->is_admin) {
            return response([
                'error' => 'Access Denied!'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $scope = $adminLogin ? 'admin' : 'ambassador';
        # Crear token
        $jwt = $user->createToken('token', [$scope])->plainTextToken; // Con ['admin'] creamos un scope para saber que ese token es de admin

        // Enviar jwt via cookies
        $cookie = cookie('jwt', $jwt, 60 * 24); // 1 día

        return response([
            'message' => 'success'
        ])->withCookie($cookie);
    }

    # Obtener la información del usuario con sesion iniciada
    public function user(Request $request)
    {
        $user = $request->user();
        # Añadimos solo el revenue si es el ambassador
        return new UserResource($user);
    }

    public function logout()
    {
        $cookie = \Cookie::forget('jwt'); // Para olvidar el jwt de las cookies

        return response([
            'message' => 'success'
        ])->withCookie($cookie);
    }

    # Actualizar información del usuario logueado
    public function updateInfo(UpdateInfoRequest $request)
    {
        $user = $request->user();

        $user->update($request->only('first_name', 'last_name', 'email'));

        return response($user, Response::HTTP_ACCEPTED);
    }

    public function updatePassword(UpdatePasswordRequest $request)
    {
        $user = $request->user();

        $user->update([
            'password' => \Hash::make($request->input('password'))
        ]);

        return response($user, Response::HTTP_ACCEPTED);
    }
}
