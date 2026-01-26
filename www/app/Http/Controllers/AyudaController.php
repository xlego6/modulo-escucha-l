<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AyudaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Mostrar la página de ayuda (FAQ)
     */
    public function index()
    {
        return view('ayuda.index');
    }
}
