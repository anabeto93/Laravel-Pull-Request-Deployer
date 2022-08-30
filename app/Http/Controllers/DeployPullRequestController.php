<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeployFormRequest;
use App\Services\DeploymentService;
use Illuminate\Http\Request;

class DeployPullRequestController extends Controller
{

    public function __construct(private DeploymentService $service)
    {
        
    }

    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(DeployFormRequest $request)
    {
        $result = $this->service->deploy($request->validated());

        if ($request->query('cli')) {

            return response()->json($result->data['token'] ?? 'ERROR', $result->error_code);
        }

        return response()->json($result->toArray(), $result->error_code);
    }
}
