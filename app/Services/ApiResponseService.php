<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;

final class ApiResponseService
{
    /**
     * Return a success JSON response.
     *
     * @param mixed  $data
     * @param string $message
     * @param int    $statusCode
     * @param array  $meta
     *
     * @return JsonResponse
     */
    public function success(
        mixed $data = null,
        string $message = 'Request successful',
        int $statusCode = 200,
        array $meta = []
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
            'data'    => $this->transformData($data),
        ];

        if (! empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return an error JSON response.
     *
     * @param string $message
     * @param int    $statusCode
     * @param mixed  $errors
     * @param array  $meta
     *
     * @return JsonResponse
     */
    public function error(
        string $message = 'Request failed',
        int $statusCode = 400,
        mixed $errors = null,
        array $meta = []
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        if (! empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a paginated JSON response.
     *
     * @param LengthAwarePaginatorContract $paginator
     * @param string                       $message
     * @param int                          $statusCode
     * @param array                        $meta
     *
     * @return JsonResponse
     */
    public function paginated(
        LengthAwarePaginatorContract $paginator,
        string $message = 'Request successful',
        int $statusCode = 200,
        array $meta = []
    ): JsonResponse {
        $response = [
            'success'    => true,
            'message'    => $message,
            'data'       => $this->transformData($paginator->items()),
            'pagination' => [
                'total'          => $paginator->total(),
                'per_page'       => $paginator->perPage(),
                'current_page'   => $paginator->currentPage(),
                'last_page'      => $paginator->lastPage(),
                'from'           => $paginator->firstItem(),
                'to'             => $paginator->lastItem(),
                'has_more_pages' => $paginator->hasMorePages(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last'  => $paginator->url($paginator->lastPage()),
                'prev'  => $paginator->previousPageUrl(),
                'next'  => $paginator->nextPageUrl(),
            ],
        ];

        if (! empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a created resource response.
     *
     * @param mixed  $data
     * @param string $message
     * @param array  $meta
     *
     * @return JsonResponse
     */
    public function created(
        mixed $data = null,
        string $message = 'Resource created successfully',
        array $meta = []
    ): JsonResponse {
        return $this->success($data, $message, 201, $meta);
    }

    /**
     * Return a no content response.
     *
     * @param string $message
     *
     * @return JsonResponse
     */
    public function noContent(string $message = 'Request successful'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
        ], 204);
    }

    /**
     * Return an unauthorized response.
     *
     * @param string $message
     * @param array  $meta
     *
     * @return JsonResponse
     */
    public function unauthorized(
        string $message = 'Unauthorized',
        array $meta = []
    ): JsonResponse {
        return $this->error($message, 401, null, $meta);
    }

    /**
     * Return a forbidden response.
     *
     * @param string $message
     * @param array  $meta
     *
     * @return JsonResponse
     */
    public function forbidden(
        string $message = 'Forbidden',
        array $meta = []
    ): JsonResponse {
        return $this->error($message, 403, null, $meta);
    }

    /**
     * Return a not found response.
     *
     * @param string $message
     * @param array  $meta
     *
     * @return JsonResponse
     */
    public function notFound(
        string $message = 'Resource not found',
        array $meta = []
    ): JsonResponse {
        return $this->error($message, 404, null, $meta);
    }

    /**
     * Return a validation error response.
     *
     * @param mixed  $errors
     * @param string $message
     * @param array  $meta
     *
     * @return JsonResponse
     */
    public function validationError(
        mixed $errors,
        string $message = 'Validation failed',
        array $meta = []
    ): JsonResponse {
        return $this->error($message, 422, $errors, $meta);
    }

    /**
     * Return a server error response.
     *
     * @param string $message
     * @param mixed  $errors
     * @param array  $meta
     *
     * @return JsonResponse
     */
    public function serverError(
        string $message = 'Internal server error',
        mixed $errors = null,
        array $meta = []
    ): JsonResponse {
        return $this->error($message, 500, $errors, $meta);
    }

    /**
     * Transform data for consistent output.
     *
     * @param mixed $data
     *
     * @return mixed
     */
    private function transformData(mixed $data): mixed
    {
        if ($data instanceof JsonResource) {
            return $data->resolve();
        }

        if ($data instanceof ResourceCollection) {
            return $data->resolve();
        }

        if ($data instanceof Collection) {
            return $data->toArray();
        }

        return $data;
    }

    /**
     * Return a custom response with full control.
     *
     * @param bool   $success
     * @param mixed  $data
     * @param string $message
     * @param int    $statusCode
     * @param array  $meta
     * @param array  $extra
     *
     * @return JsonResponse
     */
    public function custom(
        bool $success,
        mixed $data = null,
        string $message = '',
        int $statusCode = 200,
        array $meta = [],
        array $extra = []
    ): JsonResponse {
        $response = array_merge([
            'success' => $success,
            'message' => $message,
        ], $extra);

        if ($data !== null) {
            $response['data'] = $this->transformData($data);
        }

        if (! empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $statusCode);
    }
}
