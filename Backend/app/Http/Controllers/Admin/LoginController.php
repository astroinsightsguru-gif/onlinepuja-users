<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{
    public function login()
    {
        $data = ['Feni', 'Patel', 'Nishi', 'Shah'];
        return view('login.main', ['users' => $data]);
    }

    public function loginApi(Request $request)
    {
        try {
            $email = trim(strtolower($request->email ?? ''));
            $password = $request->password ?? '';

            $validator = Validator::make([
                'email' => $email,
                'password' => $password,
            ], [
                'email' => 'required|email',
                'password' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => $validator->getMessageBag()->toArray(),
                ], 422);
            }

            $user = DB::table('users')
                ->whereRaw('LOWER(email) = ?', [$email])
                ->where('isDelete', 0)
                ->first();

            if (!$user) {
                return response()->json([
                    'error' => ['Account not found or inactive.'],
                ], 422);
            }

            if (!password_verify($password, $user->password)) {
                return response()->json([
                    'error' => ['Invalid email or password.'],
                ], 422);
            }

            $adminUser = \App\Models\AdminModel\User::where('id', $user->id)->first();
            if (!$adminUser) {
                return response()->json([
                    'error' => ['Admin account model could not be resolved.'],
                ], 422);
            }

            Auth::guard('web')->login($adminUser, false);
            $request->session()->regenerate();
            $request->session()->save();

            $userId = $adminUser->id;
            $teamMember = DB::table('teammember')->where('userId', $userId)->first();
            $first = '/admin/dashboard';

                if ($teamMember) {
                    $sideMenu = [];
                    $rolePages = DB::table('rolepages')
                        ->join('adminpages', 'adminpages.id', 'rolepages.adminPageId')
                        ->where('teamRoleId', $teamMember->teamRoleId)
                        ->select('adminpages.*')
                        ->get();
                    $pageGroup = DB::table('adminpages')
                        ->whereNull('pageGroup')
                        ->get();
                    for ($i = 0; $i < count($pageGroup); $i++) {
                        $pages = DB::table('adminpages')
                            ->where('pageGroup', $pageGroup[$i]->id)
                            ->get();
                        $pageGroup[$i]->sub_menu = [];
                        if ($pages && count($pages) > 0) {
                            for ($j = 0; $j < count($rolePages); $j++) {
                                $id = $rolePages[$j]->id;
                                $result = array_filter(json_decode($pages), function ($event) use ($id) {
                                    return $event->id === $id;
                                });
                                if ($result && count($result) > 0) {
                                    array_push($pageGroup[$i]->sub_menu, $rolePages[$j]);
                                }
                            }
                        }
                    }
                    for ($i = 0; $i < count($pageGroup); $i++) {
                        if ($pageGroup[$i]->sub_menu && count($pageGroup[$i]->sub_menu) > 0) {
                            array_push($sideMenu, $pageGroup[$i]);
                        }
                    }
                    $parentPages = DB::table('rolepages')
                        ->join('adminpages', 'adminpages.id', 'rolepages.adminPageId')
                        ->where('teamRoleId', $teamMember->teamRoleId)
                        ->whereNull('adminpages.pageGroup')
                        ->select('adminpages.*')
                        ->get();
                    for ($i = 0; $i < count($parentPages); $i++) {
                        $parentPages[$i]->sub_menu = [];
                    }
                    $sideMenu = array_merge($sideMenu, json_decode($parentPages));
                    $sort = [];
                    foreach ($sideMenu as $key => $row) {
                        $sort[$key] = $row->displayOrder ?? 0;
                    }

                    if (!empty($sideMenu)) {
                        array_multisort($sort, SORT_ASC, $sideMenu);
                        if (!empty($sideMenu[0]->sub_menu) && count($sideMenu[0]->sub_menu) > 0) {
                            $first = '/admin/' . $sideMenu[0]->sub_menu[0]->route;
                        } elseif (!empty($sideMenu[0]->route)) {
                            $first = '/admin/' . $sideMenu[0]->route;
                        }
                    }
                }

                return response()->json([
                    'success' => "Login Success",
                    'first' => $first,
                ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => ['An unexpected error occurred: ' . $e->getMessage()],
            ], 500);
        }
    }

}
