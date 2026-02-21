<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UsuariosController extends Controller
{
    public function LoginMovil(Request $request)
    {
        try {
            $request->validate([
                "email" => 'required|email',
                "password" => 'required'
            ]);
            
            $user = User::where("email", $request->email)->first();
            
            if ($user && Hash::check($request->password, $user->password)) {
                $user->load('roles'); // Cargamos los roles antes de enviarlo
                $token = $user->createToken("auth_token")->plainTextToken;
                return response()->json([
                    'status' => 'ok',
                    'msg' => 'Usuario loggeado exitosamente',
                    "token" => $token,
                    "user" => $user,
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'msg' => 'Credenciales inválidas',
                ], 401);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'msg' => 'Error en el servidor',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function RegisterMovil(Request $request)
    {
        try {
            $request->validate([
                "name" => 'required',
                "email" => 'required|email|unique:users',
                "password" => 'required|min:6'
            ]);
            
            $user = new User();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            $user->save();

            return response()->json([
                'status' => 'ok',
                'msg' => 'Se registro de manera correcta'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'msg' => 'Error al registrar usuario',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function LogOutMovil()
    {
        try {
            Auth::user()->tokens()->delete();
            return response()->json([
                'status' => 'ok',
                'msg' => 'Sesión cerrada exitosamente'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'msg' => 'Error al cerrar sesión'
            ], 500);
        }
    }

    public function ProfileMovil()
    {
        try {
            return response()->json([
                'status' => 'ok',
                'data' => Auth::user()
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'msg' => 'Error al obtener perfil'
            ], 500);
        }
    }
}
