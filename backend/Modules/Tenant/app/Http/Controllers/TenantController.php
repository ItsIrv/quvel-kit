<?php

namespace Modules\Tenant\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function index(): string
    {
        return 'Welcome to tenant ' . session('tenant_id');
    }
}
