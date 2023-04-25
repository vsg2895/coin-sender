<?php

namespace App\Http\Controllers;

use App\Models\{
    Project,
    AmbassadorProjectReport,
};

use App\Http\Resources\{
    ReportCollection,
    Project as ProjectResource,
};

use App\Http\Requests\ReportListRequest;

class ReportController extends Controller
{
    /**
     * Reports
     * @OA\Get (
     *     path="/api/reports",
     *     tags={"Reports"},
     *     @OA\Parameter(
     *         in="query",
     *         name="page",
     *         required=false,
     *         @OA\Schema(type="number"),
     *     ),
     *     @OA\Parameter(
     *         in="query",
     *         name="per_page",
     *         required=false,
     *         @OA\Schema(type="number"),
     *     ),
     *     @OA\Parameter(
     *         in="query",
     *         name="order_by",
     *         required=true,
     *         @OA\Schema(type="string", enum={"asc", "desc"}, example={"asc", "desc"}),
     *     ),
     *     @OA\Parameter(
     *         in="query",
     *         name="project_id",
     *         required=false,
     *         @OA\Schema(type="number"),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="success",
     *         @OA\JsonContent(
     *              @OA\Property(
     *                  property="id",
     *                  type="number",
     *                  example="1",
     *              ),
     *              @OA\Property(
     *                  property="text",
     *                  type="string",
     *                  example="example text",
     *              ),
     *              @OA\Property(
     *                  property="type",
     *                  type="string",
     *                  enum={"Fake or spam", "Technical issues", "Something else"},
     *                  example="Something else",
     *              ),
     *              @OA\Property(
     *                  property="project_name",
     *                  type="string",
     *                  example="Project",
     *              ),
     *              @OA\Property(
     *                  property="ambassador_name",
     *                  type="string",
     *                  example="Talent1",
     *              ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="errors",
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated."),
     *         ),
     *     ),
     *     security={{ "apiAuth": {} }},
     * )
     */
    public function index(ReportListRequest $request)
    {
        $perPage = $request->get('per_page') ?: 10;
        $orderBy = $request->get('order_by') ?: 'desc';
        $projectId = $request->get('project_id');

        $reports = AmbassadorProjectReport::with(['project', 'ambassador'])
            ->when($request->has('project_id'), fn ($query) => $query->where('project_id', $projectId))
            ->orderBy('id', $orderBy)
            ->paginate($perPage);

        return response()->json(new ReportCollection($reports));
    }

    /**
     * List Projects With One Or More Report
     * @OA\Get (
     *     path="/api/reports/projects",
     *     tags={"Reports"},
     *     @OA\Response(
     *         response=200,
     *         description="success",
     *         @OA\JsonContent(
     *              @OA\Property(
     *                  property="id",
     *                  type="number",
     *                  example="1",
     *              ),
     *              @OA\Property(
     *                  property="name",
     *                  type="string",
     *                  example="example name"
     *              ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated."),
     *         ),
     *     ),
     *     security={{ "apiAuth": {} }},
     * )
     */
    public function projects()
    {
        $projects = Project::has('reports')->get();
        return response()->json(ProjectResource::collection($projects));
    }
}
