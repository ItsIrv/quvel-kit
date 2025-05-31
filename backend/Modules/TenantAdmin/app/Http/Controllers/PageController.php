<?php

namespace Modules\TenantAdmin\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PageController extends Controller
{
    /**
     * Show the installation page
     */
    public function show(Request $request)
    {
        return view('tenantadmin::app');
    }
}
