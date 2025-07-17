<?php

namespace App\Http\Controllers\Api;

use App\Models\Role;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use OpenApi\Annotations as OA;
 /**
    * @OA\Info(
    *   title="Mon API",
    *   version="1.0.0",
    *   @OA\Contact(
    *     email="Aline@gmail.com"
    *   ),
    * )
    */

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    
    public function index()
    {
        return Role::all();
    }

    /**
     * Store a newly created resource in storage.
     */
     
    public function store(Request $request)
    {
        $request->validate(['libelle' => 'required|unique:roles|in:formateur,superviseur,parent,apprenant,administrateur,auditeur,caissier,vendeur']);

        return Role::create($request->only('libelle'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role)
    {
        return $role;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Role $role)
    {
        $request->validate(['libelle' => 'required|unique:roles,libelle|in:formateur,superviseur,parent,apprenant,administrateur,auditeur,caissier,vendeur' . $role->id]);
        $role->update($request->only('libelle'));
        return $role;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        $role->delete();
        return response()->json(['message' => 'Role supprimé']);
    }
}
