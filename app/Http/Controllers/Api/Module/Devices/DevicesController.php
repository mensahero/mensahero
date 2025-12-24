<?php

namespace App\Http\Controllers\Api\Module\Devices;

use App\Concerns\DeviceStatus;
use App\Facades\Api;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Modules\Devices\CreateDeviceRequest;
use App\Http\Resources\Api\Modules\Devices\DevicesResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DevicesController extends Controller
{
    /**
     * Create Device
     *
     * This device can be added as a gateway
     *
     * @param CreateDeviceRequest $request
     *
     * @return JsonResponse
     */
    public function create(CreateDeviceRequest $request): JsonResponse
    {
        $user = auth()->user();
        $request->mergeIfMissing([
            'last_seen' => now(),
            'status'    => DeviceStatus::Online,
        ]);
        $device = $user->devices()->create($request->validated());

        /** Successfully created */
        return Api::created(data: DevicesResource::make($device), message: 'Device successfully created', meta: ['retrieved_at' => now()]);
    }

    /**
     * Mark Device Online
     *
     * This will update the status of the device to online and the last seen
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function ping(Request $request): JsonResponse
    {
        $request->validate([
            'id' => ['required', 'exists:devices,id'],
        ]);

        $user = auth()->user();
        $device = $user->devices()->find($request->id);

        if (! $device) {
            /** Device Not Found */
            return Api::notFound();
        }

        $device->update([
            'status'    => DeviceStatus::Online,
            'last_seen' => now(),
        ]);

        $device->save();

        /** Request Success */
        return Api::success();

    }
}
