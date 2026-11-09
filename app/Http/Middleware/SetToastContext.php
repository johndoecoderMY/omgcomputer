<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class SetToastContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();
        $controller = $route?->getControllerClass();

        if ($controller) {
            $method = $route->getActionMethod();
            $action = $this->actionLabel($method, $request);

            if ($action) {
                $subject = $this->subjectLabel($controller, $method);
                $request->session()->flash('toast_title', "{$subject} {$action}");
            }
        }

        return $next($request);
    }

    private function subjectLabel(string $controller, string $method): string
    {
        $suffix = preg_replace('/^(store|create|update|destroy|delete|remove|save)/i', '', $method) ?? '';
        if ($suffix !== ''
            && ! str_starts_with(strtolower($suffix), 'for')
            && strtolower($suffix) !== 'authenticated') {
            return Str::headline(Str::snake($suffix));
        }

        $name = Str::beforeLast(class_basename($controller), 'Controller');

        return Str::headline(Str::snake(Str::singular($name)));
    }

    private function actionLabel(string $method, Request $request): ?string
    {
        return match (true) {
            Str::startsWith($method, ['store', 'create']) => 'creation',
            Str::startsWith($method, ['update', 'save']) => 'update',
            Str::startsWith($method, ['destroy', 'delete', 'remove']) => 'deletion',
            Str::startsWith($method, ['publish']) => 'publication',
            Str::startsWith($method, ['send', 'deliver']) => 'delivery',
            Str::startsWith($method, ['issue']) => 'issuance',
            Str::startsWith($method, ['record', 'pay']) => 'payment',
            Str::startsWith($method, ['void', 'cancel']) => 'cancellation',
            Str::startsWith($method, ['run', 'execute', 'sync']) => 'execution',
            $request->isMethod('POST') => 'submission',
            $request->isMethod('PUT') || $request->isMethod('PATCH') => 'update',
            $request->isMethod('DELETE') => 'deletion',
            default => null,
        };
    }
}
