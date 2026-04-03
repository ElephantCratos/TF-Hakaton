<?php

namespace Modules\Core\Abstracts\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Core\Traits\ApiResponse;

abstract class BaseController extends Controller
{
    use ApiResponse;
}
