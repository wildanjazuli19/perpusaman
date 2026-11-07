<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RecordActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Catat hanya aksi perubahan data yang berhasil; jangan simpan isi form
        // karena form anggota memuat data pribadi dan data plaintext sementara.
        if ($request->user()
            && in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)
            && $response->getStatusCode() < 400) {

            $route = $request->route();
            $routeName = $route?->getName();
            $parameters = $route?->parameters() ?? [];
            $subjectType = null;
            $subjectId = null;

            foreach ($parameters as $parameter) {
                if (is_object($parameter) && method_exists($parameter, 'getKey')) {
                    $subjectType = class_basename($parameter);
                    $subjectId = $parameter->getKey();
                    break;
                }
            }

            $resource = $routeName ? explode('.', $routeName)[0] : 'system';
            $action = strtoupper($request->method()).' '.$resource;

            ActivityLog::create([
                'user_id' => $request->user()->getAuthIdentifier(),
                'action' => $action,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'route_name' => $routeName,
                'method' => $request->method(),
                'url' => '/'.$request->path(),
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 1000),
            ]);
        }

        return $response;
    }
}
