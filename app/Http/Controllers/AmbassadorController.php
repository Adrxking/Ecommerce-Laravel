<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class AmbassadorController extends Controller
{
    public function index() // Obtener todos los Vendedores
    {
        //return User::where('is_admin', 0)->get();
        return User::ambassadors()->get();
    }
}
